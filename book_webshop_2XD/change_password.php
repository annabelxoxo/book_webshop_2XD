<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$userId = (int)$_SESSION["user_id"];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $current = (string)($_POST["current_password"] ?? "");
  $new1 = (string)($_POST["new_password"] ?? "");
  $new2 = (string)($_POST["new_password2"] ?? "");

  if ($current === "" || $new1 === "" || $new2 === "") {
    $error = "Please fill in all fields.";
  } elseif (strlen($new1) < 6) {
    $error = "New password must be at least 6 characters.";
  } elseif ($new1 !== $new2) {
    $error = "New passwords do not match.";
  } else {
    $stmt = $pdo->prepare("SELECT password FROM user WHERE id=? LIMIT 1");
    $stmt->execute([$userId]);
    $hash = (string)($stmt->fetchColumn() ?? "");

    if (!$hash || !password_verify($current, $hash)) {
      $error = "Current password is incorrect.";
    } else {
      $newHash = password_hash($new1, PASSWORD_DEFAULT);
      $upd = $pdo->prepare("UPDATE user SET password=? WHERE id=? LIMIT 1");
      $upd->execute([$newHash, $userId]);
      $success = "Password updated.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Change password - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
  <div class="container" >

    <a class="btn-secondary" href="book_webshop_2XD/profile.php">← Back to profile</a>

    <h2 >Change password</h2>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <form method="post" class="contact-form">
      <div class="form-group">
        <label>Current password *</label>
        <input type="password" name="current_password" required>
      </div>

      <div class="form-group">
        <label>New password *</label>
        <input type="password" name="new_password" required>
      </div>

      <div class="form-group">
        <label>Repeat new password *</label>
        <input type="password" name="new_password2" required>
      </div>

      <button type="submit" class="btn-primary">Save</button>
    </form>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
