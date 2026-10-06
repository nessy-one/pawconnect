<?php
// php/admin_reports.php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$host = "localhost"; $user = "root"; $pass = "123456"; $db = "pawconnect";

function getdb(): mysqli {
    global $host, $user, $pass, $db;
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function listReports(): void {
    $db = getdb();
    $sql = "SELECT r.*, u.name AS reporter_name, u.username AS reporter_username
            FROM reports r
            JOIN users u ON u.id = r.reporter_id
            ORDER BY r.created_at DESC";
    $res = $db->query($sql);
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $row['images'] = $row['images'] ? json_decode($row['images'], true) : [];
        $rows[] = $row;
    }
    echo json_encode(['success' => true, 'reports' => $rows]);
    $db->close();
}

function setStatus(): void {
    $db = getdb();
    $data = json_decode(file_get_contents('php://input'), true);
    $id     = (int)($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!$id || !in_array($status, ['open', 'resolved', 'dismissed'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        $db->close();
        return;
    }

    $stmt = $db->prepare("UPDATE reports SET status = ?, resolved_at = IF(? <> 'open', NOW(), NULL) WHERE id = ?");
    $stmt->bind_param('ssi', $status, $status, $id);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows >= 0]);
    $stmt->close();
    $db->close();
}

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'list':       listReports();  break;
        case 'set_status': setStatus();    break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_reports.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(), // remove/trim this detail once things are stable in production
    ]);
}