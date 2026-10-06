<?php
// rfid_scan.php
// Intake endpoint the bridge script (or a WiFi board, later) calls on
// every scan. Verifies the device key, looks up the tag, and logs the scan.
// Not session-gated like your admin/vet pages — this is meant to be called
// by a machine, authenticated with the device key instead.

header('Content-Type: application/json');
require_once __DIR__ . '/../php/db.php'; // adjust the ../ if RFID isn't a sibling of your php folder; expects a mysqli connection in $conn — rename if yours differs

$input     = json_decode(file_get_contents('php://input'), true);
$chipUid = normalizeChipUid($input['chip_uid'] ?? '');
$deviceKey = $input['device_key'] ?? '';
$eventType = $input['event_type'] ?? 'scan';
$note      = $input['note'] ?? null;
 
function normalizeChipUid(string $raw): string {
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw));
}

if ($chipUid === '' || $deviceKey === '') {
    echo json_encode(['success' => false, 'message' => 'Missing chip_uid or device_key.']);
    exit;
}

// 1. Verify the device
$stmt = $conn->prepare("SELECT id, facility_id FROM rfid_devices WHERE device_key = ?");
$stmt->bind_param("s", $deviceKey);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$device) {
    echo json_encode(['success' => false, 'message' => 'Unrecognized device key.']);
    exit;
}

// 2. Look up the tag
$stmt = $conn->prepare(
    "SELECT rt.id, rt.status, rt.pet_id, p.name AS pet_name
     FROM rfid_tags rt
     LEFT JOIN pet p ON p.id = rt.pet_id
     WHERE rt.chip_uid = ?"
);
$stmt->bind_param("s", $chipUid);
$stmt->execute();
$tag = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tag) {
    echo json_encode(['success' => false, 'message' => 'This chip is not registered yet.']);
    exit;
}

if ($tag['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'This chip is ' . $tag['status'] . '.']);
    exit;
}

// 3. Log the scan
$facilityId = $device['facility_id'];
$stmt = $conn->prepare(
    "INSERT INTO rfid_scans (rfid_tag_id, facility_id, event_type, note) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("iiss", $tag['id'], $facilityId, $eventType, $note);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success'    => true,
    'scan_id'    => $conn->insert_id,
    'chip_uid'   => $chipUid,
    'tag_status' => $tag['status'],
    'pet'        => ['id' => $tag['pet_id'], 'name' => $tag['pet_name']],
]);