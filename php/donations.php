<?php
// php/donations.php — lets a logged-in user see their own donation history
session_start();
header('Content-Type: application/json');
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list': {
        $stmt = mysqli_prepare($conn, "
            SELECT id, purpose, amount, recurring, status, created_at
            FROM user_donations
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $rows = [];
        $total = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $row['recurring'] = (bool) $row['recurring'];
            if ($row['status'] === 'completed') $total += (float) $row['amount'];
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'donations' => $rows, 'total_donated' => $total]);
        break;
    }

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}