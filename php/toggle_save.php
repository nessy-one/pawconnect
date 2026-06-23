<?php
header('Content-Type: application/json');
require 'db.php';
session_start();

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$item_id   = intval($data['item_id']  ?? 0);
$item_type = ($data['item_type'] ?? '') === 'product' ? 'product' : 'pet';
$action    = $data['action'] ?? '';

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Missing item_id.']);
    exit;
}

if ($action === 'save') {
    $stmt = $conn->prepare("INSERT IGNORE INTO saves (user_id, item_id, item_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $item_id, $item_type);
    $stmt->execute();
    echo json_encode(['success' => true]);
} elseif ($action === 'unsave') {
    $stmt = $conn->prepare("DELETE FROM saves WHERE user_id=? AND item_id=? AND item_type=?");
    $stmt->bind_param("iis", $user_id, $item_id, $item_type);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>