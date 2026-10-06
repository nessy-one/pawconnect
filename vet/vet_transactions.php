<?php
/**
 * PawConnect - Vet Transactions API (mysqli version)
 * Lets a logged-in vet report a completed adoption hand-off, with a photo,
 * for the admin to review. Mirrors the session/role pattern used by
 * admin_reservations.php and the query style used by notifications.php.
 *
 * Actions:
 *   GET  ?action=list    -> this vet's own reported transactions
 *   POST action=create   -> multipart/form-data: animal_name, adopter_name,
 *                           notes, photo (file). Inserts with status 'pending'.
 *
 * Expects the vet to be logged in with $_SESSION['user_id'] and
 * $_SESSION['role'] === 'vet' — adjust ROLE_NAME below if your login
 * flow uses a different string (e.g. 'clinic' or 'veterinarian').
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../php/db.php';

const ROLE_NAME = 'vet';
const UPLOAD_DIR = __DIR__ . '/../uploads/transactions/';   // filesystem path

/**
 * FIX: this used to be 'uploads/transactions/' — a relative path with no
 * leading segment. That gets stored in the DB and handed straight to the
 * browser as an <img src>, but it's rendered inside pages that live one
 * folder below the project root (/admin/admin_dashboard.php and
 * /vet/vet_dashboard.php). A relative path with no '../' resolves against
 * THOSE folders, so the browser was requesting
 * /admin/uploads/transactions/xxx.jpg or /vet/uploads/transactions/xxx.jpg —
 * neither of which exists, since uploads/ is a sibling of admin/ and vet/,
 * not nested inside them. That's why the photo never showed up on either
 * side. '../uploads/transactions/' resolves correctly from both.
 */
const UPLOAD_URL_PREFIX = '../uploads/transactions/';        // path used by <img src> from /admin/ and /vet/ pages
const MAX_PHOTO_BYTES = 5 * 1024 * 1024; // 5MB
const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_NAME) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$vetId  = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {

        case 'list': {
            $stmt = $conn->prepare("
                SELECT id, animal_name, adopter_name, notes, photo_path, status, created_at
                FROM vet_transactions
                WHERE vet_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->bind_param('i', $vetId);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$row) {
                $row['photo_url'] = $row['photo_path'] ? $row['photo_path'] : null;
            }

            echo json_encode(['success' => true, 'transactions' => $rows]);
            break;
        }

        case 'create': {
            $reservationId = (int) ($_POST['reservation_id'] ?? 0);
            $notes         = trim($_POST['notes'] ?? '');

            if (!$reservationId) {
                echo json_encode(['success' => false, 'message' => 'Please select an adoption request.']);
                break;
            }
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'A photo is required.']);
                break;
            }

            $stmt = $conn->prepare("
                SELECT r.id, r.name AS applicant_name, r.status, r.proof_photos, p.name AS pet_name
                FROM reservations r
                LEFT JOIN pet p ON p.id = r.pet_id
                WHERE r.id = ?
            ");
            $stmt->bind_param('i', $reservationId);
            $stmt->execute();
            $reservation = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$reservation) {
                echo json_encode(['success' => false, 'message' => 'Adoption request not found.']);
                break;
            }
            if ($reservation['status'] !== 'vet_approved') {
                echo json_encode(['success' => false, 'message' => 'You can only report a hand-off for a request you have approved.']);
                break;
            }

            $file = $_FILES['photo'];
            if ($file['size'] > MAX_PHOTO_BYTES) {
                echo json_encode(['success' => false, 'message' => 'Photo must be under 5MB.']);
                break;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXT, true)) {
                echo json_encode(['success' => false, 'message' => 'Photo must be jpg, jpeg, png, or webp.']);
                break;
            }

            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            $filename = 'txn_' . $vetId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = UPLOAD_DIR . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'Could not save the photo.']);
                break;
            }
            $photoPath   = UPLOAD_URL_PREFIX . $filename;
            $animalName  = $reservation['pet_name'] ?? '';
            $adopterName = $reservation['applicant_name'] ?? '';

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    INSERT INTO vet_transactions (vet_id, reservation_id, animal_name, adopter_name, notes, photo_path, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param('iissss', $vetId, $reservationId, $animalName, $adopterName, $notes, $photoPath);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();

                // This is the part that actually surfaces the photo on the admin side —
                // it appends into the same proof_photos column the verify modal reads.
                $existing = $reservation['proof_photos'] ? json_decode($reservation['proof_photos'], true) : [];
                if (!is_array($existing)) $existing = [];
                $existing[] = $photoPath;
                $updatedJson = json_encode($existing);

                $upd = $conn->prepare("UPDATE reservations SET proof_photos = ? WHERE id = ?");
                $upd->bind_param('si', $updatedJson, $reservationId);
                $upd->execute();
                $upd->close();

                $adminMsg = "Vet reported a completed hand-off for " . ($animalName ?: 'a pet') . " — awaiting your review.";
                $adminType = 'handoff_reported';
                $sn = $conn->prepare("INSERT INTO admin_notifications (type, message, reservation_id) VALUES (?, ?, ?)");
                $sn->bind_param('ssi', $adminType, $adminMsg, $reservationId);
                $sn->execute();
                $sn->close();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Could not submit report.']);
                break;
            }

            echo json_encode([
                'success' => true,
                'transaction' => [
                    'id' => $newId,
                    'reservation_id' => $reservationId,
                    'animal_name' => $animalName,
                    'adopter_name' => $adopterName,
                    'notes' => $notes,
                    'photo_path' => $photoPath,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}