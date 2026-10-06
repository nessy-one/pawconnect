<?php
/* PawConnect Admin - Post a Notification
 * Lets an admin broadcast a vaccination drive, pet care event, or community activity notice to all users (or one specific user */

session_start();
require_once __DIR__ . '/../php/db.php'; 

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('Admins only.');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $message   = trim($_POST['message'] ?? '');
    $type      = $_POST['type'] ?? 'general';
    $target    = trim($_POST['target'] ?? 'all'); // 'all' or a user_id
    $eventDate = $_POST['event_date'] ?? null;     // optional
    $location  = trim($_POST['location'] ?? '');

    $allowedTypes = ['vaccination', 'event', 'community', 'general'];
    if ($title === '' || $message === '') {
        $errors[] = 'Title and message are required.';
    }
    if (!in_array($type, $allowedTypes, true)) {
        $errors[] = 'Invalid notification type.';
    }

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO notifications (title, message, type, target, event_date, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $title, $message, $type, $target, $eventDate, $location);
        $stmt->execute();
        // $stmt->execute([
        //     ':title'      => $title,
        //     ':message'    => $message,
        //     ':type'       => $type,
        //     ':target'     => $target !== '' ? $target : 'all',
        //     ':event_date' => $eventDate ?: null,
        //     ':location'   => $location ?: null,
        //     ':created_by' => $_SESSION['admin_id'],
        // ]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Post Notification - PawConnect Admin</title>
<style>
  * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
  body { background: #F4F1E6; padding: 40px; }
  .form-card { max-width: 520px; margin: 0 auto; background: white; padding: 28px; border-radius: 10px; }
  h2 { color: #012224; margin-bottom: 18px; }
  label { display: block; margin: 14px 0 6px; font-size: 13px; font-weight: 600; color: #012224; }
  input, select, textarea {
    width: 100%; padding: 9px 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;
  }
  textarea { resize: vertical; min-height: 90px; }
  button {
    margin-top: 20px; background: #3b8fc4; color: white; border: none;
    padding: 11px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;
  }
  button:hover { opacity: 0.9; }
  .msg-success { background: #dff5e1; color: #1d6b2f; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13.5px; }
  .msg-error { background: #fbe0e0; color: #a12626; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 13.5px; }
</style>
</head>
<body>
  <div class="form-card">
    <h2>Post a Notification</h2>

    <?php if ($success): ?>
      <div class="msg-success">Notification sent successfully.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
      <div class="msg-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="POST">
      <label>Title</label>
      <input type="text" name="title" maxlength="150" required>

      <label>Message</label>
      <textarea name="message" required></textarea>

      <label>Type</label>
      <select name="type">
        <option value="vaccination">Vaccination Drive</option>
        <option value="event">Pet Care Event</option>
        <option value="community">Community Activity</option>
        <option value="general">General</option>
      </select>

      <label>Event Date &amp; Time (optional)</label>
      <input type="datetime-local" name="event_date">

      <label>Location (optional)</label>
      <input type="text" name="location" maxlength="150">

      <label>Send To</label>
      <input type="text" name="target" value="all" placeholder="'all' or a specific user ID">

      <button type="submit">Post Notification</button>
    </form>
  </div>
</body>
</html>