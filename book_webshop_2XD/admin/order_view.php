<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$orderId = (int)($_GET["id"] ?? 0);
if ($orderId < 1) {
  header("Location: /book_webshop_2XD/admin/orders.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order #<?= (int)$orderId ?> - Admin</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container admin-order-view">

    <section class="admin-head">
      <div>
        <h2>Order #<?= (int)$order["id"] ?></h2>
        <p class="admin-sub">
          <?= h($order["user_email"]) ?> · <?= h($order["created_at"]) ?>
        </p>
      </div>

      <a class="btn-secondary" href="book_webshop_2XD/admin/orders.php">
        ← Back to orders
      </a>
    </section>

    <div class="admin-order-layout">

      <!-- LEFT -->
      <div>

        <div class="admin-order-card">
          <h3>Order summary</h3>

          <div class="admin-order-row">
            <span>Status</span>
            <span class="admin-order-status"><?= h($order["status"]) ?></span>
          </div>

          <div class="admin-order-row">
            <span>Subtotal</span>
            <strong><?= (int)$order["subtotal"] ?> units</strong>
          </div>

          <div class="admin-order-row">
            <span>Discount</span>
            <strong><?= (int)$order["discount_total"] ?> units</strong>
          </div>

          <div class="admin-order-row admin-order-total-final">
            <span>Total</span>
            <strong><?= (int)$order["grand_total"] ?> units</strong>
          </div>
        </div>

      </div>

      <!-- RIGHT -->
      <div>

        <div class="admin-order-card">
          <h3>Items</h3>

          <div class="admin-order-items">

            <?php foreach ($items as $it): ?>
              <div class="admin-order-item">

                <img src="<?= h($it["cover_image"]) ?>" alt="">

                <div class="admin-order-item-info">
                  <h4><?= h($it["title"]) ?></h4>
                  <p><?= h($it["author_name"]) ?></p>
                </div>

                <div class="admin-order-item-meta">
                  <strong><?= (int)round($it["price"] * 10) ?> units</strong>
                  Qty: <?= (int)$it["quantity"] ?>
                </div>

              </div>
            <?php endforeach; ?>

          </div>
        </div>

      </div>

    </div>

  </div>
</main>



<?php include __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
