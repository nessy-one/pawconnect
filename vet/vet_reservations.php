<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../php/db.php';

const ROLE_NAME = 'vet';

/**
 * PawConnect currently runs a single vet clinic: Angeles City Vet Office.
 * Previously this endpoint filtered every query by
 * `p.shelter = $_SESSION['facility_name']`. If that session value was ever
 * missing or didn't match the exact text typed into a pet's `shelter`
 * field on the admin side, the WHERE clause silently excluded every
 * forwarded request — the vet dashboard just looked empty, with no error.
 * Since there's only one clinic, we drop that fragile string match
 * entirely: every vet account sees every forwarded / vet-approved
 * reservation. If a second clinic is ever added, reintroduce a facility
 * filter driven by a real facility_id column instead of free-text matching.
 */
const FACILITY_NAME = 'Angeles City Vet Office';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_NAME) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$vetId  = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list_pending';

function notifyApplicantFromVet(mysqli $conn, int $applicantUserId, string $petName, string $type, string $title, string $message): void {
    $target = (string) $applicantUserId;
    $stmt = mysqli_prepare($conn, "
        INSERT INTO notifications (title, message, type, target, event_date, location)
        VALUES (?, ?, ?, ?, NULL, NULL)
    ");
    mysqli_stmt_bind_param($stmt, 'ssss', $title, $message, $type, $target);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

try {
    switch ($action) {
        // Requests the admin has forwarded to the vet, awaiting an approve/deny decision.
        case 'list_forwarded': {
            $stmt = $conn->prepare("
                SELECT r.id, r.name AS applicant_name, r.contact AS applicant_contact,
                    r.visit_date, r.status, r.forwarded_at,
                    p.name AS pet_name, p.type AS pet_type, p.breed AS pet_breed, p.shelter AS pet_shelter
                FROM reservations r
                LEFT JOIN pet p ON p.id = r.pet_id
                WHERE r.status = 'forwarded'
                ORDER BY r.forwarded_at DESC
            ");
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['success' => true, 'reservations' => $rows]);
            break;
        }

        // Reservations the vet has already approved and can now report a completed hand-off for.
        case 'list_pending': {
            $stmt = $conn->prepare("
                SELECT r.id, r.name AS applicant_name, r.contact AS applicant_contact,
                    r.visit_date, r.status, r.proof_photos,
                    p.name AS pet_name, p.type AS pet_type, p.breed AS pet_breed, p.shelter AS pet_shelter
                FROM reservations r
                LEFT JOIN pet p ON p.id = r.pet_id
                WHERE r.status = 'vet_approved'
                ORDER BY r.vet_decided_at DESC
            ");
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$row) {
                $row['has_proof'] = !empty($row['proof_photos']);
                unset($row['proof_photos']);
            }
            echo json_encode(['success' => true, 'reservations' => $rows]);
            break;
        }

        // The vet approves or denies a request that was forwarded to them.
        case 'decide': {
            $d        = json_decode(file_get_contents('php://input'), true);
            $id       = (int)($d['id'] ?? 0);
            $decision = $d['decision'] ?? '';
            $notes    = trim($d['notes'] ?? '');

            if (!$id || !in_array($decision, ['approve', 'deny'], true)) {
                echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters.']);
                exit;
            }

            $stmt = $conn->prepare("
                SELECT r.id, r.status, r.pet_id, r.user_id, p.name AS pet_name
                FROM reservations r LEFT JOIN pet p ON p.id = r.pet_id
                WHERE r.id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) { echo json_encode(['success' => false, 'message' => 'Reservation not found.']); exit; }
            if ($row['status'] !== 'forwarded') {
                echo json_encode(['success' => false, 'message' => 'This request is no longer awaiting a vet decision.']);
                exit;
            }

            $conn->begin_transaction();
            try {
                $newStatus = $decision === 'approve' ? 'vet_approved' : 'vet_denied';
                $s1 = $conn->prepare("UPDATE reservations SET status = ?, vet_decision_notes = ?, vet_decided_at = NOW() WHERE id = ?");
                $s1->bind_param('ssi', $newStatus, $notes, $id);
                $s1->execute();
                $s1->close();

                $petName = $row['pet_name'] ?: 'the pet';

                if ($decision === 'deny') {
                    $s2 = $conn->prepare("UPDATE pet SET status = 'available', reserved_days = NULL, reserved_at = NULL WHERE id = ?");
                    $s2->bind_param('i', $row['pet_id']);
                    $s2->execute();
                    $s2->close();

                    notifyApplicantFromVet($conn, (int)$row['user_id'], $petName, 'adoption_denied',
                        'Update on Your Adoption Request',
                        "We're sorry — after review by the facility, your reservation for {$petName} could not be approved. {$petName} has been made available again.");
                } else {
                    notifyApplicantFromVet($conn, (int)$row['user_id'], $petName, 'adoption_approved',
                        'Good News — Vet Clearance Approved!',
                        "{$petName}'s reservation has been reviewed and approved by " . FACILITY_NAME . ". The facility will now arrange the hand-off — we'll notify you again once it's complete.");
                }
                $adminMsg  = $decision === 'approve'
                    ? "Vet approved the adoption of {$petName} — awaiting hand-off report."
                    : "Vet denied the adoption of {$petName}.";
                $adminType = $decision === 'approve' ? 'vet_approved' : 'vet_denied';
                $sn = $conn->prepare("INSERT INTO admin_notifications (type, message, reservation_id) VALUES (?, ?, ?)");
                $sn->bind_param('ssi', $adminType, $adminMsg, $id);
                $sn->execute();
                $sn->close();

                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('vet_reservations.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(), // remove/trim this detail once things are stable in production
    ]);
}