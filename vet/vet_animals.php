<?php
/**
 * vet_animals.php — backend for the vet "Register New Animal" page.
 * Place next to vet_reservations.php / vet_records.php.
 *
 * Actions
 *   GET  ?action=list_facilities
 *   POST action=create      (multipart: pet fields + photo_before / photo_after)
 *   POST action=add_photo   (pet_id, kind=before|after, photo) — e.g. add the "after" photo later
 */
session_start();
header('Content-Type: application/json');

function out($ok, $extra = [], $code = 200){
  http_response_code($code);
  echo json_encode(array_merge(['success' => $ok], $extra));
  exit;
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'vet') {
  out(false, ['message' => 'Not authorized.'], 403);
}

// ── DB connection ─────────────────────────────────────────────
// Use the same include your other vet_*.php files use. It must expose a
// mysqli connection named $conn.
require __DIR__ . '/../php/db.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');

const MAX_PHOTO_BYTES = 5 * 1024 * 1024;
const PHOTO_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ---------- helpers ---------- */

function clean($v, $max = 255){
  $v = trim((string)($v ?? ''));
  if ($v === '') return null;
  return mb_substr($v, 0, $max);
}

// Returns null if no file was sent, an error string if invalid, or ['tmp'=>..,'ext'=>..].
function check_photo($key){
  if (empty($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) return null;
  $f = $_FILES[$key];
  if ($f['error'] !== UPLOAD_ERR_OK) return "Upload failed for the {$key} image (code {$f['error']}).";
  if ($f['size'] > MAX_PHOTO_BYTES) return 'Each image must be smaller than 5 MB.';
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  if (!isset(PHOTO_MIMES[$mime])) return 'Only JPEG, PNG, GIF or WEBP images are allowed.';
  return ['tmp' => $f['tmp_name'], 'ext' => PHOTO_MIMES[$mime]];
}

// Moves the file and returns the root-based web path (e.g. /pawconnect/php/uploads/pet_photos/12_before_ab12.jpg).
function store_photo(array $p, $petId, $kind, &$savedFiles){
  $dir = __DIR__ . '/../php/uploads/pet_photos';
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) return null;
  $fname = sprintf('%d_%s_%s.%s', $petId, $kind, bin2hex(random_bytes(4)), $p['ext']);
  $dest = $dir . '/' . $fname;
  if (!move_uploaded_file($p['tmp'], $dest)) return null;
  $savedFiles[] = $dest;
  $root = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');   // "/pawconnect"
  return $root . '/php/uploads/pet_photos/' . $fname;
}

