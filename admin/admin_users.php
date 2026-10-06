<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php';
require __DIR__ . '/../php/profile_photo.php';   

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list': {
            $result = mysqli_query(
                $conn,
                "SELECT id, name, username, email, mobile, gender, role, status, profile_image, created_at, last_login
                FROM users
                WHERE role IS NULL OR role != 'admin'
                ORDER BY created_at DESC, id DESC"
            );
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
                exit;
            }
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
            echo json_encode(['success' => true, 'users' => $rows]);
            break;
        }

        // Body: { id, status: 'active' | 'suspended' }
        case 'set_status': {
            $d      = json_decode(file_get_contents('php://input'), true);
            $id     = (int) ($d['id'] ?? 0);
            $status = $d['status'] ?? '';

            if (!$id || !in_array($status, ['active', 'suspended'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid id or status.']);
                exit;
            }

            $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ? AND (role IS NULL OR role != 'admin')");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) === 0) {
                // Not necessarily an error (status might already match), but
                // surface it if the id genuinely doesn't exist.
                $check = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ?");
                mysqli_stmt_bind_param($check, "i", $id);
                mysqli_stmt_execute($check);
                if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
                    echo json_encode(['success' => false, 'message' => 'User not found.']);
                    exit;
                }
            }

            echo json_encode(['success' => true]);
            break;
        }

        case 'upload_photo': {
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Missing user id.']);
                exit;
            }
            if (empty($_FILES['photo'])) {
                echo json_encode(['success' => false, 'message' => 'No photo uploaded.']);
                exit;
            }

            $result = processProfilePhotoUpload($id, $_FILES['photo']);
            if (!$result['success']) {
                echo json_encode($result);
                exit;
            }

            $stmt = mysqli_prepare($conn, "UPDATE users SET profile_image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $result['photo'], $id);
            mysqli_stmt_execute($stmt);

            echo json_encode(['success' => true, 'profile_image' => $result['photo']]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('admin_users.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(), // remove/trim this detail once things are stable in production
    ]);
}