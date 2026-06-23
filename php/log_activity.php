<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "123456";
$db   = "pawconnect";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true);
$userId    = isset($body['user_id'])    ? (int)$body['user_id']          : 0;
$itemType  = isset($body['item_type'])  ? trim($body['item_type'])        : '';
$searchTerm = isset($body['search_term']) ? trim($body['search_term'])   : '';

if (!$userId || !in_array($itemType, ['pet','product']) || $searchTerm === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO user_activity
                       (user_id, item_type, item_id, search_term, action)
                       VALUES (?, ?, 0, ?, 'search')");
$stmt->execute([$userId, $itemType, $searchTerm]);

echo json_encode(['success' => true]);