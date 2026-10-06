<?php
// php/missing.php
session_start();
header('Content-Type: application/json');

// ── DB CONNECTION ──────────────────────────────────────
// FIX: this used to reimplement its own DB connection with hardcoded
// credentials instead of reusing db.php — meaning the credentials lived
// in two places that had to be kept in sync by hand. Use the shared
// connection everywhere instead.
require __DIR__ . '/db.php';

function getdb(): mysqli {
    global $conn;
    return $conn;
}

// ── ROUTE ────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':      listMissing(); break;
    case 'list_mine': listMine();    break;
    case 'report':    reportMissing(); break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}

// ── LIST (public — all missing pets) ──────────────────
function listMissing(): void {
    $db   = getdb();
    $type = $_GET['type'] ?? 'all';

    // if ($type === 'dog') {
    //     $stmt = $db->prepare("SELECT * FROM missing WHERE pet_id = 1 ORDER BY id DESC");
    // } elseif ($type === 'cat') {
    //     $stmt = $db->prepare("SELECT * FROM missing WHERE pet_id = 2 ORDER BY id DESC");
    // } else {
    //     $stmt = $db->prepare("SELECT * FROM missing ORDER BY id DESC");
    // }
    if ($type === 'dog' || $type === 'cat') {
        $stmt = $db->prepare(
            "SELECT m.* FROM missing m
            JOIN pet p ON p.id = m.pet_id
            WHERE p.type = ? ORDER BY m.id DESC"
        );
        $stmt->bind_param('s', $type);
    } else {
        $stmt = $db->prepare("SELECT * FROM missing ORDER BY id DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();  
    $rows   = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode($rows);
    $stmt->close();
    $db->close();
}

// ── LIST MINE (reports filed by the logged-in user) ───
function listMine(): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in.']);
        return;
    }

    $db  = getdb();
    $uid = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        "SELECT id, name, breed, place, date, contact, image, pet_id
         FROM missing WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = [
            'id'          => $row['id'],
            'pet_name'    => $row['name'],
            'report_type' => 'missing', 
            'location'    => $row['place'],
            'created_at'  => $row['date'], 
            'status'      => 'Open',       
            'description' => $row['breed'] ? ('Breed: ' . $row['breed']) : '',
            'images'      => $row['image'] ? [$row['image']] : [],
        ];
    }

    echo json_encode(['success' => true, 'reports' => $reports]);
    $stmt->close();
    $db->close();
}

// ── REPORT ───────────────────────────────────────────
function reportMissing(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required.']);
        return;
    }

    $db = getdb();

    $name    = trim($_POST['name']    ?? '');
    $breed   = trim($_POST['breed']   ?? '');
    $place   = trim($_POST['place']   ?? '');
    $date    = trim($_POST['date']    ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $pet_id  = (int)($_POST['pet_id'] ?? 0);
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // Basic validation
    if (!$name || !$place || !$date || !$contact || !$pet_id) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        $db->close();
        return;
    }

    // ── IMAGE UPLOAD ──────────────────────────────────
    $image_path = null;

    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['pet_image'];
        $allowed  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize  = 5 * 1024 * 1024; // 5 MB

        // Validate type
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.']);
            $db->close();
            return;
        }

        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Image must be under 5MB.']);
            $db->close();
            return;
        }

        $uploadDir = __DIR__ . '/../miss/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename   = 'pet_' . time() . '_' . mt_rand(1000, 9999) . '.' . strtolower($ext);
        $destPath   = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save image. Check folder permissions.']);
            $db->close();
            return;
        }

        $image_path = 'miss/' . $filename; // relative path stored in DB
    }

    $marks = trim($_POST['marks'] ?? '');

    // ── INSERT ────────────────────────────────────────
    $stmt = $db->prepare(
        "INSERT INTO missing (name, breed, place, date, contact, image, pet_id, user_id, marks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param('ssssssiis', $name, $breed, $place, $date, $contact, $image_path, $pet_id, $user_id, $marks);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $db->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    $stmt->close();
    $db->close();
}