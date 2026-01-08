<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$email = trim((string)($_GET["email"] ?? ""));
$token = trim((string)($_GET["token"] ?? ""));

$error = "";
$success = "";

if ($email === "" || $token === "") {
  http_response_code(400);
  die("Invalid reset link.");
}

$stmt = $pdo->prepare("SELECT id, reset_token_hash, reset_token_expires FROM user WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u || empty($u["reset_token_hash"]) || empty($u["reset_token_expires"])) {
  http_response_code(400);
  die("Invalid reset link.");
}

if (strtotime($u["reset_token_expires"]) < time()) {
  http_response_code(400);
  die("Reset link expired.");
}

if (!password_verify($token, $u["reset_token_hash"])) {
  http_response_code(400);
  die("Invalid reset link.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $pass1 = (string)($_POST["password"] ?? "");
  $pass2 = (string)($_POST["password2"] ?? "");

  if ($pass1 === "" || strlen($pass1) < 6) {
    $error = "Password must be at least 6 characters.";
  } elseif ($pass1 !== $pass2) {
    $error = "Passwords do not match.";
  } else {
    $hash = password_hash($pass1, PASSWORD_DEFAULT);

    $upd = $pdo->prepare("
      UPDATE user
      SET password=?, reset_token_hash=NULL, reset_token_expires=NULL
      WHERE id=? LIMIT 1
    ");
    $upd->execute([$hash, (int)$u["id"]]);

    $success = "Password updated. You can now login.";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset password - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main class="auth-page">
  <section class="auth-layout">
    <div class="auth-left">
      <h2>NEW<br>PASSWORD</h2>
      <p>Set a new password for your account.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Reset password</h3>

        <?php if ($error): ?>
          <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
          <p class="success"><?= h($success) ?></p>
          <p class="auth-bottom"><a href="book_webshop_2XD/login.php">Go to login</a></p>
        <?php else: ?>
          <form method="post">
            <label>New password</label>
            <input type="password" name="password" required>

            <label>Repeat password</label>
            <input type="password" name="password2" required>

            <button class="auth-btn" type="submit">Update password</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