/* ---------- list_facilities ---------- */
if ($action === 'list_facilities') {
  $rows = [];
  // Adjust table/column names if your facility table is named differently.
  $res = $conn->query('SELECT id, name FROM facilities ORDER BY name');
  if ($res) while ($r = $res->fetch_assoc()) $rows[] = ['id' => (int)$r['id'], 'name' => $r['name']];
  else out(false, ['message' => 'Could not read facilities: ' . $conn->error], 500);
  out(true, ['facilities' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, ['message' => 'Invalid request.'], 405);

/* ---------- create ---------- */
if ($action === 'create') {
  $name = clean($_POST['name'] ?? null);
  $type = clean($_POST['type'] ?? null, 50);
  if (!$name) out(false, ['message' => 'Animal name is required.'], 422);
  if (!in_array($type, ['Dog', 'Cat'], true)) out(false, ['message' => 'Type must be Dog or Cat.'], 422);

  $ageRaw = trim($_POST['age'] ?? '');
  $age = null;
  if ($ageRaw !== '') {
    if (!ctype_digit($ageRaw) || (int)$ageRaw > 40) out(false, ['message' => 'Age must be a whole number between 0 and 40.'], 422);
    $age = (int)$ageRaw;
  }

  $gender = clean($_POST['gender'] ?? null, 20);
  if ($gender !== null && !in_array($gender, ['Male', 'Female'], true)) out(false, ['message' => 'Invalid gender.'], 422);

  $urgency = clean($_POST['urgency'] ?? null, 50);
  if ($urgency !== null && !in_array($urgency, ['Low', 'Medium', 'High', 'Critical'], true)) out(false, ['message' => 'Invalid urgency.'], 422);

  $status = $_POST['status'] ?? 'in_custody';
  if (!in_array($status, ['in_custody', 'available'], true)) out(false, ['message' => 'Invalid listing status.'], 422);

  $health = clean($_POST['health_status'] ?? null, 100);
  $allowedHealth = ['Healthy', 'Recovering', 'Under treatment', 'Injured', 'Critical'];
  if ($health !== null && !in_array($health, $allowedHealth, true)) out(false, ['message' => 'Invalid health status.'], 422);

  $facilityId = ($_POST['facility_id'] ?? '') !== '' ? (int)$_POST['facility_id'] : null;
  $breed    = clean($_POST['breed'] ?? null);
  $shelter  = clean($_POST['shelter'] ?? null);
  $location = clean($_POST['location'] ?? null);
  $story    = clean($_POST['story'] ?? null, 5000);
  $notes    = clean($_POST['medical_notes'] ?? null, 5000);
  $vacc     = !empty($_POST['vaccinated']) ? 1 : 0;
  $deworm   = !empty($_POST['dewormed'])   ? 1 : 0;
  $neuter   = !empty($_POST['neutered'])   ? 1 : 0;

  // Validate photos before touching the DB.
  $photos = [];
  foreach (['before' => 'photo_before', 'after' => 'photo_after'] as $kind => $key) {
    $p = check_photo($key);
    if (is_string($p)) out(false, ['message' => $p], 422);
    if ($p) $photos[$kind] = $p;
  }

  $conn->begin_transaction();
  $saved = [];
  try {
    $stmt = $conn->prepare(
      'INSERT INTO pet (name, breed, age, gender, type, shelter, urgency, facility_id, location, status,
                        story, vaccinated, dewormed, neutered, health_status, medical_notes)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param('ssissssisssiiiss',
      $name, $breed, $age, $gender, $type, $shelter, $urgency, $facilityId, $location, $status,
      $story, $vacc, $deworm, $neuter, $health, $notes);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $petId = $conn->insert_id;
    $stmt->close();

    foreach ($photos as $kind => $p) {
      $path = store_photo($p, $petId, $kind, $saved);
      if (!$path) throw new Exception('Could not save the ' . $kind . ' photo on the server.');
      $s2 = $conn->prepare('INSERT INTO pet_photos (pet_id, kind, photo_path, uploaded_by) VALUES (?,?,?,?)');
      if (!$s2) throw new Exception($conn->error);
      $uid = (int)$_SESSION['user_id'];
      $s2->bind_param('issi', $petId, $kind, $path, $uid);
      if (!$s2->execute()) throw new Exception($s2->error);
      $s2->close();
    }

    $conn->commit();
    out(true, ['pet_id' => $petId]);
  } catch (Throwable $e) {
    $conn->rollback();
    foreach ($saved as $f) @unlink($f);
    out(false, ['message' => 'Could not register the animal: ' . $e->getMessage()], 500);
  }
}

/* ---------- add_photo (e.g. attach the "after" photo later) ---------- */
if ($action === 'add_photo') {
  $petId = (int)($_POST['pet_id'] ?? 0);
  $kind  = $_POST['kind'] ?? '';
  if (!$petId || !in_array($kind, ['before', 'after'], true)) out(false, ['message' => 'Invalid request.'], 422);

  $p = check_photo('photo');
  if (!$p) out(false, ['message' => 'Attach an image.'], 422);
  if (is_string($p)) out(false, ['message' => $p], 422);

  $chk = $conn->prepare('SELECT id FROM pet WHERE id = ?');
  $chk->bind_param('i', $petId); $chk->execute();
  if (!$chk->get_result()->fetch_assoc()) out(false, ['message' => 'Animal not found.'], 404);

  $saved = [];
  $path = store_photo($p, $petId, $kind, $saved);
  if (!$path) out(false, ['message' => 'Could not save the photo on the server.'], 500);

  $uid = (int)$_SESSION['user_id'];
  $s = $conn->prepare('INSERT INTO pet_photos (pet_id, kind, photo_path, uploaded_by) VALUES (?,?,?,?)');
  $s->bind_param('issi', $petId, $kind, $path, $uid);
  if (!$s->execute()) { @unlink($saved[0]); out(false, ['message' => $s->error], 500); }
  out(true, ['photo_path' => $path]);
}

out(false, ['message' => 'Unknown action.'], 400);