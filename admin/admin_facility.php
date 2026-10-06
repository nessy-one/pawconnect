<?php
// admin_facility.php
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
        error_log('admin_facility.php FATAL: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error: ' . $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')',
        ]);
    }
});

$dbPath = __DIR__ . '/../php/db.php'; 
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Server misconfiguration: db.php not found at $dbPath — adjust the require path in admin_facility.php"]);
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

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list': {
            $result = mysqli_query($conn, "SELECT * FROM facility ORDER BY submitted_at DESC");
            if (!$result) {
                throw new RuntimeException(mysqli_error($conn));
            }
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
            echo json_encode(['success' => true, 'facilities' => $rows]);
            break;
        }

        case 'create': {
            $d = json_decode(file_get_contents('php://input'), true);
            if (empty($d['name'])) {
                echo json_encode(['success' => false, 'message' => 'Facility name is required.']);
                break;
            }
            $stmt = mysqli_prepare($conn,
                "INSERT INTO facility (name, type, address, contact, latitude, longitude, opening_hours, description, status, submitted_by, submitted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) throw new RuntimeException(mysqli_error($conn));

            $status = $d['status'] ?? 'active';
            $lat = $d['latitude'] ?? 0;
            $lng = $d['longitude'] ?? 0;
            $submittedBy = $_SESSION['admin_name'] ?? 'Admin';
            mysqli_stmt_bind_param($stmt, "ssssddssss",
                $d['name'], $d['type'], $d['address'], $d['contact'],
                $lat, $lng, $d['opening_hours'], $d['description'],
                $status, $submittedBy
            );
            mysqli_stmt_execute($stmt);
            echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
            break;
        }

        case 'update': {
            $d = json_decode(file_get_contents('php://input'), true);
            if (empty($d['id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing facility id.']);
                break;
            }
            $stmt = mysqli_prepare($conn,
                "UPDATE facility SET name=?, type=?, address=?, contact=?, latitude=?, longitude=?, opening_hours=?, description=?, status=? WHERE id=?");
            if (!$stmt) throw new RuntimeException(mysqli_error($conn));

            mysqli_stmt_bind_param($stmt, "ssssddsssi",
                $d['name'], $d['type'], $d['address'], $d['contact'],
                $d['latitude'], $d['longitude'], $d['opening_hours'], $d['description'],
                $d['status'], $d['id']
            );
            mysqli_stmt_execute($stmt);
            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            $d = json_decode(file_get_contents('php://input'), true);
            if (empty($d['id'])) {
                echo json_encode(['success' => false, 'message' => 'Missing facility id.']);
                break;
            }
            $stmt = mysqli_prepare($conn, "DELETE FROM facility WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $d['id']);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) === 0 && mysqli_error($conn)) {
                echo json_encode(['success' => false, 'message' => 'Could not delete — this facility may still have pets or products linked to it.']);
                break;
            }
            echo json_encode(['success' => true]);
            break;
        }

        case 'set_status': { 
            $d = json_decode(file_get_contents('php://input'), true);
            if (empty($d['id']) || empty($d['status'])) {
                echo json_encode(['success' => false, 'message' => 'Missing id or status.']);
                break;
            }
            if (!in_array($d['status'], ['active', 'pending', 'rejected', 'inactive'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status.']);
                break;
            }
            $stmt = mysqli_prepare($conn, "UPDATE facility SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $d['status'], $d['id']);
            mysqli_stmt_execute($stmt);
            echo json_encode(['success' => true]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_facility.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}