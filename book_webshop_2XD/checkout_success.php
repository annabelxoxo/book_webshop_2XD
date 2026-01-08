<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$userId = (int)$_SESSION["user_id"];
$orderId = (int)($_GET["id"] ?? 0);

if ($orderId < 1) {
  header("Location: /book_webshop_2XD/profile.php");
  exit;
}

$error = "";
$order = null;
$items = [];

try {
  $stmt = $pdo->prepare("
    SELECT *
    FROM `order`
    WHERE id = ? AND user_id = ?
    LIMIT 1
  ");
  $stmt->execute([$orderId, $userId]);
  $order = $stmt->fetch();
  $paidUnits = (int)round(((float)$order["grand_total"]) * 10);

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
  <title>Order confirmed - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include __DIR__ . "/includes/header.php"; ?>

<main>
  <div class="container">

    <section class="cart-head">
      <div>
        <h2>Order confirmed</h2>
        <p class="cart-sub">Thanks! Your order has been placed.</p>
      </div>
      <a class="btn-secondary" href="book_webshop_2XD/catalog.php">Continue shopping</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($order): ?>
      <div class="profile-box">
        <h3>Order #<?= (int)$order["id"] ?></h3>

        <div class="profile-row">
             <span>Status</span>
                 <strong><?= h($order["status"] ?? "") ?></strong>
        </div>

        <div class="profile-row">
             <span>Total</span>
                <strong><?= $paidUnits ?> units</strong>
        </div>

<div class="profile-row">
  <span>Date</span>
  <strong><?= h($order["created_at"] ?? "") ?></strong>
</div>
    </div>
<?php endif; ?>


    <div class="profile-box">
      <h3>Items</h3>

      <?php if (empty($items)): ?>
        <p>No items found.</p>
      <?php else: ?>
        <div>
          <?php foreach ($items as $it): ?>
            <?php
              $line = ((float)$it["price"]) * ((int)$it["quantity"]);
              $unitsEach = (int)round(((float)$it["price"]) * 10);
              $unitsLine = $unitsEach * (int)$it["quantity"];
            ?>
            <article class="cart-card">
              <a class="cart-card-link" href="book_webshop_2XD/product.php?id=<?= (int)$it["book_id"] ?>">
                <div class="cart-img">
                  <img src="<?= h($it["cover_image"]) ?>" alt="<?= h($it["title"]) ?>">
                </div>
                <div class="cart-info">
                  <h3><?= h(ucwords($it["title"])) ?></h3>
                  <p class="cart-author"><?= h($it["author_name"]) ?></p>
                  <p class="cart-meta">Qty: <strong><?= (int)$it["quantity"] ?></strong></p>

                  <p class="cart-price">
                    <?= $unitsEach ?> units each
                  </p>

                </div>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
