<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'root', '', 'your_db');
$data = json_decode(file_get_contents('php://input'), true);

$user_id = intval($data['user_id']);
$pet_id  = $conn->real_escape_string($data['pet_id']);
$action  = $data['action'];

if ($action === 'save') {
    $conn->query("INSERT IGNORE INTO saved_pets (user_id, pet_id) VALUES ($user_id, '$pet_id')");
} else {
    $conn->query("DELETE FROM saved_pets WHERE user_id=$user_id AND pet_id='$pet_id'");
}

echo json_encode(['success' => true]);
?>