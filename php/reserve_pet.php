<?php
//reserve_pet.php 
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to reserve a pet.']);
    exit;
}
$user_id = (int) $_SESSION['user_id'];

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php';

// ── EXPIRE OLD RESERVATIONS FIRST ─────────────────────────────────
// Flip any pet whose reserved_at + reserved_days window has passed back
// to available, before building the listing. (Shared helper — see db.php.)
expireOldReservations($conn);

$data = json_decode(file_get_contents('php://input'), true);

// $user_id    = intval($data['user_id']   ?? 0);
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

// Only allow the durations offered in the UI
if (!in_array($duration, [1, 3, 5, 7], true)) {
    $duration = 3;
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

// Insert reservation         --------------- dinelete q basta
// $stmt = $conn->prepare(
//     "INSERT INTO reservations (user_id, pet_id, name, contact, visit_date, duration_days, notes, created_at)
//      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
// );
// $stmt->bind_param("iisssis", $user_id, $pet_id, $name, $contact, $visit_date, $duration, $notes);


// if (!$stmt->execute()) {
//     echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
//     $stmt->close();
//     exit;
// }
// $stmt->close();

$conn->begin_transaction();
try {
    $s = $conn->prepare("UPDATE pet SET status='reserved', reserved_days=?, reserved_at=NOW()
                         WHERE id=? AND status='available'");
    $s->bind_param('ii', $duration, $pet_id);
    $s->execute();
    if ($s->affected_rows !== 1) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'This pet is no longer available.']);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO reservations (user_id, pet_id, name, contact, visit_date, duration_days, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("iisssis", $user_id, $pet_id, $name, $contact, $visit_date, $duration, $notes);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation confirmed.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}