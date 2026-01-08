<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$error = "";
$email = "";

$redirect = trim((string)($_GET['redirect'] ?? 'book_webshop_2XD/index.php'));

if ($redirect === '' || str_contains($redirect, '://') || str_starts_with($redirect, '//')) {
  $redirect = 'book_webshop_2XD/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

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

    if ($user && password_verify($password, $user['password'])) {

      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['name'] = $user['name'];
      $_SESSION['units'] = (int)$user['units'];

header("Location: /book_webshop_2XD/" . ltrim($redirect, "/"));
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
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

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

          <form method="post" action="book_webshop_2XD/login.php?redirect=<?= urlencode($redirect) ?>">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

          <label>Password</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" required>

            <button type="button" id="togglePassword" aria-label="Show password">
              <img id="eyeIcon" src="book_webshop_2XD/images/hide.png" alt="Show password">
            </button>
          </div>

          <button type="submit" class="auth-btn">Login</button>
        </form>
        <p class="auth-bottom">
          <a href="book_webshop_2XD/forgot_password.php">Forgot password?</a>
        </p>
        
        <p class="auth-bottom">
          Don't have an account? <a href="book_webshop_2XD/register.php">Register here</a>.
        </p>
      </div>
    </div>

  </section>
</main>

<?php include 'includes/footer.php'; ?>

<script>
  const passwordInput = document.getElementById("password");
  const toggleBtn = document.getElementById("togglePassword");
  const eyeIcon = document.getElementById("eyeIcon");

  toggleBtn.addEventListener("click", function () {
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      eyeIcon.src = "book_webshop_2XD/images/visible.png";
      eyeIcon.alt = "Hide password";
      toggleBtn.setAttribute("aria-label", "Hide password");
    } else {
      passwordInput.type = "password";
      eyeIcon.src = "book_webshop_2XD/images/hide.png";
      eyeIcon.alt = "Show password";
      toggleBtn.setAttribute("aria-label", "Show password");
    }
  });
</script>

</body>
</html>
