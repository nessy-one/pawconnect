<?php
require_once 'db.php'; // must define $conn (mysqli), same file login.php uses

const DEV_MODE = true;                       // true = log the link to a file instead of emailing
const BASE_URL = 'http://localhost/pawconnect';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../forgot_password.html?error=invalid_email');
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);

    // one active reset per user
    $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $del->bind_param('i', $user['id']);
    $del->execute();

    $ins = $conn->prepare(
        "INSERT INTO password_resets (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))"
    );
    $ins->bind_param('is', $user['id'], $hash);
    $ins->execute();

    $link = BASE_URL . '/reset_password.html?token=' . $token;

    if (DEV_MODE) {
        file_put_contents(__DIR__ . '/reset_links.log', date('c') . "  $email  $link\n", FILE_APPEND);
    } else {
        require_once __DIR__ . '/../vendor/autoload.php';
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'youraddress@gmail.com';
            $mail->Password   = 'your-16-char-app-password';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('youraddress@gmail.com', 'PawConnect');
            $mail->addAddress($email, $user['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Reset your PawConnect password';
            $mail->Body    = 'Hi ' . htmlspecialchars($user['name']) . ',<br><br>'
                           . 'Click the link below to choose a new password. It expires in 1 hour.<br><br>'
                           . '<a href="' . $link . '">Reset my password</a><br><br>'
                           . 'If you didn\'t request this, you can ignore this email.';
            $mail->send();
        } catch (Exception $e) {
            error_log('Reset email failed: ' . $e->getMessage());
        }
    }
}

// Same response whether or not the email exists, so nobody can probe for registered emails
header('Location: ../forgot_password.html?sent=1');
exit;