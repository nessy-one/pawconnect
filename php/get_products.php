<?php
header('Content-Type: application/json');

// --- DB connection (adjust to your config) ---
// $host = "localhost";
// $user = "root";
// $pass = "123456";
// $db   = "pawconnect";

require __DIR__ . '/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// --- Rule-Based Filtering ---
$conditions = [];
$params     = [];

if (!empty($_GET['id'])) {
    $conditions[] = "id = ?";
    $params[] = (int) $_GET['id'];
}

// ── TYPE filter (Toys / Accessories / Apparel / Food / Grooming / Others) ──
// Comes in as a comma-separated list from the checkbox panel, e.g. "toy,food".
// category is a single value per row, so a plain IN (...) covers "match any".
if (!empty($_GET['category'])) {
    $cats = array_filter(array_map('trim', explode(',', $_GET['category'])));
    if ($cats) {
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        $conditions[] = "category IN ($placeholders)";
        foreach ($cats as $c) { $params[] = $c; }
    }
}

// ── SPECIES filter (Cat / Dog) ──────────────────────────────────────
// pet_type stores "cat", "dog", or "cat,dog" for items that suit both.
// Also comes in as a comma-separated list, e.g. "cat,dog" (match either).
if (!empty($_GET['species'])) {
    $specs = array_filter(array_map('trim', explode(',', $_GET['species'])));
    if ($specs) {
        $orParts = [];
        foreach ($specs as $s) {
            $orParts[] = "FIND_IN_SET(?, pet_type)";
            $params[]  = $s;
        }
        $conditions[] = '(' . implode(' OR ', $orParts) . ')';
    }
}

// ── SEARCH filter — checks name, category, AND description ───────
if (!empty($_GET['search'])) {
    $t = '%' . $_GET['search'] . '%';
    $conditions[] = "(name LIKE ? OR category LIKE ? OR description LIKE ?)";
    array_push($params, $t, $t, $t);
}

// ── BUILD & RUN QUERY ────────────────────────────────────────────
$sql = "SELECT * FROM product";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);