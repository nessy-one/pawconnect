<?php
// php/donate.php — handles submission from donate.html's "Donate Now" button
session_start();
header('Content-Type: application/json');
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to donate.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$d = json_decode(file_get_contents('php://input'), true);

$firstName = trim($d['first_name'] ?? '');
$lastName  = trim($d['last_name'] ?? '');
$email     = trim($d['email'] ?? '');
$mobile    = trim($d['mobile'] ?? '');
$purpose   = trim($d['purpose'] ?? 'General Fund');
$amount    = (float) ($d['amount'] ?? 0);
$recurring = !empty($d['recurring']) ? 1 : 0;

if ($firstName === '' || $lastName === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter your first and last name.']);
    exit;
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid donation amount.']);
    exit;
}

$allowedPurposes = ['Shelter Animals', 'Medical Care', 'Food & Supplies', 'Rescue Operations', 'General Fund'];
if (!in_array($purpose, $allowedPurposes, true)) {
    $purpose = 'General Fund';
}

$stmt = mysqli_prepare($conn, "
    INSERT INTO user_donations (user_id, first_name, last_name, email, mobile, purpose, amount, recurring, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'completed')
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, "isssssdi", $userId, $firstName, $lastName, $email, $mobile, $purpose, $amount, $recurring);
mysqli_stmt_execute($stmt);

echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);