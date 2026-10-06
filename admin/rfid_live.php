<?php
// rfid_live.php — polled by the admin dashboard to surface the newest scan.
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../php/db.php';   // match whatever admin_rfid.php uses

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'vet'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$since = isset($_GET['since']) ? (int) $_GET['since'] : 0;

$sql = "SELECT s.id AS scan_id, s.event_type, s.note, s.scanned_at,
               t.id AS tag_id, t.chip_uid, t.status AS tag_status, t.registered_at,
               p.id AS pet_id, p.name AS pet_name, p.type AS pet_type, p.breed AS pet_breed,
               p.vaccinated, p.dewormed, p.neutered, p.health_status, p.medical_notes,
               f.name AS facility_name
        FROM rfid_scans s
        JOIN rfid_tags t     ON t.id = s.rfid_tag_id
        LEFT JOIN pet p      ON p.id = t.pet_id
        LEFT JOIN facility f ON f.id = s.facility_id
        WHERE s.id > ?
        ORDER BY s.id DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $since);
$stmt->execute();
$scan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$scan) {
    echo json_encode(['success' => true, 'scan' => null]);
    exit;
}

// Last 5 scans on the same tag, for the little history strip.
$history = [];
$stmt = $conn->prepare(
    "SELECT event_type, note, scanned_at FROM rfid_scans
     WHERE rfid_tag_id = ? ORDER BY id DESC LIMIT 5"
);
$stmt->bind_param('i', $scan['tag_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $history[] = $row; }
$stmt->close();

$scan['history'] = $history;
echo json_encode(['success' => true, 'scan' => $scan]);