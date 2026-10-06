<?php
/*admin_announcement.php*/

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
        error_log('admin_announcement.php FATAL: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal server error: ' . $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')',
        ]);
    }
});

$dbPath = __DIR__ . '/../php/db.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Server misconfiguration: db.php not found at $dbPath — fix the require path in admin_announcement.php"]);
    exit;
}
require_once $dbPath; 

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: db.php did not set up a valid $conn (mysqli instance)']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userId  = (int) $_SESSION['user_id'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

function prep(mysqli $conn, string $sql): mysqli_stmt {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Query prepare failed: ' . $conn->error);
    }
    return $stmt;
}

/** Maps the announcement "type" label to the notification `type` enum. */
function mapAnnouncementType(string $type): string {
    $map = [
        'Vaccination Drive'        => 'vaccination',
        'Rabies Awareness Seminar' => 'vaccination',
        'Free Spay/Neuter'         => 'event',
        'Pet Adoption Fair'        => 'event',
        'Free Veterinary Check-up' => 'event',
        'Medical Adoption Case'    => 'community',
        'Animal Rescue Event'      => 'community',
        'Pet Blessing'             => 'community',
    ];
    return $map[$type] ?? 'general';
}

function buildNotificationMessage(string $title, string $facility, ?string $desc, ?string $location): string {
    $msg = $desc ?: ($title . ' — hosted by ' . $facility);
    if (!empty($location)) $msg .= ' Location: ' . $location . '.';
    return $msg;
}

