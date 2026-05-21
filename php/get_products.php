<?php
header('Content-Type: application/json');

// --- DB connection (adjust to your config) ---
$host = "localhost";
$user = "root";
$pass = "123456";
$db   = "pawconnect";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// --- Rule-Based Filtering ---
$conditions = [];
$params     = [];

// Filter by category (matches your cat-chip buttons)
if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
    $conditions[] = "category = ?";
    $params[]     = $_GET['category'];
}

// Filter by search (name LIKE)
if (!empty($_GET['search'])) {
    $conditions[] = "name LIKE ?";
    $params[]     = '%' . $_GET['search'] . '%';
}

// Build query
$sql = "SELECT * FROM product";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);