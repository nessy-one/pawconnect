<?php
ob_start();
error_reporting(0);
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
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$conditions = [];
$params     = [];

// ── TYPE filter ──────────────────────────────────────────────────
$filterType = '';
if (!empty($_GET['type']) && in_array($_GET['type'], ['cat', 'dog'])) {
    $conditions[] = "type = ?";
    $params[]     = $_GET['type'];
    $filterType   = $_GET['type'];
}

// ── SEARCH filter ────────────────────────────────────────────────
if (!empty($_GET['search'])) {
    $conditions[] = "name LIKE ?";
    $params[]     = '%' . $_GET['search'] . '%';
}

// ── BREED filter ─────────────────────────────────────────────────
$allowedBreeds = ['native', 'mixed', 'purebred'];
$filterBreeds  = isset($_GET['breed']) ? (array)$_GET['breed'] : [];
$filterBreeds  = array_filter($filterBreeds, fn($b) => in_array($b, $allowedBreeds));

if (!empty($filterBreeds)) {
    $placeholders = implode(',', array_fill(0, count($filterBreeds), '?'));
    $conditions[] = "breed IN ($placeholders)";
    foreach ($filterBreeds as $b) $params[] = $b;
}

// ── AGE filter ───────────────────────────────────────────────────
$allowedAges = ['0-1', '1-3', '3-7', '7+'];
$filterAges  = isset($_GET['age']) ? (array)$_GET['age'] : [];
$filterAges  = array_filter($filterAges, fn($a) => in_array($a, $allowedAges));

if (!empty($filterAges)) {
    $ageClauses = [];
    foreach ($filterAges as $range) {
        switch ($range) {
            case '0-1': $ageClauses[] = "(age >= 0 AND age < 1)"; break;
            case '1-3': $ageClauses[] = "(age >= 1 AND age < 3)"; break;
            case '3-7': $ageClauses[] = "(age >= 3 AND age < 7)"; break;
            case '7+':  $ageClauses[] = "(age >= 7)";             break;
        }
    }
    $conditions[] = '(' . implode(' OR ', $ageClauses) . ')';
}

// ── PRIORITY filter ──────────────────────────────────────────────
$allowedPriorities = ['urgent', 'normal'];
$filterPriorities  = isset($_GET['priority']) ? (array)$_GET['priority'] : [];
$filterPriorities  = array_filter($filterPriorities, fn($p) => in_array($p, $allowedPriorities));

if (!empty($filterPriorities)) {
    $placeholders = implode(',', array_fill(0, count($filterPriorities), '?'));
    $conditions[] = "urgency IN ($placeholders)";
    foreach ($filterPriorities as $p) $params[] = $p;
}

// ── LOCATION (optional) ──────────────────────────────────────────
// User optionally sends their city e.g. ?location=Angeles+City
$userLocation = !empty($_GET['location']) ? strtolower(trim($_GET['location'])) : '';

// ── BUILD & RUN QUERY ────────────────────────────────────────────
$sql = "SELECT * FROM pet";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pets = $stmt->fetchAll();

// ── WEIGHTED SCORING ─────────────────────────────────────────────
// Weights
const W_URGENCY       = 0.5;
const W_COMPATIBILITY = 0.3;
const W_PROXIMITY     = 0.2;

// Count how many compatibility filters the user actually set
// (so we can normalize the compatibility score fairly)
$totalCompatFilters = 0;
if ($filterType)                    $totalCompatFilters++;
if (!empty($filterBreeds))          $totalCompatFilters++;
if (!empty($filterAges))            $totalCompatFilters++;

foreach ($pets as &$pet) {

    // ── 1. URGENCY SCORE (0.0 – 1.0) ────────────────────────────
    $urgencyScore = match(strtolower($pet['urgency'] ?? '')) {
        'urgent' => 1.0,
        'normal' => 0.3,
        default  => 0.1,
    };

    // ── 2. COMPATIBILITY SCORE (0.0 – 1.0) ──────────────────────
    // How well the pet matches the user's stated preferences.
    // If the user set NO filters, every pet is equally compatible (1.0).
    if ($totalCompatFilters === 0) {
        $compatScore = 1.0;
    } else {
        $matchCount = 0;

        // Type match
        if ($filterType && strtolower($pet['type']) === strtolower($filterType)) {
            $matchCount++;
        }

        // Breed match
        if (!empty($filterBreeds) && in_array(strtolower($pet['breed']), array_map('strtolower', $filterBreeds))) {
            $matchCount++;
        }

        // Age range match
        if (!empty($filterAges)) {
            $petAge = (float)$pet['age'];
            foreach ($filterAges as $range) {
                $hit = match($range) {
                    '0-1' => $petAge >= 0 && $petAge < 1,
                    '1-3' => $petAge >= 1 && $petAge < 3,
                    '3-7' => $petAge >= 3 && $petAge < 7,
                    '7+'  => $petAge >= 7,
                    default => false,
                };
                if ($hit) { $matchCount++; break; }
            }
        }

        $compatScore = $matchCount / $totalCompatFilters;
    }

    // ── 3. PROXIMITY SCORE (0.0 – 1.0) ──────────────────────────
    // If user provided a location, pets in the same city score 1.0,
    // otherwise 0.0. If user gave no location, everyone scores 0.5
    // so it doesn't unfairly penalise any pet.
    if ($userLocation === '') {
        $proximityScore = 0.5; // neutral — location not provided
    } else {
        $petLocation = strtolower($pet['location'] ?? $pet['shelter'] ?? '');
        $proximityScore = (str_contains($petLocation, $userLocation) ||
                           str_contains($userLocation, $petLocation))
                          ? 1.0
                          : 0.0;
    }

    // ── FINAL WEIGHTED SCORE ─────────────────────────────────────
    $pet['score'] = round(
        ($urgencyScore    * W_URGENCY)       +
        ($compatScore     * W_COMPATIBILITY) +
        ($proximityScore  * W_PROXIMITY),
        4
    );
}
unset($pet); // break reference

// ── SORT BY SCORE DESCENDING ─────────────────────────────────────
usort($pets, fn($a, $b) => $b['score'] <=> $a['score']);

ob_end_clean();
echo json_encode(['success' => true, 'pets' => $pets]);