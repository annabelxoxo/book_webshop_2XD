<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/order_view.php?id=" . (int)($_GET["id"] ?? 0)));
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

function asset_url(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^(https?://|/)~i', $path)) return $path;
  return APP_URL . ltrim($path, '/');
}

$orderId = (int)($_GET["id"] ?? 0);
if ($orderId < 1) {
  header("Location: " . APP_URL . "admin/orders.php");
  exit;
}

$error = "";
$order = null;
$items = [];

try {
  $stmt = $pdo->prepare("
    SELECT
      o.*,
      u.name AS user_name,
      u.email AS user_email
    FROM `order` o
    JOIN user u ON u.id = o.user_id
    WHERE o.id = ?
    LIMIT 1
  ");
  $stmt->execute([$orderId]);
  $order = $stmt->fetch();

  if (!$order) {
    http_response_code(404);
    die("Order not found.");
  }

  $stmt = $pdo->prepare("
    SELECT
      ob.book_id,
      ob.quantity,
      ob.price,
      b.title,
      b.cover_image,
      a.name AS author_name
    FROM order_book ob
    JOIN book b ON b.id = ob.book_id
    JOIN author a ON a.id = b.author_id
    WHERE ob.order_id = ?
    ORDER BY ob.book_id ASC
  ");
  $stmt->execute([$orderId]);
  $items = $stmt->fetchAll();

} catch (Throwable $e) {
  $error = "Could not load order: " . $e->getMessage();
}

// units helpers (1€ = 10 units)
// (order bestaat hier sowieso door de die() hierboven, maar we houden het veilig)
$subtotalUnits = (int)round(((float)($order["subtotal"] ?? 0)) * 10);
$discountUnits = (int)round(((float)($order["discount_total"] ?? 0)) * 10);
$grandUnits    = (int)round(((float)($order["grand_total"] ?? 0)) * 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order #<?= (int)$orderId ?> - Admin</title>

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css" />
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container admin-order-view">

    <section class="admin-head">
      <div>
        <h2>Order #<?= (int)$order["id"] ?></h2>
        <p class="admin-sub">
          <?= h($order["user_email"] ?? "") ?> · <?= h($order["created_at"] ?? "") ?>
        </p>
      </div>

      <a class="btn-secondary" href="<?= APP_URL ?>admin/orders.php">← Back to orders</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <div class="admin-order-layout">

      <!-- LEFT -->
      <div>
        <div class="admin-order-card">
          <h3>Order summary</h3>

          <div class="admin-order-row">
            <span>Status</span>
            <span class="admin-order-status"><?= h($order["status"] ?? "") ?></span>
          </div>

          <div class="admin-order-row">
            <span>Subtotal</span>
            <strong><?= $subtotalUnits ?> units</strong>
          </div>

          <div class="admin-order-row">
            <span>Discount</span>
            <strong><?= $discountUnits ?> units</strong>
          </div>

          <div class="admin-order-row admin-order-total-final">
            <span>Total</span>
            <strong><?= $grandUnits ?> units</strong>
          </div>
        </div>
      </div>

      <!-- RIGHT -->
      <div>
        <div class="admin-order-card">
          <h3>Items</h3>

          <div class="admin-order-items">
            <?php foreach ($items as $it): ?>
              <?php
                $unitsEach = (int)round(((float)($it["price"] ?? 0)) * 10);
                $coverUrl = asset_url((string)($it["cover_image"] ?? ""));
              ?>
              <div class="admin-order-item">

                <?php if ($coverUrl): ?>
                  <img src="<?= h($coverUrl) ?>" alt="">
                <?php endif; ?>

                <div class="admin-order-item-info">
                  <h4><?= h($it["title"] ?? "") ?></h4>
                  <p><?= h($it["author_name"] ?? "") ?></p>
                </div>

                <div class="admin-order-item-meta">
                  <strong><?= $unitsEach ?> units</strong>
                  Qty: <?= (int)($it["quantity"] ?? 0) ?>
                </div>

              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
