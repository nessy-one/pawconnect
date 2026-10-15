<?php
// admin_animals.php — admin API for Animal Records
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? 'list';
$in = json_decode(file_get_contents('php://input'), true) ?: [];
const STATUSES = ['available', 'reserved', 'adopted', 'in_custody', 'under_treatment', 'unavailable'];
const TYPES    = ['dog', 'cat', 'other'];

function out($a) { echo json_encode($a); exit; }
function fail($m) { out(['success' => false, 'message' => $m]); }

try {
    switch ($action) {

    case 'list': {
        $sql = "SELECT p.id, p.name, p.type, p.breed, p.gender, p.age, p.story AS description,
                       p.status, p.facility_id,
                       COALESCE(f.name, p.shelter) AS facility_name,
                       p.vaccinated, p.dewormed, p.neutered, p.health_status, p.medical_notes,
                       p.approval_status, p.submitted_by_vet, p.review_notes, p.created_at,
                       u.name AS vet_name,
                       (SELECT t.chip_uid FROM rfid_tags t
                         WHERE t.pet_id = p.id AND t.status = 'active' LIMIT 1) AS chip_uid
                FROM pet p
                LEFT JOIN users u ON u.id = p.submitted_by_vet
                LEFT JOIN facility f ON f.id = p.facility_id
                ORDER BY p.id DESC";
        $res = mysqli_query($conn, $sql);
        if (!$res) { http_response_code(500); fail(mysqli_error($conn)); }
        out(['success' => true, 'animals' => mysqli_fetch_all($res, MYSQLI_ASSOC)]);
    }

    case 'create':
    case 'update': {
        $name = trim($in['name'] ?? '');
        if ($name === '') fail('Name is required.');
        $status = $in['status'] ?? 'available';
        if (!in_array($status, STATUSES, true)) fail('Invalid status.');

        $type = strtolower(trim($in['type'] ?? 'dog'));
        if (!in_array($type, TYPES, true)) fail('Invalid species.');
        $gender = strtolower(trim($in['gender'] ?? ''));
        if (!in_array($gender, ['', 'male', 'female'], true)) fail('Invalid sex.');
        $breed  = trim($in['breed'] ?? '');
        $story  = trim($in['description'] ?? '');
        $age    = ($in['age'] ?? '') === '' ? null : max(0, (int)$in['age']);
        $fac    = !empty($in['facility_id']) ? (int)$in['facility_id'] : null;
        $vac = !empty($in['vaccinated']) ? 1 : 0;
        $dew = !empty($in['dewormed']) ? 1 : 0;
        $neu = !empty($in['neutered']) ? 1 : 0;
        $health = trim($in['health_status'] ?? '');
        $notes  = trim($in['medical_notes'] ?? '');

        // Shelter name: take it from the facility table when one is chosen.
        $shelter = '';
        if ($fac) {
            $q = mysqli_prepare($conn, "SELECT name FROM facility WHERE id=?");
            mysqli_stmt_bind_param($q, 'i', $fac);
            mysqli_stmt_execute($q);
            $r = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
            if (!$r) fail('Selected facility does not exist.');
            $shelter = $r['name'];
        }

        if ($action === 'create') {
            $stmt = mysqli_prepare($conn, "INSERT INTO pet
                (name,type,breed,gender,age,story,status,facility_id,shelter,vaccinated,dewormed,neutered,health_status,medical_notes,approval_status,created_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'approved', NOW())");
            mysqli_stmt_bind_param($stmt, 'ssssissisiiiss',
                $name,$type,$breed,$gender,$age,$story,$status,$fac,$shelter,$vac,$dew,$neu,$health,$notes);
        } else {
            $id = (int)($in['id'] ?? 0);
            if (!$id) fail('Missing animal id.');
            // No facility picked -> keep the existing shelter text (older rows only have the text).
            $stmt = mysqli_prepare($conn, "UPDATE pet SET name=?,type=?,breed=?,gender=?,age=?,story=?,status=?,
                facility_id=?, shelter=IF(?='', shelter, ?),
                vaccinated=?,dewormed=?,neutered=?,health_status=?,medical_notes=?,
                reserved_days = IF(?='reserved', reserved_days, NULL),
                reserved_at   = IF(?='reserved', reserved_at, NULL)
                WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssississiiissssi',
                $name,$type,$breed,$gender,$age,$story,$status,$fac,$shelter,$shelter,
                $vac,$dew,$neu,$health,$notes,$status,$status,$id);
        }
        if (!mysqli_stmt_execute($stmt)) fail('Could not save animal: ' . mysqli_error($conn));
        out(['success' => true]);
    }

    case 'set_status': {
        $id = (int)($in['id'] ?? 0);
        $status = $in['status'] ?? '';
        if (!$id || !in_array($status, STATUSES, true)) fail('Invalid request.');
        // Leaving "reserved" manually should also clear the reservation timer.
        $stmt = mysqli_prepare($conn, "UPDATE pet SET status=?,
            reserved_days = IF(?='reserved', reserved_days, NULL),
            reserved_at   = IF(?='reserved', reserved_at, NULL) WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $status, $status, $status, $id);
        if (!mysqli_stmt_execute($stmt)) fail('Could not update availability.');
        out(['success' => true]);
    }

    case 'review': {
        $id = (int)($in['id'] ?? 0);
        $decision = $in['decision'] ?? '';
        $notes = trim($in['notes'] ?? '');
        if (!$id || !in_array($decision, ['approve', 'reject'], true)) fail('Invalid request.');
        $approval = $decision === 'approve' ? 'approved' : 'rejected';
        $stmt = mysqli_prepare($conn, $decision === 'approve'
            ? "UPDATE pet SET approval_status=?, review_notes=?, status=IF(status='unavailable','available',status)
            WHERE id=? AND submitted_by_vet IS NOT NULL"
            : "UPDATE pet SET approval_status=?, review_notes=?, status='unavailable'
            WHERE id=? AND submitted_by_vet IS NOT NULL");
        mysqli_stmt_bind_param($stmt, 'ssi', $approval, $notes, $id);
        if (!mysqli_stmt_execute($stmt)) fail('Could not save review.');
        if (mysqli_stmt_affected_rows($stmt) === 0) fail('Submission not found or nothing changed.');
        out(['success' => true]);
    }

    case 'delete': {
        $id = (int)($in['id'] ?? 0);
        if (!$id) fail('Missing animal id.');
        $chk = mysqli_prepare($conn, "SELECT status, (SELECT COUNT(*) FROM reservations WHERE pet_id = pet.id) AS res_count
                                      FROM pet WHERE id=?");
        mysqli_stmt_bind_param($chk, 'i', $id);
        mysqli_stmt_execute($chk);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        if (!$row) fail('Animal not found.');
        if (in_array($row['status'], ['reserved', 'adopted'], true) || (int)$row['res_count'] > 0) {
            fail('This animal has adoption records, so it can\'t be deleted. Set it to Unavailable instead.');
        }
        $stmt = mysqli_prepare($conn, "DELETE FROM pet WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!mysqli_stmt_execute($stmt)) fail('Could not delete this animal.');
        out(['success' => true]);
    }

    default:
        http_response_code(400);
        fail('Unknown action.');
    }
} catch (\Throwable $e) {
    error_log('admin_animals.php error: ' . $e->getMessage());
    http_response_code(500);
    out(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}