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
  <div class="container">
    <div class="admin-order-view">
    <section class="admin-order-head">
      <div>
        <h2 class="admin-order-title">Order #<?= (int)$orderId ?></h2>
        <?php if ($order): ?>
          <p class="admin-order-sub">
            <?= h($order["user_name"]) ?> · <?= h($order["user_email"]) ?> · <?= h($order["created_at"]) ?>
          </p>
        <?php endif; ?>
      </div>
        <div class="admin-order-back">
      <a class="btn-secondary" href="book_webshop_2XD/admin/orders.php">← Back to orders</a>
        </div>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($order): ?>

      <div class="admin-order-layout">

      <aside class="admin-order-summary">
        <h3>Order summary</h3>

        <div class="admin-order-summary-row">
            <span>Status</span>
            <strong>
                <span class="admin-orders-status <?= h($order["status"]) ?>">
                  <?= h($order["status"]) ?>
                </span>
            </strong>
            </div>
        
        <div class="admin-order-summary-row">
            <span>Currency</span>
            <strong>
                <?= h($order["currency"] ?? "EUR") ?>
            </strong>
        </div>
    
        <div class="admin-order-summary-row">
            <span>Subtotal</span>
            <strong>
                <?= number_format((float)$order["subtotal"], 2, ",", ".") ?>
            </strong>
        </div>
    
        <div class="admin-order-summary-row">
            <span>Shipping</span>
            <strong>
                <?= number_format((float)$order["shipping_cost"], 2, ",", ".") ?>
            </strong>
        </div>
    
        <div class="admin-order-summary-row">
            <span>Tax</span>
            <strong>
                <?= number_format((float)$order["tax_amount"], 2, ",", ".") ?>
            </strong>
        </div>

        <div class="admin-order-summary-row admin-order-summary-total">
            <span><strong>Grand total</strong></span>
          <strong>
            <?= number_format((float)$order["grand_total"], 2, ",", ".") ?>
        </strong>
        </div>
        </aside>
  
          <section class="admin-order-card">
         <h3>Items</h3>
        </section>

        <div class="admin-order-summary-row">
            <span>Discount</span>
            <strong>-
                <?= number_format((float)$order["discount_total"], 2, ",", ".") ?>
            </strong>
        </div>
        <div class="admin-order-summary-row admin-order-summary-total">
        
      <?php if (empty($items)): ?>
        <p>No items found for this order.</p>
      <?php else: ?>
        <div class="admin-order-items">
          <?php foreach ($items as $it): ?>
            <?php
              $line = ((float)$it["price"]) * ((int)$it["quantity"]);
              $unitsEach = (int)round(((float)$it["price"]) * 10);
              $unitsLine = $unitsEach * (int)$it["quantity"];
            ?>
            <article class="admin-order-item-card">
              <a class="admin-order-item-link" href="book_webshop_2XD/product.php?id=<?= (int)$it["book_id"] ?>">
                <div class="admin-order-item-img">
                  <img src="<?= h($it["cover_image"]) ?>" alt="<?= h($it["title"]) ?>">
                </div>

                      <div class="admin-order-item-info">
                        <h4><?= h(ucwords($it["title"])) ?></h4>
                        <p class="admin-order-item-author"><?= h($it["author_name"]) ?></p>

                        <div class="admin-order-item-meta">
                          <span>Qty: <strong><?= (int)$it["quantity"] ?></strong></span>
                        </div>

                        <p class="admin-order-item-price">
                          €<?= number_format((float)$it["price"], 2, ",", ".") ?>
                          <span>(<?= $unitsEach ?> units)</span>
                        </p>

                  <div class="admin-order-item-line">
                    <div class="admin-order-item-line-euro">Line: €<?= number_format($line, 2, ",", ".") ?></div>
                    <div class="admin-order-item-line-units"><?= (int)$unitsLine ?> units</div>
                  </div>
                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
          </section>

  </div>
</main>

<?php endif; ?>
<?php include __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
