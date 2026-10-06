<?php
// register.php
session_start();
include "db.php";
 
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    //$gender    = trim($_POST['gender']); // BASTA WALA MUNA 2
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    // $role      = $_POST['role']; // DI Q ALAM PAANO Q GAGAWIN TO HAHAHAHAHHA
    // $mobile = trim($_POST['mobile'] ?? '');
 
    // Validation
    // if (empty($name) || empty($username) || empty($email) || empty($password)) {
    //     die("All fields are required.");
    // }

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        header("Location: ../register.html?error=missing_fields");
        exit();
    }

    if ($password !== $confirm) {
        die("Passwords do not match.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        die("Email already registered.");
    }

    // Check if username already exists — BEFORE inserting, not after
    $check2 = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check2->bind_param("s", $username);
    $check2->execute();
    $check2->store_result();
    if ($check2->num_rows > 0) {
        header("Location: ../register.html?error=username_taken");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    // ------------------------- PS. INALIS KO MUNA USER ROLES HELLOOOOOOOOO ------------------------------ !!!!!!!!!!!!!
    // $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    // $stmt->bind_param("sssss", $name, $username, $email, $hashedPassword, $role);
    $stmt = $conn->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $username, $email, $hashedPassword);

    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['user']    = $username;
        $_SESSION['name']    = $name;
        $_SESSION['email']   = $email;
        header("Location: ../login.html?registered=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>