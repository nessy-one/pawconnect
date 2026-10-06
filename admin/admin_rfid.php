<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php';
// admin_rfid.php — RFID chip registry.
// Admin AND vet sessions can manage tags here. The physical reader
// (Arduino) authenticates separately via device_key, not a session,
// through the `scan` action below.

$action = $_GET['action'] ?? 'list';

// NOTE: this used to also handle action=scan for the physical reader,
// via handleDeviceScan() below. That duplicated rfid_scan.php exactly —
// rfid_bridge.py only ever posts to rfid_scan.php, so the copy here was
// dead code and has been removed. rfid_scan.php remains the single
// device-facing scan endpoint, authenticated by device_key instead of a
// session.

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'vet'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

function friendlyDuplicateMessage(mysqli $conn): string {
    $err = mysqli_error($conn);
    if (strpos($err, 'chip_uid') !== false) {
        return 'That chip ID is already registered to another tag.';
    }
    if (strpos($err, 'uniq_active_pet') !== false) {
        return 'This animal already has an active RFID tag. Deactivate or replace the existing one first.';
    }
    return 'That record already exists.';
}

function logScan(mysqli $conn, int $tagId, ?int $facilityId, string $eventType, ?string $note): void {
    $stmt = mysqli_prepare($conn, "INSERT INTO rfid_scans (rfid_tag_id, facility_id, event_type, note) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiss", $tagId, $facilityId, $eventType, $note);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function normalizeChipUid(string $raw): string {
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw));
}

