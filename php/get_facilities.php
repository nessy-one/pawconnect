<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// require 'php/db.php';
require __DIR__ . '/db.php';

// Validate input
if (!isset($_POST['latitude']) || !isset($_POST['longitude'])) {
    echo json_encode(['error' => 'Missing coordinates']);
    exit;
}

$user_lat = floatval($_POST['latitude']);
$user_lon = floatval($_POST['longitude']);
$filter   = isset($_POST['type']) ? $_POST['type'] : 'All';

function haversine($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a    = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
    $c    = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

// Build query — filter by type if not "All"
if ($filter !== 'All') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM facility WHERE LOWER(type) = LOWER(?)");
    mysqli_stmt_bind_param($stmt, "s", $filter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, "SELECT * FROM facility");
}

$facilities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $distance          = haversine($user_lat, $user_lon, $row['latitude'], $row['longitude']);
    $row['distance']   = round($distance, 2);
    $facilities[]      = $row;
}

// Sort by distance ascending
usort($facilities, function ($a, $b) {
    return $a['distance'] <=> $b['distance'];
});

echo json_encode($facilities);
?>