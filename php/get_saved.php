<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'root', '', 'your_db');
$user_id = intval($_GET['user_id']);

$result = $conn->query(
    "SELECT pet_id FROM saved_pets WHERE user_id=$user_id"
);

$saved = [];
while ($row = $result->fetch_assoc()) {
    $saved[] = $row['pet_id'];
}

echo json_encode(['success' => true, 'saved' => $saved]);
?>