<?php
// ── Catch ALL fatal errors and return them as JSON (never as HTML) ──
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

// Register a shutdown handler so even fatal errors come back as JSON
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal: ' . $e['message'] . ' in ' . basename($e['file']) . ':' . $e['line']
        ]);
    }
});

// Set error handler so warnings/notices also return JSON
set_error_handler(function ($severity, $msg, $file, $line) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $msg . ' in ' . basename($file) . ':' . $line
    ]);
    exit;
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Helper: discard buffered junk, send clean JSON, exit
function jsonOut(array $data): void {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

session_start();

$host    = "localhost";
$user    = "root";
$pass    = "123456";
$db      = "pawconnect";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    jsonOut(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
}

// ── AUTH CHECK ───────────────────────────────────────────────
// Uncomment in production:
// if (empty($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Not logged in.']);
//     exit;
// }
// $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

$username = $_SESSION['user'] ?? '';
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$userRow = $stmt->fetch();
$userId = $userRow ? (int)$userRow['id'] : 0;

if (!$userId) {
    jsonOut(['success' => false, 'message' => 'Not logged in.']);
}

// ── ENSURE mobile & gender COLUMNS ARE CORRECT TYPE ─────────
try {
    $colInfo = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $colMap  = [];
    foreach ($colInfo as $c) {
        $colMap[strtolower($c['Field'])] = strtolower($c['Type']);
    }

    if (!isset($colMap['mobile'])) {
        $pdo->exec("ALTER TABLE users ADD COLUMN mobile VARCHAR(30) DEFAULT NULL");
    } elseif (strpos($colMap['mobile'], 'varchar') === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN mobile VARCHAR(30) DEFAULT NULL");
    }

    if (!isset($colMap['gender'])) {
        $pdo->exec("ALTER TABLE users ADD COLUMN gender VARCHAR(20) DEFAULT NULL");
    } elseif (strpos($colMap['gender'], 'varchar') === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN gender VARCHAR(20) DEFAULT NULL");
    }
} catch (PDOException $e) {
    jsonOut(['success' => false, 'message' => 'Schema check failed: ' . $e->getMessage()]);
}

// ── UPLOAD DIR ───────────────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/profile_photos/';

// Absolute URL so the browser resolves it from any page location
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$uploadUrl = $scheme . '://' . $host . $scriptDir . '/uploads/profile_photos/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── ROUTER ───────────────────────────────────────────────────
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {

    // ── GET PROFILE ──────────────────────────────────────────
    case 'get':
        $stmt = $pdo->prepare(
            "SELECT id, name, username, email, role,
                    COALESCE(mobile, '') AS mobile,
                    COALESCE(gender, '') AS gender
             FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            jsonOut(['success' => false, 'message' => 'User not found.']);
        }

        $photoPath = $uploadDir . $userId . '.jpg';
        $photoUrl  = file_exists($photoPath)
            ? $uploadUrl . $userId . '.jpg?v=' . filemtime($photoPath)
            : null;

        jsonOut([
            'success' => true,
            'user'    => [
                'id'       => $row['id'],
                'name'     => $row['name'],
                'username' => $row['username'],
                'email'    => $row['email'],
                'role'     => $row['role'],
                'mobile'   => $row['mobile'],
                'gender'   => $row['gender'],
                'photo'    => $photoUrl,
            ]
        ]);

    // ── UPDATE PROFILE ───────────────────────────────────────
    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $name     = trim($data['name']     ?? '');
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email']    ?? '');
        $mobile   = trim($data['mobile']   ?? '');
        $gender   = trim($data['gender']   ?? '');

        if (!$name || !$username || !$email) {
            jsonOut(['success' => false, 'message' => 'Name, username, and email are required.']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonOut(['success' => false, 'message' => 'Invalid email address.']);
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            jsonOut(['success' => false, 'message' => 'Username is already taken.']);
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            jsonOut(['success' => false, 'message' => 'Email is already in use.']);
        }

        $stmt = $pdo->prepare(
            "UPDATE users SET name=?, username=?, email=?, mobile=?, gender=? WHERE id=?"
        );
        $stmt->execute([$name, $username, $email, $mobile, $gender, $userId]);

        jsonOut(['success' => true, 'message' => 'Profile updated successfully.']);

    // ── UPLOAD PHOTO ─────────────────────────────────────────
    case 'upload_photo':
        if (!extension_loaded('gd')) {
            jsonOut(['success' => false, 'message' => 'GD image library not enabled. In XAMPP: open php.ini, remove the semicolon from ";extension=gd", then restart Apache.']);
        }
        if (empty($_FILES['photo'])) {
            jsonOut(['success' => false, 'message' => 'No file uploaded.']);
        }

        $file    = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            jsonOut(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($file['tmp_name']);

        if (!in_array($mimeReal, $allowed)) {
            jsonOut(['success' => false, 'message' => 'Only JPEG, PNG, GIF, WEBP allowed.']);
        }

        if ($file['size'] > $maxSize) {
            jsonOut(['success' => false, 'message' => 'File too large (max 5 MB).']);
        }

        $dest = $uploadDir . $userId . '.jpg';

        $src = null;
        switch ($mimeReal) {
            case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
            case 'image/png':  $src = imagecreatefrompng($file['tmp_name']);  break;
            case 'image/gif':  $src = imagecreatefromgif($file['tmp_name']);  break;
            case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
        }

        if (!$src) {
            jsonOut(['success' => false, 'message' => 'Could not process image.']);
        }

        imagejpeg($src, $dest, 90);
        imagedestroy($src);

        jsonOut(['success' => true, 'photo' => $uploadUrl . $userId . '.jpg?v=' . time(), 'message' => 'Photo updated.']);

    // ── REMOVE PHOTO ─────────────────────────────────────────
    case 'remove_photo':
        $dest = $uploadDir . $userId . '.jpg';
        if (file_exists($dest)) unlink($dest);
        jsonOut(['success' => true, 'message' => 'Photo removed.']);

    default:
        http_response_code(400);
        jsonOut(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
}