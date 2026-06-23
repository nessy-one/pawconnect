<?php
// login.php
session_start();
include "db.php";

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
 
if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {

    $_SESSION['user'] = $username;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] == 'admin') {
        header("Location: ../admin/admin.html");
    } else {
        header("Location: ../dashboard.html");
    }
    exit();
}
} else {
    header("Location: ../login.html?error=user_not_found");
    exit();
}
?>