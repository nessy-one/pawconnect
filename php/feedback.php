<?php
// php/feedback.php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to leave feedback.']);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // Active facilities from the `facility` table, for the facility picker.
        case 'facilities': {
            $res  = $conn->query("SELECT id, TRIM(name) AS name, type FROM facility WHERE status = 'active' ORDER BY name");
            echo json_encode(['success' => true, 'facilities' => $res->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        // Feedback this user has submitted (for the "My Feedback" tab).
        case 'list_mine': {
            $stmt = $conn->prepare("
                SELECT f.id, f.rating, f.comment, f.created_at, f.target_type,
                       TRIM(fac.name) AS facility_name
                FROM feedback f
                LEFT JOIN facility fac ON fac.id = f.facility_id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            echo json_encode(['success' => true, 'feedback' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        case 'create': {
            $data       = json_decode(file_get_contents('php://input'), true);
            $rating     = (int) ($data['rating'] ?? 0);
            $comment    = trim($data['comment'] ?? '');
            $targetType = ($data['target_type'] ?? 'app') === 'facility' ? 'facility' : 'app';
            $facilityId = null;

            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Please choose a rating from 1 to 5.']);
                exit;
            }

            // Facility feedback must point at a real, active facility.
            if ($targetType === 'facility') {
                $facilityId = !empty($data['facility_id']) ? (int) $data['facility_id'] : 0;
                $fchk = $conn->prepare("SELECT id FROM facility WHERE id = ? AND status = 'active'");
                $fchk->bind_param('i', $facilityId);
                $fchk->execute();
                if (!$fchk->get_result()->fetch_assoc()) {
                    echo json_encode(['success' => false, 'message' => 'Please choose a facility.']);
                    exit;
                }
            }

            $stmt = $conn->prepare(
                "INSERT INTO feedback (user_id, rating, comment, target_type, facility_id)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iissi', $userId, $rating, $comment, $targetType, $facilityId);

            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Could not save feedback.']);
                exit;
            }

            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
            break;
        }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (\Throwable $e) {
    error_log('feedback.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}