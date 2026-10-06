<?php
// php/reports.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

// FIX: this used to reimplement its own DB connection with hardcoded
// credentials instead of reusing db.php — meaning the credentials lived
// in two places that had to be kept in sync by hand. Use the shared
// connection everywhere instead.
require __DIR__ . '/db.php';

function getdb(): mysqli {
    global $conn;
    return $conn;
}

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'list_mine':       listMine();       break;
    case 'create':           createReport();  break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}

// Entity reports + missing-pet reports filed by the logged-in user, merged.
function listMine(): void {
    $db  = getdb();
    $uid = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        "SELECT id, entity_type, entity_name, reason, description, images, status, created_at
         FROM reports WHERE reporter_id = ? ORDER BY created_at DESC"
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $entityReports = [];
    while ($row = $res->fetch_assoc()) {
        $row['images'] = $row['images'] ? json_decode($row['images'], true) : [];
        $row['kind']   = 'entity';
        $entityReports[] = $row;
    }
    $stmt->close();

    // Adjust column list here if your `missing` table differs (see note above).
    $stmt2 = $db->prepare(
        "SELECT id, name, breed, place, date, contact, image, pet_id
         FROM missing WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt2->bind_param('i', $uid);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $missingReports = [];
    while ($row = $res2->fetch_assoc()) {
        $row['kind'] = 'missing';
        $missingReports[] = $row;
    }
    $stmt2->close();

    echo json_encode(['success' => true, 'reports' => $entityReports, 'missing' => $missingReports]);
    $db->close();
}

function createReport(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required.']);
        return;
    }

    $db  = getdb();
    $uid = (int)$_SESSION['user_id'];

    // The reporter just types a name freehand — it isn't matched against
    // an existing user/facility record, so entity_type/entity_id are no
    // longer required from the client. Stored as 'unspecified' / NULL.
    $entity_name = trim($_POST['entity_name'] ?? '');
    $reason      = trim($_POST['reason'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$entity_name || !$reason) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        $db->close();
        return;
    }

    $entity_type = 'unspecified';

    // ── EVIDENCE IMAGES ──────────────────────────────
    $imagePaths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $allowed  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize  = 5 * 1024 * 1024;
        $uploadDir = __DIR__ . '/../reports_evidence/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($_FILES['images']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if (!in_array($mime, $allowed) || $_FILES['images']['size'][$i] > $maxSize) continue;

            $ext      = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
            $filename = 'rep_' . time() . '_' . mt_rand(1000, 9999) . '.' . strtolower($ext);
            if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                $imagePaths[] = 'reports_evidence/' . $filename;
            }
        }
    }
    $imagesJson = json_encode($imagePaths);

    // entity_id is nullable / unmatched since there's no picker anymore.
    $stmt = $db->prepare(
        "INSERT INTO reports (reporter_id, entity_type, entity_id, entity_name, reason, description, images)
         VALUES (?, ?, NULL, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssss', $uid, $entity_type, $entity_name, $reason, $description, $imagesJson);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $db->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
    $db->close();
}