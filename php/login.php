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

        if (empty($user['email_verified_at'])) {
            require_once 'otp_lib.php';
            $_SESSION['otp_email_signup'] = strtolower($user['email']);
            $r = otp_issue($conn, $user['email'], 'signup');
            if ($r['ok']) $_SESSION['otp_sent_at_signup'] = time();
            header("Location: ../verify_otp.html?purpose=signup");
            exit();
        }

        $_SESSION['user'] = $username;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

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