<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user_id'])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

$userId = (int)$_SESSION['user_id'];

$success = "";
$error = "";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("SELECT id, name, email FROM user WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT units FROM user WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$userUnits = (int)($stmt->fetchColumn() ?? 0);


if (!$user) {
  session_destroy();
  header("Location: book_webshop_2XD/login.php");
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  if ($action === "delete_address") {
    $addressId = (int)($_POST["address_id"] ?? 0);

    if ($addressId > 0) {
      $del = $pdo->prepare("DELETE FROM address WHERE id = ? AND user_id = ?");
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
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
  <div class="container profile-layout">

    <aside class="profile-sidebar">
        <h2>Your Profile</h2>
            <nav class="profile-menu">
            <a href="book_webshop_2XD/profile.php">Profile Info</a>
            <a href="book_webshop_2XD/wishlist.php">Wishlist</a>
            <a href="book_webshop_2XD/classes/cart.php">Shopping Cart</a>
            <a href="book_webshop_2XD/classes/order.php">Order History</a>

            <form method="post" action="book_webshop_2XD/logout.php">
                <button type="submit" class="btn-secondary profile-logout-btn">Logout</button>
            </form>
            </nav>
    </aside>

    <section class="profile-content">
        <h1>Profile Information</h1>

        <?php if ($error): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
          <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <div class="profile-box">
            <h2>my contact details</h2>

        <div class="profile-row">
            <span> Name</span>
            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
        </div>

        <div class="profile-row">
            <span>Email</span>
            <strong><?php echo htmlspecialchars($user['email']); ?></strong>
        </div>

        <div class="profile-row">
            <span>units balance</span>
            <strong><?= (int)$userUnits ?> units</strong>
        </div>
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
          <a class="btn-secondary" href="book_webshop_2XD/wishlist.php">Open wishlist</a>
          <a class="btn-secondary" href="book_webshop_2XD/classes/cart.php">Open cart</a>
        </div>
      </div>
    <div class="profile-box">
        <h2>change password</h2>
        <p>
            To change your password, please click the button below.
        </p>

        <a href="book_webshop_2XD/change_password.php" class="btn-primary">Change Password</a>
    </div>

  <div class="profile-box">
          <div class="profile-box-head">
    <h2>my address(es)</h2>
    <a class="btn-secondary" href="book_webshop_2XD/address_form.php">Add address</a>
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
                        <?= htmlspecialchars($a['type']) ?> address
                      </strong>

              <div class="profile-address-lines">
                <?= htmlspecialchars($a['full_name']) ?><br>
                <?= htmlspecialchars($a['street']) ?> <?= htmlspecialchars($a['house_number']) ?>
                <?= htmlspecialchars($a['house_number_addition']) ?><br>
                <?= htmlspecialchars($a['postal_code']) ?> <?= htmlspecialchars($a['city']) ?>
                <?= $a['region'] ? " - " . htmlspecialchars($a['region']) : "" ?><br>
                <?= htmlspecialchars($a['country_code']) ?>
                <?= $a['phone'] ? " · " . htmlspecialchars($a['phone']) : "" ?>
              </div>
            </div>

                <div class="profile-address-actions">
                      <a class="btn-secondary" href="book_webshop_2XD/address_form.php?id=<?= (int)$a['id'] ?>">Edit</a>

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


<?php include 'includes/footer.php'; ?>

</body>
</html>
