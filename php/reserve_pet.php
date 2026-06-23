<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id    = intval($data['user_id']   ?? 0);
$pet_id     = intval($data['pet_id']    ?? 0);
$name       = trim($data['name']        ?? '');
$contact    = trim($data['contact']     ?? '');
$visit_date = trim($data['visit_date']  ?? '');
$duration   = intval($data['duration']  ?? 3);
$notes      = trim($data['notes']       ?? '');

// Validation
if (!$pet_id || !$name || !$contact || !$visit_date) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Check if pet is still available
$stmt = $conn->prepare("SELECT status FROM pet WHERE id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Pet not found.']);
    exit;
}
if ($result['status'] !== 'available') {
    echo json_encode(['success' => false, 'message' => 'This pet is no longer available.']);
    exit;
}

// Insert reservation
$stmt = $conn->prepare(
    "INSERT INTO reservations (user_id, pet_id, name, contact, visit_date, duration_days, notes, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param("iisssis", $user_id, $pet_id, $name, $contact, $visit_date, $duration, $notes);


if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();

// Update pet status to 'reserved'
$stmt = $conn->prepare("UPDATE pet SET status = 'reserved', reserved_days = ? WHERE id = ?");
$stmt->bind_param("ii", $duration, $pet_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Reservation confirmed.']);