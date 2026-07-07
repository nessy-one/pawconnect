<?php
session_start();
//credentials: admin / admin123
//admin_login.php

// Redirect if already logged in as admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Connect to database
    include "../db.php";

    // Fetch the admin user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();

        // Check password (use password_verify if hashed, direct compare if plaintext)
        $passwordMatch = password_verify($password, $admin['password']);
        // If still using plaintext temporarily: $passwordMatch = ($password === $admin['password']);

        if ($passwordMatch) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['role']       = $admin['role'];

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
</head>
<body>
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Username:</label>
        <input type="text" name="username" required /><br><br>

        <label>Password:</label>
        <input type="password" name="password" required /><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>