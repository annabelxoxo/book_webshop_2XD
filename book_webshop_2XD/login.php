<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$error = "";
$email = "";

/**
 * redirect:
 * - default naar index.php
 * - block open redirects
 * - altijd redirecten via APP_URL + relative path
 */
$redirect = trim((string)($_GET['redirect'] ?? 'index.php'));

if ($redirect === '' || str_contains($redirect, '://') || str_starts_with($redirect, '//')) {
  $redirect = 'index.php';
}

$redirect = ltrim($redirect, '/');
$redirect = preg_replace('#^book_webshop_2XD/book_webshop_2XD/#', '', $redirect);
$redirect = preg_replace('#^book_webshop_2XD/#', '', $redirect);
$redirect = str_replace(['../', '..\\'], '', $redirect);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($email === '' || $password === '') {
    $error = "Please fill in both fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address.";
  } else {

    $stmt = $pdo->prepare("
      SELECT id, name, email, password, role, units
      FROM user
      WHERE email = ?
      LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, (string)$user['password'])) {

      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['role']    = (string)$user['role'];
      $_SESSION['name']    = (string)$user['name'];
      $_SESSION['units']   = (int)$user['units'];

      header("Location: " . APP_URL . $redirect);
      exit;

    } else {
      $error = "Invalid email or password.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Book Webshop</title>

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="auth-page">
  <section class="auth-layout">

    <div class="auth-left">
      <h2>MORE<br>BOOKS<br>LESS<br>SCREENS</h2>
      <p>Welcome back! Log in to continue shopping.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Sign In</h3>

        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" action="<?= APP_URL ?>login.php?redirect=<?= urlencode($redirect) ?>">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

          <label>Password</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" required>

            <button type="button" id="togglePassword" aria-label="Show password">
              <img id="eyeIcon" src="<?= APP_URL ?>images/hide.png" alt="Show password">
            </button>
          </div>

          <button type="submit" class="auth-btn">Login</button>
        </form>

        <p class="auth-bottom">
          <a href="<?= APP_URL ?>forgot_password.php">Forgot password?</a>
        </p>

        <p class="auth-bottom">
          Don't have an account? <a href="<?= APP_URL ?>register.php">Register here</a>.
        </p>
      </div>
    </div>

  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
  const passwordInput = document.getElementById("password");
  const toggleBtn = document.getElementById("togglePassword");
  const eyeIcon = document.getElementById("eyeIcon");

  toggleBtn.addEventListener("click", function () {
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      eyeIcon.src = "<?= APP_URL ?>images/visible.png";
      eyeIcon.alt = "Hide password";
      toggleBtn.setAttribute("aria-label", "Hide password");
    } else {
      passwordInput.type = "password";
      eyeIcon.src = "<?= APP_URL ?>images/hide.png";
      eyeIcon.alt = "Show password";
      toggleBtn.setAttribute("aria-label", "Show password");
    }
  });
</script>

</body>
</html>
