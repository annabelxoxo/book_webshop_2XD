<?php
require __DIR__ . "/includes/config.php";
require __DIR__ . "/includes/auth.php";

$error = "";
$email = ""; // <-- belangrijk!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $error = "Please fill in both fields.";
  } else {

    $stmt = $pdo->prepare("
      SELECT id, email, password, role
      FROM user
      WHERE email = ?
    ");

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['role'] = $user['role'];

      header('Location: index.php');
      exit();
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
  <link rel="stylesheet" href="css/styles.css">
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

        <form method="post">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

          <label>Password</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" required>

            <button type="button" id="togglePassword" aria-label="Show password">
              <img id="eyeIcon" src="images/hide.png" alt="Show password">
            </button>
          </div>

          <button type="submit" class="auth-btn">Login</button>
        </form>

        <p class="auth-bottom">
          Don't have an account? <a href="register.php">Register here</a>.
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
      eyeIcon.src = "images/visible.png";
      eyeIcon.alt = "Hide password";
      toggleBtn.setAttribute("aria-label", "Hide password");
    } else {
      passwordInput.type = "password";
      eyeIcon.src = "images/hide.png";
      eyeIcon.alt = "Show password";
      toggleBtn.setAttribute("aria-label", "Show password");
    }
  });
</script>


</body>
</html>
