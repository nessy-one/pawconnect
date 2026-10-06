<?php
// login.php
session_start();
include "db.php";

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if ($username === '' || $password === '') {
    header("Location: ../login.html?error=missing_fields");
    exit();
}

$sql = "SELECT * FROM users WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        if ($user['status'] === 'suspended') {
            header("Location: ../login.html?error=suspended");
            exit();
        }

        $_SESSION['user'] = $username;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // FIX: admin_login.php also sets 'admin_id' and 'admin_name', and
        // send_notif.php / admin_donations.php / admin_facility.php read
        // those specifically. An admin who logs in through this general
        // form (this file redirects role === 'admin' to the admin
        // dashboard, so it's a real path) previously never got those keys
        // set, so send_notif.php wrongly reported "Admins only," and
        // donation/facility records got logged under the generic name
        // "Admin" instead of the real one. Keep both conventions in sync.
        if ($user['role'] == 'admin') {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
        }

        if ($user['role'] == 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($user['role'] == 'vet') {
            header("Location: ../vet/vet_dashboard.php");
        } else {
            header("Location: ../dashboard.html");
        }
        exit();
    } else {
        header("Location: ../login.html?error=wrong_password");
        exit();
    }
} else {
    header("Location: ../login.html?error=user_not_found");
    exit();
}
?>