<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$success = "";
$email = "";
$name = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';

  if ($name === '' || $email === '' || $password === '' || $password2 === '') {
    $error = "Please fill in all fields.";
  } elseif ($password !== $password2) {
    $error = "Passwords do not match.";
  } else {

  if ($email === '' || $password === '' || $password2 === '') {
    $error = "Please fill in all fields.";
  } elseif ($password !== $password2) {
    $error = "Passwords do not match.";
  } else {

    $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $exists = $stmt->fetch();

    if ($exists) {
      $error = "This email is already registered.";
    } else {

      $hash = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare("
        INSERT INTO user (email, password, role)
        VALUES (?, ?, 'user')
      ");
      $stmt->execute([$email, $hash]);

      $success = "Account created! You can now login.";

    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Book Webshop</title>
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<main class="auth-page">
  <section class="auth-layout">

    <div class="auth-left">
      <h2>Create<br>Your<br>Account</h2>
      <p>Join the webshop and start shopping your favourite books.</p>
    </div>

    <div class="auth-right">
      <div class="auth-card">
        <h3>Register</h3>

        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
          <p class="success"><?= htmlspecialchars($success); ?></p>
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

          <label>Confirm password</label>
          <div class="password-wrapper">
            <input type="password" id="password2" name="password2" required>
            <button type="button" id="togglePassword2" aria-label="Show password">
              <img id="eyeIcon2" src="images/hide.png" alt="Show password">
            </button>
          </div>

          <button type="submit" class="auth-btn">Register</button>
        </form>

        <p class="auth-bottom">
          Already have an account? <a href="login.php">Login here</a>.
        </p>
      </div>
    </div>

  </section>
</main>

<?php include 'includes/footer.php'; ?>

<script>
  const pw1 = document.getElementById("password");
  const btn1 = document.getElementById("togglePassword");
  const icon1 = document.getElementById("eyeIcon");

  btn1.addEventListener("click", function () {
    if (pw1.type === "password") {
      pw1.type = "text";
      icon1.src = "images/visible.png";
      btn1.setAttribute("aria-label", "Hide password");
    } else {
      pw1.type = "password";
      icon1.src = "images/hide.png";
      btn1.setAttribute("aria-label", "Show password");
    }
  });

  const pw2 = document.getElementById("password2");
  const btn2 = document.getElementById("togglePassword2");
  const icon2 = document.getElementById("eyeIcon2");

  btn2.addEventListener("click", function () {
    if (pw2.type === "password") {
      pw2.type = "text";
      icon2.src = "images/visible.png";
      btn2.setAttribute("aria-label", "Hide password");
    } else {
      pw2.type = "password";
      icon2.src = "images/hide.png";
      btn2.setAttribute("aria-label", "Show password");
    }
  });
</script>

</body>
</html>