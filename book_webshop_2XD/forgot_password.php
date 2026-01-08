<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$success = "";
$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim((string)($_POST["email"] ?? ""));

  if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email.";
  } else {

    $success = "If that email exists, you'll receive a reset link.";

    try {
      $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      $uid = (int)($stmt->fetchColumn() ?? 0);

      if ($uid > 0) {
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($token, PASSWORD_DEFAULT);
        $expires = date("Y-m-d H:i:s", time() + 60*30);

        $upd = $pdo->prepare("UPDATE user SET reset_token_hash=?, reset_token_expires=? WHERE id=? LIMIT 1");
        $upd->execute([$hash, $expires, $uid]);

        // ✅ juiste base URL (met subfolder)
        $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";

        // BELANGRIJK: dit pakt automatisch /book_webshop_2XD/book_webshop_2XD
        $basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");

        $resetLink = $scheme . "://" . $host . $basePath .
          "/reset_password.php?email=" . urlencode($email) .
          "&token=" . urlencode($token);

        $success .= "<br><small>TEST LINK: <a href='".h($resetLink)."'>Reset password</a></small>";
      }
    } catch (Throwable $e) {
      // bewust stil (security)
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot password - Book Webshop</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="auth-page">
  <section class="auth-layout">
    <div class="auth-left">
      <h2>RESET<br>PASSWORD</h2>
      <p>Enter your email and we'll send you a reset link.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Forgot password</h3>

        <?php if ($error): ?>
          <p class="error"><?= h($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
          <p class="success"><?= $success ?></p>
        <?php endif; ?>

        <form method="post">
          <label>Email</label>
          <input type="email" name="email" value="<?= h($email) ?>" required>
          <button class="auth-btn" type="submit">Send reset link</button>
        </form>

        <p class="auth-bottom">
          Back to <a href="login.php">Login</a>
        </p>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
