<?php
// admin/admin_feedback.php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list': {
            $sql = "SELECT f.id, f.user_id, f.rating, f.comment, f.status, f.created_at,
                           f.target_type, f.facility_id,
                           u.name AS user_name, u.username,
                           TRIM(fac.name) AS facility_name
                    FROM feedback f
                    LEFT JOIN users u ON u.id = f.user_id
                    LEFT JOIN facility fac ON fac.id = f.facility_id
                    ORDER BY f.created_at DESC";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            echo json_encode(['success' => true, 'feedback' => mysqli_fetch_all($result, MYSQLI_ASSOC)]);
            break;
        }

        case 'set_status': {
            $d      = json_decode(file_get_contents('php://input'), true);
            $id     = (int) ($d['id'] ?? 0);
            $status = $d['status'] ?? '';
            if (!$id || !in_array($status, ['new', 'reviewed', 'hidden'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid request.']);
                exit;
            }
            $stmt = mysqli_prepare($conn, "UPDATE feedback SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);
            echo json_encode(['success' => true]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_feedback.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}