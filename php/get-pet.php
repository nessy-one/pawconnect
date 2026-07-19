<?php
// para 2 sa pet
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "123456";
$db   = "pawconnect";
$charset = 'utf8mb4';
  
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// Validate id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'No pet ID provided.']);
    exit;
}

// Fetch pet
$stmt = $pdo->prepare('SELECT * FROM pet WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$pet = $stmt->fetch();

if (!$pet) {
    echo json_encode(['success' => false, 'message' => 'Pet not found.']);
    exit;
}

echo json_encode(['success' => true, 'pet' => $pet]);