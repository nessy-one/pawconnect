<?php
//get-pet.php -->

// para 2 sa pet
header('Content-Type: application/json');

// $host = "localhost";
// $user = "root";
// $pass = "123456";
// $db   = "pawconnect";
// $charset = 'utf8mb4';
   
require __DIR__ . '/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// ── EXPIRE OLD RESERVATIONS FIRST ─────────────────────────────────
// Flip any pet whose reserved_at + reserved_days window has passed back
// to available, before building the listing. (Shared helper — see db.php.)
expireOldReservations($pdo);

// Validate id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'No pet ID provided.']);
    exit;
}

// Fetch pet, plus its active RFID chip if one is registered.
$stmt = $pdo->prepare(
    'SELECT p.*, rt.chip_uid AS chip_uid, rt.registered_at AS chip_registered_at,
            GREATEST(0, DATEDIFF(DATE_ADD(p.reserved_at, INTERVAL p.reserved_days DAY), NOW())) AS reserved_days_remaining
     FROM pet p
     LEFT JOIN rfid_tags rt ON rt.pet_id = p.id AND rt.status = "active"
     WHERE p.id = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$pet = $stmt->fetch();

if (!$pet) {
    echo json_encode(['success' => false, 'message' => 'Pet not found.']);
    exit;
}

// Add how many days are actually left, for accurate display on the page       ----------- i2 pa dinelete q basta error yarn ?!?!?!
// if ($pet['status'] === 'reserved' && !empty($pet['reserved_at']) && !empty($pet['reserved_days'])) {
//     $expiresAt = new DateTime($pet['reserved_at']);
//     $expiresAt->modify('+' . intval($pet['reserved_days']) . ' days');
//     $now = new DateTime();
//     $daysLeft = (int)ceil(($expiresAt->getTimestamp() - $now->getTimestamp()) / 86400);
//     $pet['reserved_days_remaining'] = max(0, $daysLeft);
// } else {
//     $pet['reserved_days_remaining'] = null;
// }

echo json_encode(['success' => true, 'pet' => $pet]);