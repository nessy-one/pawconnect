<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/db.php';

// Validate input
if (!isset($_POST['latitude']) || !isset($_POST['longitude'])) {
    echo json_encode(['error' => 'Missing coordinates']);
    exit;
}

$user_lat = floatval($_POST['latitude']);
$user_lon = floatval($_POST['longitude']);
$filter   = isset($_POST['type']) ? $_POST['type'] : 'All';

// ── Haversine distance ───────────────────────────────────────────
function haversine($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a    = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
    return $earth_radius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
 
// Build query — filter by type if not "All"
// supports optional search sent from the search bar
$conditions = [];
$bindParams = [];
$bindTypes  = "";
 
if ($filter !== 'All') {
    $conditions[] = "LOWER(type) = LOWER(?)";
    $bindParams[] = $filter;
    $bindTypes   .= "s";
}

//Search bar on facilities.html filters client-side
if (!empty($_POST['search'])) {
    $t = '%' . $_POST['search'] . '%';
    $conditions[] = "(name LIKE ? OR address LIKE ? OR description LIKE ?)";
    array_push($bindParams, $t, $t, $t);
    $bindTypes   .= "sss";
    // $bindParams[] = $searchTerm;
    // $bindParams[] = $searchTerm;
    // $bindTypes   .= "ss";
}

$sql = "SELECT * FROM facility";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = mysqli_prepare($conn, $sql);
if (!empty($bindParams)) {
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ── Calculate distance & collect ─────────────────────────────────
$facilities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $distance          = haversine($user_lat, $user_lon, $row['latitude'], $row['longitude']);
    $row['distance']   = round($distance, 2);
    $facilities[]      = $row;
}
 
// ── Sort closest first ───────────────────────────────────────────
usort($facilities, fn($a, $b) => $a['distance'] <=> $b['distance']);
 
echo json_encode($facilities);
?>