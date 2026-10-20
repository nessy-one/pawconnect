<?php
// db.php
$host = "localhost";
$user = "root";
$pass = "123456";
$db   = "pawconnect";

$conn = new mysqli("localhost", "root", "123456", "pawconnect");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

const RESERVATION_EXPIRY_SQL = "
    UPDATE pet p
    SET status='available', reserved_days=NULL, reserved_at=NULL
    WHERE p.status='reserved'
      AND p.reserved_at IS NOT NULL
      AND DATE_ADD(p.reserved_at, INTERVAL p.reserved_days DAY) <= NOW()
      AND NOT EXISTS (
          SELECT 1 FROM reservations r
          WHERE r.pet_id = p.id AND r.status IN ('forwarded','vet_approved')
      )
";

function expireOldReservations($dbConnection): void {
    try {
        if ($dbConnection instanceof PDO) {
            $dbConnection->exec(RESERVATION_EXPIRY_SQL);
        } elseif ($dbConnection instanceof mysqli) {
            @$dbConnection->query(RESERVATION_EXPIRY_SQL);
        }
    } catch (\Throwable $e) {
        error_log('expireOldReservations() failed: ' . $e->getMessage());
    }
}
?>