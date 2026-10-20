<?php
// register.php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Role is always set on the server, never taken from the form
    $role = 'adopter';

    // Validation
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        header("Location: ../register.html?error=missing_fields");
        exit();
    }

    if ($password !== $confirm) {
        header("Location: ../register.html?error=password_mismatch");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.html?error=invalid_email");
        exit();
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        header("Location: ../register.html?error=email_taken");
        exit();
    }

    // Check if username already exists
    $check2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check2->bind_param("s", $username);
    $check2->execute();
    $check2->store_result();
    if ($check2->num_rows > 0) {
        header("Location: ../register.html?error=username_taken");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user with default role
    $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
        // No session here: the user signs in from the popup instead
        header("Location: ../register.html?registered=1");
        exit();
    } else {
        header("Location: ../register.html?error=server");
        exit();
    }
}
?>