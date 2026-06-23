<?php
// para 2 sa favorites/saves
header('Content-Type: application/json');
require 'db.php';
session_start();

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$item_type = ($_GET['item_type'] ?? '') === 'product' ? 'product' : 'pet';

$stmt = $conn->prepare("SELECT item_id FROM saves WHERE user_id=? AND item_type=?");
$stmt->bind_param("is", $user_id, $item_type);
$stmt->execute();
$result = $stmt->get_result();

$saved = [];
while ($row = $result->fetch_assoc()) {
    $saved[] = $row['item_id'];
}
echo json_encode(['success' => true, 'saved' => $saved]);
?>