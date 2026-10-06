<?php
/**
 * PawConnect - Notifications API (mysqli version)
 * Actions:
 *   GET  ?action=get           -> list notifications for the logged-in user
 *   GET  ?action=unread_count  -> { success, count }
 *   POST action=mark_read      -> body: notification_id
 *   POST action=mark_all_read  -> marks every visible notification as read
 *
 * This is the user-facing counterpart to admin_announcement.php: once an
 * admin approves an announcement, a row lands in `notifications` and shows
 * up here for every user (target = 'all').
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php'; // must expose a mysqli instance in $conn

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        case 'get': {
            $uidStr = (string) $userId;
            $stmt = $conn->prepare("
                SELECT n.id, n.title, n.message, n.type, n.event_date, n.location, n.created_at,
                       CASE WHEN r.id IS NULL THEN 0 ELSE 1 END AS is_read
                FROM notifications n
                LEFT JOIN notification_reads r
                       ON r.notification_id = n.id AND r.user_id = ?
                WHERE n.target = 'all' OR n.target = ?
                ORDER BY n.created_at DESC
                LIMIT 100
            ");
            $stmt->bind_param('is', $userId, $uidStr);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($rows as &$row) {
                $row['is_read'] = (bool) $row['is_read'];
            }

            echo json_encode(['success' => true, 'notifications' => $rows]);
            break;
        }

        case 'unread_count': {
            $uidStr = (string) $userId;
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS cnt
                FROM notifications n
                LEFT JOIN notification_reads r
                       ON r.notification_id = n.id AND r.user_id = ?
                WHERE (n.target = 'all' OR n.target = ?)
                  AND r.id IS NULL
            ");
            $stmt->bind_param('is', $userId, $uidStr);
            $stmt->execute();
            $count = (int) $stmt->get_result()->fetch_assoc()['cnt'];
            $stmt->close();

            echo json_encode(['success' => true, 'count' => $count]);
            break;
        }

        case 'mark_read': {
            $notificationId = (int) ($_POST['notification_id'] ?? 0);
            if (!$notificationId) {
                echo json_encode(['success' => false, 'message' => 'Missing notification_id']);
                break;
            }

            $stmt = $conn->prepare("
                INSERT IGNORE INTO notification_reads (notification_id, user_id)
                VALUES (?, ?)
            ");
            $stmt->bind_param('ii', $notificationId, $userId);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true]);
            break;
        }

        case 'mark_all_read': {
            $uidStr = (string) $userId;
            $stmt = $conn->prepare("
                SELECT n.id
                FROM notifications n
                LEFT JOIN notification_reads r
                       ON r.notification_id = n.id AND r.user_id = ?
                WHERE (n.target = 'all' OR n.target = ?)
                  AND r.id IS NULL
            ");
            $stmt->bind_param('is', $userId, $uidStr);
            $stmt->execute();
            $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
            $stmt->close();

            if ($ids) {
                $insert = $conn->prepare("
                    INSERT IGNORE INTO notification_reads (notification_id, user_id)
                    VALUES (?, ?)
                ");
                foreach ($ids as $nid) {
                    $nid = (int) $nid;
                    $insert->bind_param('ii', $nid, $userId);
                    $insert->execute();
                }
                $insert->close();
            }

            echo json_encode(['success' => true]);
            break;
        }

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}