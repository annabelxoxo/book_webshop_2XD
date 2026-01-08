<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("profile.php"));
  exit;
}

$userId = (int)$_SESSION['user_id'];

$success = "";
$error = "";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("SELECT id, name, email FROM user WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT units FROM user WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$userUnits = (int)($stmt->fetchColumn() ?? 0);

if (!$user) {
  session_destroy();
  header("Location: " . APP_URL . "login.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  if ($action === "delete_address") {
    $addressId = (int)($_POST["address_id"] ?? 0);

    if ($addressId > 0) {
      $del = $pdo->prepare("DELETE FROM address WHERE id = ? AND user_id = ? LIMIT 1");
      $del->execute([$addressId, $userId]);

      if ($del->rowCount() > 0) {
        $success = "Address deleted.";
      } else {
        $error = "Could not delete address (not found).";
      }
    }
  }
}

$addrStmt = $pdo->prepare("
  SELECT
    id, type, full_name, phone, street, house_number, house_number_addition,
    postal_code, city, region, country_code
  FROM address
  WHERE user_id = ?
  ORDER BY type ASC, id DESC
");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll();

$wishlistCount = 0;
try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
  $stmt->execute([$userId]);
  $wishlistCount = (int)($stmt->fetchColumn() ?? 0);
} catch (Throwable $e) {
  $wishlistCount = 0;
}

$cartItemsCount = 0;
$cartQtyTotal = 0;

try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
  $stmt->execute([$userId]);
  $cartItemsCount = (int)($stmt->fetchColumn() ?? 0);

  $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
  $stmt->execute([$userId]);
  $cartQtyTotal = (int)($stmt->fetchColumn() ?? 0);
} catch (Throwable $e) {
  $cartItemsCount = 0;
  $cartQtyTotal = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile - Book Webshop</title>

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>
<body>

<?php include __DIR__ . "/includes/header.php"; ?>

<main>
  <div class="container profile-layout">

    <aside class="profile-sidebar">
      <h2>Your Profile</h2>
      <nav class="profile-menu">
        <a href="<?= APP_URL ?>profile.php">Profile Info</a>
        <a href="<?= APP_URL ?>wishlist.php">Wishlist</a>
        <a href="<?= APP_URL ?>classes/cart.php">Shopping Cart</a>
        <a href="<?= APP_URL ?>classes/order.php">Order History</a>

        <form method="post" action="<?= APP_URL ?>logout.php">
          <button type="submit" class="btn-secondary profile-logout-btn">Logout</button>
        </form>
      </nav>
    </aside>

    <section class="profile-content">
      <h1>Profile Information</h1>

      <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
      <?php endif; ?>

      <?php if ($success): ?>
        <p class="success"><?= h($success) ?></p>
      <?php endif; ?>

      <div class="profile-box">
        <h2>My contact details</h2>

        <div class="profile-row">
          <span>Name</span>
          <strong><?= h($user['name']) ?></strong>
        </div>

        <div class="profile-row">
          <span>Email</span>
          <strong><?= h($user['email']) ?></strong>
        </div>

        <div class="profile-row">
          <span>Units balance</span>
          <strong><?= (int)$userUnits ?> units</strong>
        </div>

        <a href="#" class="profile-edit">Edit Profile</a>
      </div>

      <div class="profile-box">
        <h2>My shopping</h2>

        <div class="profile-row">
          <span>Wishlist</span>
          <strong><?= (int)$wishlistCount ?> item(s)</strong>
        </div>

        <div class="profile-row">
          <span>Cart</span>
          <strong><?= (int)$cartQtyTotal ?> item(s)</strong>
        </div>

        <div>
          <a class="btn-secondary" href="<?= APP_URL ?>wishlist.php">Open wishlist</a>
          <a class="btn-secondary" href="<?= APP_URL ?>classes/cart.php">Open cart</a>
        </div>
      </div>

      <div class="profile-box">
        <h2>Change password</h2>
        <p>To change your password, please click the button below.</p>
        <a href="<?= APP_URL ?>change_password.php" class="btn-primary">Change Password</a>
      </div>

      <div class="profile-box">
        <div class="profile-box-head">
          <h2>My address(es)</h2>
          <a class="btn-secondary" href="<?= APP_URL ?>address_form.php">Add address</a>
        </div>

        <?php if (empty($addresses)): ?>
          <p class="profile-muted">No addresses yet.</p>
        <?php else: ?>
          <div class="profile-address-list">
            <?php foreach ($addresses as $a): ?>
              <div class="profile-address-card">
                <div class="profile-address-top">

                  <div class="profile-address-text">
                    <strong class="profile-address-type">
                      <?= h($a['type']) ?> address
                    </strong>

                    <div class="profile-address-lines">
                      <?= h($a['full_name']) ?><br>
                      <?= h($a['street']) ?> <?= h($a['house_number']) ?>
                      <?= h($a['house_number_addition']) ?><br>
                      <?= h($a['postal_code']) ?> <?= h($a['city']) ?>
                      <?= $a['region'] ? " - " . h($a['region']) : "" ?><br>
                      <?= h($a['country_code']) ?>
                      <?= $a['phone'] ? " · " . h($a['phone']) : "" ?>
                    </div>
                  </div>

                  <div class="profile-address-actions">
                    <a class="btn-secondary" href="<?= APP_URL ?>address_form.php?id=<?= (int)$a['id'] ?>">Edit</a>

                    <form method="post">
                      <input type="hidden" name="action" value="delete_address">
                      <input type="hidden" name="address_id" value="<?= (int)$a['id'] ?>">
                      <button type="submit" class="btn-secondary" onclick="return confirm('Delete this address?')">
                        Delete
                      </button>
                    </form>
                  </div>

                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </section>

  </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>

</body>
</html>