/** Inserts or refreshes the notifications row tied to an announcement. */
function publishNotification(mysqli $conn, int $announcementId, string $title, string $type, string $facility, ?string $eventDate, ?string $location, ?string $desc): void {
    $notifType = mapAnnouncementType($type);
    $message   = buildNotificationMessage($title, $facility, $desc, $location);

    $check = prep($conn, "SELECT notification_id FROM announcements WHERE id = ?");
    $check->bind_param('i', $announcementId);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    $check->close();
    $existingNotifId = $row['notification_id'] ?? null;

    if ($existingNotifId) {
        $stmt = prep($conn, "
            UPDATE notifications
            SET title = ?, message = ?, type = ?, event_date = ?, location = ?
            WHERE id = ?
        ");
        $stmt->bind_param('sssssi', $title, $message, $notifType, $eventDate, $location, $existingNotifId);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $stmt = prep($conn, "
        INSERT INTO notifications (title, message, type, target, event_date, location, source_announcement_id)
        VALUES (?, ?, ?, 'all', ?, ?, ?)
    ");
    $stmt->bind_param('sssssi', $title, $message, $notifType, $eventDate, $location, $announcementId);
    $stmt->execute();
    $notifId = $stmt->insert_id;
    $stmt->close();

    $upd = prep($conn, "UPDATE announcements SET notification_id = ? WHERE id = ?");
    $upd->bind_param('ii', $notifId, $announcementId);
    $upd->execute();
    $upd->close();
}

try {
    switch ($action) {

        case 'list': {
            $mine = isset($_GET['mine']) && $_GET['mine'] === '1';
            if (!$isAdmin && !$mine) {
                echo json_encode(['success' => false, 'message' => 'Not authorized']);
                break;
            }
            if ($mine) {
                $stmt = prep($conn, "SELECT * FROM announcements WHERE submitted_by = ? ORDER BY created_at DESC");
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query("
                    SELECT a.*, u.username AS submitted_by_name
                    FROM announcements a
                    LEFT JOIN users u ON u.id = a.submitted_by
                    ORDER BY a.created_at DESC
                ");
                if ($result === false) {
                    throw new RuntimeException('Query failed: ' . $conn->error);
                }
            }
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'announcements' => $rows]);
            break;
        }

        case 'create': {
            $title    = trim($_POST['title'] ?? '');
            $type     = trim($_POST['type'] ?? '');
            $facility = trim($_POST['facility_name'] ?? '');
            $date     = $_POST['event_date'] ?? null;
            $location = trim($_POST['location'] ?? '');
            $desc     = trim($_POST['description'] ?? '');

            if ($title === '' || $type === '' || !$date) {
                echo json_encode(['success' => false, 'message' => 'Title, type, and event date are required.']);
                break;
            }

            $status = ($isAdmin && isset($_POST['status'])) ? strtolower($_POST['status']) : 'pending';
            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) $status = 'pending';

            $stmt = prep($conn, "
                INSERT INTO announcements (title, type, facility_name, event_date, location, description, status, submitted_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssssssi', $title, $type, $facility, $date, $location, $desc, $status, $userId);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();

            if ($status === 'approved') {
                publishNotification($conn, $newId, $title, $type, $facility, $date, $location, $desc);
            }

            echo json_encode(['success' => true, 'id' => $newId]);
            break;
        }

        case 'update': {
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id']); break; }

            $stmt = prep($conn, "SELECT * FROM announcements WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$existing) { echo json_encode(['success' => false, 'message' => 'Not found']); break; }

            if (!$isAdmin && (int) $existing['submitted_by'] !== $userId) {
                echo json_encode(['success' => false, 'message' => 'Not authorized']);
                break;
            }
            if (!$isAdmin && $existing['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Approved announcements can only be edited by an admin.']);
                break;
            }

            $title    = trim($_POST['title'] ?? $existing['title']);
            $type     = trim($_POST['type'] ?? $existing['type']);
            $facility = trim($_POST['facility_name'] ?? $existing['facility_name']);
            $date     = $_POST['event_date'] ?? $existing['event_date'];
            $location = trim($_POST['location'] ?? $existing['location']);
            $desc     = trim($_POST['description'] ?? $existing['description']);
            $status   = ($isAdmin && isset($_POST['status'])) ? strtolower($_POST['status']) : $existing['status'];

            $stmt = prep($conn, "
                UPDATE announcements
                SET title = ?, type = ?, facility_name = ?, event_date = ?,
                    location = ?, description = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param('sssssssi', $title, $type, $facility, $date, $location, $desc, $status, $id);
            $stmt->execute();
            $stmt->close();

            if ($status === 'approved') {
                publishNotification($conn, $id, $title, $type, $facility, $date, $location, $desc);
            }

            echo json_encode(['success' => true]);
            break;
        }

        case 'set_status': {
            if (!$isAdmin) { echo json_encode(['success' => false, 'message' => 'Admins only']); break; }
            $id     = (int) ($_POST['id'] ?? 0);
            $status = strtolower($_POST['status'] ?? '');
            if (!$id || !in_array($status, ['approved', 'rejected'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                break;
            }

            $stmt = prep($conn, "SELECT * FROM announcements WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $a = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$a) { echo json_encode(['success' => false, 'message' => 'Not found']); break; }

            $upd = prep($conn, "
                UPDATE announcements SET status = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $upd->bind_param('sii', $status, $userId, $id);
            $upd->execute();
            $upd->close();

            if ($status === 'approved') {
                publishNotification($conn, $id, $a['title'], $a['type'], $a['facility_name'], $a['event_date'], $a['location'], $a['description']);
            } elseif (!empty($a['notification_id'])) {
                $notifId = (int) $a['notification_id'];
                $del = prep($conn, "DELETE FROM notifications WHERE id = ?");
                $del->bind_param('i', $notifId);
                $del->execute();
                $del->close();

                $clear = prep($conn, "UPDATE announcements SET notification_id = NULL WHERE id = ?");
                $clear->bind_param('i', $id);
                $clear->execute();
                $clear->close();
            }

            echo json_encode(['success' => true]);
            break;
        }

        case 'delete': {
            if (!$isAdmin) { echo json_encode(['success' => false, 'message' => 'Admins only']); break; }
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Missing id']); break; }

            $stmt = prep($conn, "SELECT notification_id FROM announcements WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $notifId = $stmt->get_result()->fetch_assoc()['notification_id'] ?? null;
            $stmt->close();

            $del = prep($conn, "DELETE FROM announcements WHERE id = ?");
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();

            if ($notifId) {
                $delN = prep($conn, "DELETE FROM notifications WHERE id = ?");
                $delN->bind_param('i', $notifId);
                $delN->execute();
                $delN->close();
            }
            echo json_encode(['success' => true]);
            break;
        }

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (\Throwable $e) {
    error_log('admin_announcement.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}