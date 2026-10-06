<?php
//filter-pets.php

ob_start();
error_reporting(0);
header('Content-Type: application/json');

// $host = "localhost";
// $user = "root";
// $pass = "123456";
// $db   = "pawconnect";

require __DIR__ . '/db.php';

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

// ── EXPIRE OLD RESERVATIONS FIRST ─────────────────────────────────
// Flip any pet whose reserved_at + reserved_days window has passed back
// to available, before building the listing. (Shared helper — see db.php.)
expireOldReservations($pdo);

$conditions = [];
$params     = [];

// ── EXCLUDE MEDICAL-CARE PETS ─────────────────────────────────────
// Pets flagged 'needs_treatment' (awaiting a private vet request) or 'under_vet_care' (an approved medical case already exists) are not part of the normal adoption pool — they only show up on the Private Vet side. See admin_medical_cases.php / private_vet_api.php.
$conditions[] = "p.status NOT IN ('needs_treatment', 'under_vet_care')";

// ── TYPE filter 
$filterType = '';
if (!empty($_GET['type']) && in_array($_GET['type'], ['cat', 'dog'])) {
    $conditions[] = "type = ?";
    $params[]     = $_GET['type'];
    $filterType   = $_GET['type'];
}

// ── SEARCH filter — checks name, breed, shelter, AND location 
if (!empty($_GET['search'])) {
    $t = '%' . $_GET['search'] . '%';
    $conditions[] = "(name LIKE ? OR breed LIKE ? OR shelter LIKE ? OR location LIKE ? OR type LIKE ?)";
    array_push($params, $t, $t, $t, $t, $t);
}

// ── BREED filter 
// $allowedBreeds = ['local', 'mixed', 'purebred'];
$breedMap = ['local' => 'Native', 'mixed' => 'Mixed', 'purebred' => 'Purebred'];

$filterBreeds  = isset($_GET['breed']) ? (array)$_GET['breed'] : [];
// $filterBreeds  = array_filter($filterBreeds, fn($b) => in_array($b, $allowedBreeds));
$mapped = array_map(fn($b) => $breedMap[$b] ?? ucfirst($b), $filterBreeds);

// for priority, either change the checkbox value in adopt.html to "urgent", or map pwd -> urgent server-side:
$priorityMap = ['pwd' => 'urgent', 'normal' => 'normal'];

if (!empty($filterBreeds)) {
    $placeholders = implode(',', array_fill(0, count($mapped), '?'));
    $conditions[] = "breed IN ($placeholders)";
    foreach ($mapped as $b) $params[] = $b;
}

// ── AGE filter ───────────────────────────────────────────────────
$allowedAges = ['0-1', '2-3', '4-6', '7+'];
$filterAges  = isset($_GET['age']) ? (array)$_GET['age'] : [];
$filterAges  = array_filter($filterAges, fn($a) => in_array($a, $allowedAges));
 
if (!empty($filterAges)) {
    $ageClauses = [];
    foreach ($filterAges as $range) {
        switch ($range) {
            case '0-1': $ageClauses[] = "(age >= 0 AND age <= 1)"; break;
            case '2-3': $ageClauses[] = "(age >= 2 AND age <= 3)"; break;
            case '4-6': $ageClauses[] = "(age >= 4 AND age <= 6)"; break;
            case '7+':  $ageClauses[] = "(age >= 7)";             break;
        }
    }
    $conditions[] = '(' . implode(' OR ', $ageClauses) . ')';
}

// ── PRIORITY filter ──────────────────────────────────────────────
$allowedPriorities = ['pwd', 'normal'];
$filterPriorities  = isset($_GET['priority']) ? (array)$_GET['priority'] : [];
$filterPriorities  = array_filter($filterPriorities, fn($p) => in_array($p, $allowedPriorities));

if (!empty($filterPriorities)) {
    // FIX: the checkbox sends 'pwd'/'normal', but pet.urgency stores
    // 'urgent'/'normal'. This used to bind the raw 'pwd' value straight
    // into the query, which never matched anything — the PWD filter
    // silently returned zero pets. Map through $priorityMap first.
    $mappedPriorities = array_map(fn($p) => $priorityMap[$p] ?? $p, $filterPriorities);
    $placeholders = implode(',', array_fill(0, count($mappedPriorities), '?'));
    $conditions[] = "urgency IN ($placeholders)";
    foreach ($mappedPriorities as $p) $params[] = $p;
}

// ── LOCATION (optional) ──────────────────────────────────────────
// User optionally sends their city e.g. ?location=Angeles+City
$userLocation = !empty($_GET['location']) ? strtolower(trim($_GET['location'])) : '';

// ── BUILD & RUN QUERY ────────────────────────────────────────────
$sql = "SELECT p.*, rt.chip_uid AS chip_uid,
            GREATEST(0, DATEDIFF(DATE_ADD(p.reserved_at, INTERVAL p.reserved_days DAY), NOW())) AS reserved_days_remaining
        FROM pet p
        LEFT JOIN rfid_tags rt ON rt.pet_id = p.id AND rt.status = 'active'";
if ($conditions) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY p.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pets = $stmt->fetchAll();

// ── WEIGHTED SCORING ─────────────────────────────────────────────
// Weights
const W_URGENCY       = 0.5;
const W_COMPATIBILITY = 0.3;
const W_PROXIMITY     = 0.2;

// Count how many compatibility filters the user actually set
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
                    '0-1' => $petAge >= 0 && $petAge <= 1,
                    '2-3' => $petAge >= 2 && $petAge <= 3,
                    '4-6' => $petAge >= 4 && $petAge <= 6,
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

    // ── FINAL WEIGHTED SCORE 
    $pet['score'] = round(
        ($urgencyScore    * W_URGENCY)       +
        ($compatScore     * W_COMPATIBILITY) +
        ($proximityScore  * W_PROXIMITY),
        4
    );

    // ── DAYS REMAINING (for accurate "Reserved for X days") ─ dinelete q din basta tanginang thesis to
    // if ($pet['status'] === 'reserved' && !empty($pet['reserved_at']) && !empty($pet['reserved_days'])) {
    //     $expiresAt = new DateTime($pet['reserved_at']);
    //     $expiresAt->modify('+' . intval($pet['reserved_days']) . ' days');
    //     $now = new DateTime();
    //     $daysLeft = (int)ceil(($expiresAt->getTimestamp() - $now->getTimestamp()) / 86400);
    //     $pet['reserved_days_remaining'] = max(0, $daysLeft);
    // } else {
    //     $pet['reserved_days_remaining'] = null;
    // }
}
unset($pet); // break reference

// ── SORT BY SCORE DESCENDING 
usort($pets, fn($a, $b) => $b['score'] <=> $a['score']);

ob_end_clean();
echo json_encode(['success' => true, 'pets' => $pets]);