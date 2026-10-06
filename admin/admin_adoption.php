<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php';
//admin_adoption.php

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? 'list';

function notifyApplicant(mysqli $conn, int $applicantUserId, string $petName, string $type, string $title, string $message): void {
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
        case 'list': {
            $sql = "SELECT r.id, r.user_id, r.pet_id, r.name AS applicant_name, r.contact AS applicant_contact,
                        r.visit_date, r.duration_days, r.notes, r.status, r.created_at,
                        r.forwarded_at, r.vet_decision_notes, r.vet_decided_at,
                        r.proof_photos, r.admin_proof_photos, r.verified_by, r.verified_at,
                        p.name AS pet_name, p.type AS pet_type, p.breed AS pet_breed, p.shelter AS pet_shelter,
                        u.email AS applicant_email
                    FROM reservations r
                    LEFT JOIN pet p   ON p.id = r.pet_id
                    LEFT JOIN users u ON u.id = r.user_id
                    ORDER BY r.created_at DESC";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['evidence_photos']    = $row['proof_photos'] ? json_decode($row['proof_photos'], true) : [];
                $row['admin_proof_photos'] = $row['admin_proof_photos'] ? json_decode($row['admin_proof_photos'], true) : [];
                unset($row['proof_photos']);
                $rows[] = $row;
            }
            echo json_encode(['success' => true, 'requests' => $rows]);
            break;
        }

        // Admin sends a pending request to the vet/facility for review instead of deciding it directly.
        case 'forward': {
            $d  = json_decode(file_get_contents('php://input'), true);
            $id = (int)($d['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id.']); exit; }

            $stmt = mysqli_prepare($conn, "SELECT status FROM reservations WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Reservation not found.']); exit; }
            if ($row['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Only pending requests can be forwarded.']);
                exit;
            }

            $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'forwarded', forwarded_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            echo json_encode(['success' => true]);
            break;
        }

        // Admin can still reject a request outright without involving the vet.
        case 'deny': {
            $d  = json_decode(file_get_contents('php://input'), true);
            $id = (int)($d['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id.']); exit; }

            $stmt = mysqli_prepare($conn, "
                SELECT r.pet_id, r.user_id, r.status, p.name AS pet_name
                FROM reservations r LEFT JOIN pet p ON p.id = r.pet_id
                WHERE r.id = ?
            ");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Reservation not found.']); exit; }
            if ($row['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'This request has already moved past admin review.']);
                exit;
            }

            mysqli_begin_transaction($conn);
            try {
                $s1 = mysqli_prepare($conn, "UPDATE reservations SET status = 'denied' WHERE id = ?");
                mysqli_stmt_bind_param($s1, "i", $id);
                mysqli_stmt_execute($s1);

                $s2 = mysqli_prepare($conn, "UPDATE pet SET status = 'available', reserved_days = NULL, reserved_at = NULL WHERE id = ?");
                mysqli_stmt_bind_param($s2, "i", $row['pet_id']);
                mysqli_stmt_execute($s2);

                $petName = $row['pet_name'] ?: 'the pet';
                notifyApplicant($conn, (int)$row['user_id'], $petName, 'adoption_denied',
                    'Update on Your Adoption Request',
                    "We're sorry — your reservation for {$petName} was not approved this time. {$petName} has been made available again for other applicants.");

                mysqli_commit($conn);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        }

        // Vet-submitted hand-off reports awaiting the admin's final sign-off.
        case 'list_transactions': {
            $sql = "SELECT t.id, t.reservation_id, t.animal_name, t.adopter_name, t.notes,
                        t.photo_path AS photo_url, t.status, t.created_at,
                        r.user_id AS applicant_user_id, r.pet_id
                    FROM vet_transactions t
                    LEFT JOIN reservations r ON r.id = t.reservation_id
                    WHERE t.status = 'pending'
                    ORDER BY t.created_at DESC";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['vet_facility'] = 'Angeles City Vet Office';
                $rows[] = $row;
            }
            echo json_encode(['success' => true, 'transactions' => $rows]);
            break;
        }

        // Admin reviews the vet's hand-off proof and finalizes the adoption.
        case 'acknowledge_transaction': {
            $d       = json_decode(file_get_contents('php://input'), true);
            $txnId = (int)($_POST['transaction_id'] ?? 0);

            define('ADMIN_PROOF_DIR', __DIR__ . '/../uploads/admin_proof/');
            // FIX: this used to be '/uploads/admin_proof/' — an absolute
            // path missing the app's own folder segment. The site lives
            // under /pawconnect/ (see rfid_bridge.py's SERVER_URL), so the
            // browser was requesting /uploads/admin_proof/... at the domain
            // root instead of /pawconnect/uploads/admin_proof/..., and the
            // photo never loaded. Same fix already applied in
            // vet_transactions.php and admin_donations.php.
            define('ADMIN_PROOF_URL', '/pawconnect/uploads/admin_proof/');

            $newProofUrls = [];
            if (!empty($_FILES['admin_proof']['name']) && is_array($_FILES['admin_proof']['name'])) {
                if (!is_dir(ADMIN_PROOF_DIR)) mkdir(ADMIN_PROOF_DIR, 0755, true);
                foreach ($_FILES['admin_proof']['name'] as $i => $name) {
                    if ($_FILES['admin_proof']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) continue;
                    $filename = 'proof_' . $txnId . '_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['admin_proof']['tmp_name'][$i], ADMIN_PROOF_DIR . $filename)) {
                        $newProofUrls[] = ADMIN_PROOF_URL . $filename;
                    }
                }
            }

            if (!$txnId) { echo json_encode(['success' => false, 'message' => 'Missing transaction_id.']); exit; }
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Admin session missing user_id.']); exit;
            }
            $adminId = (int) $_SESSION['user_id'];

            $stmt = mysqli_prepare($conn, "
                SELECT t.id, t.status, t.reservation_id, t.animal_name,
                    r.pet_id, r.user_id AS applicant_id, r.admin_proof_photos
                FROM vet_transactions t
                LEFT JOIN reservations r ON r.id = t.reservation_id
                WHERE t.id = ?
            ");
            mysqli_stmt_bind_param($stmt, "i", $txnId);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Transaction report not found.']); exit; }
            if ($row['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'This report has already been acknowledged.']);
                exit;
            }
            if ($row['reservation_id'] === null) {
                echo json_encode(['success' => false, 'message' => 'This report is not linked to a reservation.']);
                exit;
            }
            $chk = mysqli_prepare($conn, "SELECT status FROM reservations WHERE id = ?");
            mysqli_stmt_bind_param($chk, "i", $row['reservation_id']);
            mysqli_stmt_execute($chk);
            $resv = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
            if (!$resv || $resv['status'] !== 'vet_approved') {
                echo json_encode(['success' => false, 'message' => 'This reservation is not awaiting hand-off confirmation.']);
                exit;
            }

            mysqli_begin_transaction($conn);
            try {
                $s1 = mysqli_prepare($conn, "UPDATE vet_transactions SET status = 'acknowledged', acknowledged_by = ?, acknowledged_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($s1, "ii", $adminId, $txnId);
                mysqli_stmt_execute($s1);

                $existing = $row['admin_proof_photos'] ? json_decode($row['admin_proof_photos'], true) : [];
                if (!is_array($existing)) $existing = [];
                $mergedProof = array_merge($existing, $newProofUrls);
                $proofJson = json_encode($mergedProof);

                $s2 = mysqli_prepare($conn, "
                    UPDATE reservations
                    SET status = 'completed', admin_proof_photos = ?, verified_by = ?, verified_at = NOW()
                    WHERE id = ?
                ");
                mysqli_stmt_bind_param($s2, "sii", $proofJson, $adminId, $row['reservation_id']);
                mysqli_stmt_execute($s2);

                $s3 = mysqli_prepare($conn, "UPDATE pet SET status = 'adopted', user_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($s3, "ii", $row['applicant_id'], $row['pet_id']);
                mysqli_stmt_execute($s3);

                $petName = $row['animal_name'] ?: 'the pet';
                // FIX: dropped the trailing emoji (🎉) — this was the source
                // of the emoji baked into notifications rows 6 and 13 in the
                // original dump. Icons/emoji should come from the UI layer
                // (Font Awesome), not be hardcoded into stored message text.
                notifyApplicant($conn, (int)$row['applicant_id'], $petName, 'adoption_approved',
                    'Adoption Verified & Complete!',
                    "Great news — your adoption of {$petName} has been verified and marked complete. Thank you for adopting through PawConnect!");

                mysqli_commit($conn);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        }

        case 'list_notifications': {
            $res = mysqli_query($conn, "SELECT id, type, message, reservation_id, is_read, created_at
                                        FROM admin_notifications
                                        ORDER BY created_at DESC LIMIT 30");
            if (!$res) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            echo json_encode(['success' => true, 'notifications' => mysqli_fetch_all($res, MYSQLI_ASSOC)]);
            break;
        }

        case 'mark_notifications_read': {
            mysqli_query($conn, "UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
            echo json_encode(['success' => true]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_adoption.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(), // remove/trim this detail once things are stable in production
    ]);
}