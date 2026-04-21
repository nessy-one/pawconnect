<?php
$host = "localhost";
$user = "root";
$pass = "123456";
$db   = "pawconnect";

$conn = new mysqli("localhost", "root", "123456", "pawconnect");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>