<?php
// admin_donations.php
session_start();
header('Content-Type: application/json');

ob_start();
register_shutdown_function(function () {
    $err = error_get_last();
    $isFatal = $err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    if ($isFatal) {
        if (ob_get_level() > 0) ob_end_clean();
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        error_log('admin_donations.php FATAL: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error: ' . $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')',
        ]);
    }
});

$dbPath = __DIR__ . '/../php/db.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Server misconfiguration: db.php not found at $dbPath"]);
    exit;
}
require $dbPath;

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: db.php did not set up a valid $conn (mysqli instance)']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

define('DONATION_UPLOAD_DIR', __DIR__ . '/../uploads/donations/');
// FIX: this used to be '/uploads/donations/' — an absolute path with no
// leading segment for the app's own folder. The site is deployed under
// /pawconnect/ (see rfid_bridge.py's SERVER_URL), so a bare '/uploads/...'
// resolves against the domain root instead of /pawconnect/uploads/...,
// meaning the browser requested a URL that doesn't exist and every
// donation photo came back broken. Same class of bug vet_transactions.php
// already fixed for hand-off photos — this applies the same fix here.
define('DONATION_UPLOAD_URL', '/pawconnect/uploads/donations/');

function saveUploadedDonationPhotos(array $files): array {
    $saved = [];
    if (empty($files['name']) || !is_array($files['name'])) return $saved;

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!is_dir(DONATION_UPLOAD_DIR)) {
        mkdir(DONATION_UPLOAD_DIR, 0755, true);
    }

    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;
        if (($files['size'][$i] ?? 0) > 5 * 1024 * 1024) continue;
        if (@getimagesize($files['tmp_name'][$i]) === false) continue;

        $filename = 'donation_' . time() . '_' . $i . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $destination = DONATION_UPLOAD_DIR . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
            $saved[] = DONATION_UPLOAD_URL . $filename;
        }
    }
    return $saved;
}

function deleteDonationPhotoFiles(array $urlPaths): void {
    foreach ($urlPaths as $url) {
        $path = DONATION_UPLOAD_DIR . basename($url);
        if (is_file($path)) @unlink($path);
    }
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {

        case 'list': {
            $result = mysqli_query($conn, "SELECT * FROM donations ORDER BY date DESC, id DESC");
            if (!$result) throw new RuntimeException(mysqli_error($conn));
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $row['photos'] = $row['photos'] ? json_decode($row['photos'], true) : [];
                $rows[] = $row;
            }
            echo json_encode(['success' => true, 'donations' => $rows]);
            break;
        }

        case 'create': {
            $date   = $_POST['date'] ?? '';
            $amount = (float) ($_POST['amount'] ?? 0);
            $desc   = trim($_POST['description'] ?? '');

            if (!$date) { echo json_encode(['success' => false, 'message' => 'Date is required.']); break; }
            if ($amount <= 0) { echo json_encode(['success' => false, 'message' => 'Please enter a valid amount used.']); break; }
            if ($desc === '') { echo json_encode(['success' => false, 'message' => 'Please describe what the donation was used for.']); break; }

            $photos = isset($_FILES['photos']) ? saveUploadedDonationPhotos($_FILES['photos']) : [];
            $photosJson = json_encode($photos);
            $loggedBy = $_SESSION['admin_name'] ?? $_SESSION['name'] ?? 'Admin';

            $stmt = mysqli_prepare($conn, "INSERT INTO donations (date, amount, description, photos, logged_by) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) throw new RuntimeException(mysqli_error($conn));
            mysqli_stmt_bind_param($stmt, "sdsss", $date, $amount, $desc, $photosJson, $loggedBy);
            mysqli_stmt_execute($stmt);

            echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
            break;
        }

        case 'update': {
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing donation id.']); break; }

            $date   = $_POST['date'] ?? '';
            $amount = (float) ($_POST['amount'] ?? 0);
            $desc   = trim($_POST['description'] ?? '');

            if (!$date) { echo json_encode(['success' => false, 'message' => 'Date is required.']); break; }
            if ($amount <= 0) { echo json_encode(['success' => false, 'message' => 'Please enter a valid amount used.']); break; }
            if ($desc === '') { echo json_encode(['success' => false, 'message' => 'Please describe what the donation was used for.']); break; }

            $old = mysqli_prepare($conn, "SELECT photos FROM donations WHERE id = ?");
            mysqli_stmt_bind_param($old, "i", $id);
            mysqli_stmt_execute($old);
            $oldRow = mysqli_fetch_assoc(mysqli_stmt_get_result($old));
            if (!$oldRow) { echo json_encode(['success' => false, 'message' => 'Donation entry not found.']); break; }
            $oldPhotos = $oldRow['photos'] ? json_decode($oldRow['photos'], true) : [];

            $keptPhotos = json_decode($_POST['existing_photos'] ?? '[]', true);
            if (!is_array($keptPhotos)) $keptPhotos = [];

            $removed = array_diff($oldPhotos, $keptPhotos);
            deleteDonationPhotoFiles($removed);

            $newPhotos = isset($_FILES['photos']) ? saveUploadedDonationPhotos($_FILES['photos']) : [];
            $finalPhotos = array_merge($keptPhotos, $newPhotos);
            $photosJson = json_encode(array_values($finalPhotos));

            $stmt = mysqli_prepare($conn, "UPDATE donations SET date=?, amount=?, description=?, photos=? WHERE id=?");
            if (!$stmt) throw new RuntimeException(mysqli_error($conn));
            mysqli_stmt_bind_param($stmt, "sdssi", $date, $amount, $desc, $photosJson, $id);
            mysqli_stmt_execute($stmt);

            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            $d  = json_decode(file_get_contents('php://input'), true);
            $id = (int) ($d['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing donation id.']); break; }

            $stmt = mysqli_prepare($conn, "SELECT photos FROM donations WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            if ($row && $row['photos']) {
                deleteDonationPhotoFiles(json_decode($row['photos'], true) ?: []);
            }

            $del = mysqli_prepare($conn, "DELETE FROM donations WHERE id = ?");
            mysqli_stmt_bind_param($del, "i", $id);
            mysqli_stmt_execute($del);

            echo json_encode(['success' => true]);
            break;
        }

        case 'list_received': {
            $result = mysqli_query($conn, "
                SELECT id, first_name, last_name, email, mobile, purpose, amount, recurring, status, created_at
                FROM user_donations
                ORDER BY created_at DESC, id DESC
            ");
            if (!$result) throw new RuntimeException(mysqli_error($conn));
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
            echo json_encode(['success' => true, 'donations' => $rows]);
            break;
        }

        // Pulls from the separate user_donations table (money coming IN
        // from donors) so the admin can compare it against this table's
        // usage log (money going OUT, with receipts). Two different tables
        // on purpose — see user_donations_migration.sql for why.
        case 'received_stats': {
            $result = mysqli_query($conn, "
                SELECT COUNT(*) AS donor_count, COALESCE(SUM(amount), 0) AS total_received
                FROM user_donations
                WHERE status = 'completed'
            ");
            if (!$result) throw new RuntimeException(mysqli_error($conn));
            $row = mysqli_fetch_assoc($result);
            echo json_encode([
                'success' => true,
                'total_received' => (float) $row['total_received'],
                'donation_count' => (int) $row['donor_count'],
            ]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_donations.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}