// Enforces "one active tag per pet"
function hasActiveTagForPet(mysqli $conn, int $petId, ?int $excludeTagId = null): bool {
    $sql = "SELECT id FROM rfid_tags WHERE pet_id = ? AND status = 'active'";
    if ($excludeTagId) $sql .= " AND id != ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($excludeTagId) {
        mysqli_stmt_bind_param($stmt, "ii", $petId, $excludeTagId);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $petId);
    }
    mysqli_stmt_execute($stmt);
    return (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

try {
    switch ($action) {
        // All tags, with the animal, facility, and (if adopted) the owner joined in.
        case 'list': {
            $sql = "SELECT rt.id, rt.chip_uid, rt.pet_id, rt.facility_id, rt.status,
                        rt.previous_chip_id, rt.registered_at,
                        p.name AS pet_name, p.type AS pet_type, p.breed AS pet_breed,
                        p.status AS pet_status,
                        f.name AS facility_name,
                        owner.id AS owner_id, owner.name AS owner_name
                    FROM rfid_tags rt
                    LEFT JOIN pet p       ON p.id = rt.pet_id
                    LEFT JOIN facility f  ON f.id = rt.facility_id
                    LEFT JOIN users owner ON owner.id = p.user_id
                    ORDER BY rt.registered_at DESC";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
            echo json_encode(['success' => true, 'tags' => $rows]);
            break;
        }

        // For populating a "which animal" dropdown in the register/edit form.
        case 'list_pets': {
            $result = mysqli_query($conn, "SELECT id, name, type, breed, status, vaccinated, dewormed, neutered, health_status, medical_notes FROM pet ORDER BY name");
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
            echo json_encode(['success' => true, 'pets' => $rows]);
            break;
        }

        case 'update_pet_medical': {
            $d     = json_decode(file_get_contents('php://input'), true);
            $petId = (int)($d['pet_id'] ?? 0);
            if (!$petId) { echo json_encode(['success' => false, 'message' => 'Missing pet_id.']); exit; }

            $vaccinated   = !empty($d['vaccinated']) ? 1 : 0;
            $dewormed     = !empty($d['dewormed']) ? 1 : 0;
            $neutered     = !empty($d['neutered']) ? 1 : 0;
            $healthStatus = trim($d['health_status'] ?? '');
            $medicalNotes = trim($d['medical_notes'] ?? '');

            $stmt = mysqli_prepare($conn, "UPDATE pet SET vaccinated = ?, dewormed = ?, neutered = ?, health_status = ?, medical_notes = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "iiissi", $vaccinated, $dewormed, $neutered, $healthStatus, $medicalNotes, $petId);
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            echo json_encode(['success' => true]);
            break;
        }

        case 'register': {
            $d     = json_decode(file_get_contents('php://input'), true);
            $chip  = normalizeChipUid($d['chip_uid'] ?? '');
            $petId = (int)($d['pet_id'] ?? 0);
            $facId = (isset($d['facility_id']) && $d['facility_id'] !== '') ? (int)$d['facility_id'] : null;

            if ($chip === '' || !$petId) {
                echo json_encode(['success' => false, 'message' => 'Chip ID and animal are required.']);
                exit;
            }
            $check = mysqli_prepare($conn, "SELECT id FROM pet WHERE id = ?");
            mysqli_stmt_bind_param($check, "i", $petId);
            mysqli_stmt_execute($check);
            if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
                echo json_encode(['success' => false, 'message' => 'That animal does not exist.']);
                exit;
            }
            if (hasActiveTagForPet($conn, $petId)) {
                echo json_encode(['success' => false, 'message' => 'This animal already has an active RFID tag. Deactivate or replace the existing one first.']);
                exit;
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO rfid_tags (chip_uid, pet_id, facility_id, status) VALUES (?, ?, ?, 'active')");
            mysqli_stmt_bind_param($stmt, "sii", $chip, $petId, $facId);
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => false, 'message' => friendlyDuplicateMessage($conn)]);
                exit;
            }
            $newId = mysqli_insert_id($conn);
            logScan($conn, $newId, $facId, 'registered', 'Chip implanted & registered by ' . $_SESSION['role']);
            echo json_encode(['success' => true, 'id' => $newId]);
            break;
        }

        // Correct which animal / facility a tag is linked to.
        case 'update': {
            $d     = json_decode(file_get_contents('php://input'), true);
            $id    = (int)($d['id'] ?? 0);
            $petId = (int)($d['pet_id'] ?? 0);
            $facId = (isset($d['facility_id']) && $d['facility_id'] !== '') ? (int)$d['facility_id'] : null;
            if (!$id || !$petId) { echo json_encode(['success' => false, 'message' => 'Missing fields.']); exit; }

            $cur = mysqli_prepare($conn, "SELECT status FROM rfid_tags WHERE id = ?");
            mysqli_stmt_bind_param($cur, "i", $id);
            mysqli_stmt_execute($cur);
            $curRow = mysqli_fetch_assoc(mysqli_stmt_get_result($cur));
            if (!$curRow) { echo json_encode(['success' => false, 'message' => 'Tag not found.']); exit; }
            if ($curRow['status'] === 'active' && hasActiveTagForPet($conn, $petId, $id)) {
                echo json_encode(['success' => false, 'message' => 'This animal already has an active RFID tag. Deactivate or replace the existing one first.']);
                exit;
            }

            $stmt = mysqli_prepare($conn, "UPDATE rfid_tags SET pet_id = ?, facility_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "iii", $petId, $facId, $id);
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => false, 'message' => friendlyDuplicateMessage($conn)]);
                exit;
            }
            logScan($conn, $id, $facId, 'updated', 'Record edited by ' . $_SESSION['role']);
            echo json_encode(['success' => true]);
            break;
        }

        // Deactivate / reactivate.
        case 'set_status': {
            $d      = json_decode(file_get_contents('php://input'), true);
            $id     = (int)($d['id'] ?? 0);
            $status = $d['status'] ?? '';
            if (!$id || !in_array($status, ['active', 'deactivated'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
            }
            $cur = mysqli_prepare($conn, "SELECT status, facility_id, pet_id FROM rfid_tags WHERE id = ?");
            mysqli_stmt_bind_param($cur, "i", $id);
            mysqli_stmt_execute($cur);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($cur));
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Tag not found.']); exit; }
            if ($row['status'] === 'replaced') {
                echo json_encode(['success' => false, 'message' => 'A replaced chip can\'t be reactivated — register a new chip instead.']);
                exit;
            }
            if ($status === 'active' && $row['pet_id'] && hasActiveTagForPet($conn, (int)$row['pet_id'], $id)) {
                echo json_encode(['success' => false, 'message' => 'This animal already has an active RFID tag. Deactivate or replace the existing one first.']);
                exit;
            }

            $stmt = mysqli_prepare($conn, "UPDATE rfid_tags SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            if (!mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => false, 'message' => friendlyDuplicateMessage($conn)]);
                exit;
            }
            logScan($conn, $id, $row['facility_id'], $status === 'active' ? 'reactivated' : 'deactivated', 'By ' . $_SESSION['role']);
            echo json_encode(['success' => true]);
            break;
        }

        // Retire an old chip, mint a new one for the same animal.
        case 'replace': {
            $d       = json_decode(file_get_contents('php://input'), true);
            $id      = (int)($d['id'] ?? 0);
            $newChip = normalizeChipUid($d['new_chip_uid'] ?? '');
            if (!$id || $newChip === '') { echo json_encode(['success' => false, 'message' => 'Missing fields.']); exit; }

            $cur = mysqli_prepare($conn, "SELECT * FROM rfid_tags WHERE id = ?");
            mysqli_stmt_bind_param($cur, "i", $id);
            mysqli_stmt_execute($cur);
            $old = mysqli_fetch_assoc(mysqli_stmt_get_result($cur));
            if (!$old) { echo json_encode(['success' => false, 'message' => 'Tag not found.']); exit; }
            if ($old['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Only an active chip can be replaced.']); exit;
            }

            mysqli_begin_transaction($conn);
            try {
                $s1 = mysqli_prepare($conn, "UPDATE rfid_tags SET status = 'replaced' WHERE id = ?");
                mysqli_stmt_bind_param($s1, "i", $id);
                mysqli_stmt_execute($s1);

                $s2 = mysqli_prepare($conn, "INSERT INTO rfid_tags (chip_uid, pet_id, facility_id, status, previous_chip_id) VALUES (?, ?, ?, 'active', ?)");
                mysqli_stmt_bind_param($s2, "siii", $newChip, $old['pet_id'], $old['facility_id'], $id);
                mysqli_stmt_execute($s2);
                $newId = mysqli_insert_id($conn);

                logScan($conn, $id, $old['facility_id'], 'replaced', "Replaced with new chip {$newChip}");
                logScan($conn, $newId, $old['facility_id'], 'registered', "Replacement chip for tag #{$id}");

                mysqli_commit($conn);
                echo json_encode(['success' => true, 'new_id' => $newId]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $msg = strpos(mysqli_error($conn), 'chip_uid') !== false ? 'That new chip ID is already registered.' : $e->getMessage();
                echo json_encode(['success' => false, 'message' => $msg]);
            }
            break;
        }

        // Change who *owns* the animal this tag is on (writes pet.user_id).
        // The chip itself doesn't move — same animal, new registered owner.
        case 'transfer_owner': {
            $d          = json_decode(file_get_contents('php://input'), true);
            $id         = (int)($d['id'] ?? 0); // rfid_tags.id
            $newOwnerId = (int)($d['new_owner_id'] ?? 0);
            $reason     = trim($d['reason'] ?? '');
            if (!$id || !$newOwnerId) { echo json_encode(['success' => false, 'message' => 'Missing fields.']); exit; }

            $cur = mysqli_prepare($conn, "SELECT pet_id, facility_id FROM rfid_tags WHERE id = ?");
            mysqli_stmt_bind_param($cur, "i", $id);
            mysqli_stmt_execute($cur);
            $tag = mysqli_fetch_assoc(mysqli_stmt_get_result($cur));
            if (!$tag || !$tag['pet_id']) { echo json_encode(['success' => false, 'message' => 'Tag is not linked to an animal.']); exit; }

            $stmt = mysqli_prepare($conn, "UPDATE pet SET user_id = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $newOwnerId, $tag['pet_id']);
            mysqli_stmt_execute($stmt);

            logScan($conn, $id, $tag['facility_id'], 'ownership_transfer', $reason !== '' ? $reason : ('Ownership updated by ' . $_SESSION['role']));
            echo json_encode(['success' => true]);
            break;
        }

        case 'history': {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id.']); exit; }
            $stmt = mysqli_prepare($conn, "SELECT scanned_at, event_type, note, facility_id FROM rfid_scans WHERE rfid_tag_id = ? ORDER BY scanned_at DESC");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $rows = [];
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
            echo json_encode(['success' => true, 'scans' => $rows]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_rfid.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(), // remove/trim this detail once things are stable in production
    ]);
}