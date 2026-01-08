<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["user_id"])) {
  $id = (int)($_GET["id"] ?? 0);
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("checkout_success.php?id=" . $id));
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

// zelfde veilige helper als in checkout.php
function asset_url(string $path): string {
  if ($path === '') return '';
  if (preg_match('~^(https?://|/)~i', $path)) return $path;
  return APP_URL . ltrim($path, '/');
}

$userId = (int)$_SESSION["user_id"];
$orderId = (int)($_GET["id"] ?? 0);

if ($orderId < 1) {
  header("Location: " . APP_URL . "profile.php");
  exit;
}

$error = "";
$order = null;
$items = [];
$paidUnits = 0;

try {
  $stmt = $pdo->prepare("
    SELECT *
    FROM `order`
    WHERE id = ? AND user_id = ?
    LIMIT 1
  ");
  $stmt->execute([$orderId, $userId]);
  $order = $stmt->fetch();

  if (!$order) {
    http_response_code(404);
    die("Order not found.");
  }

  $paidUnits = (int)round(((float)($order["grand_total"] ?? 0)) * 10);

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

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>
<body>

<?php require_once __DIR__ . "/includes/header.php"; ?>

<main>
  <div class="container">

    <section class="cart-head">
      <div>
        <h2>Order confirmed</h2>
        <p class="cart-sub">Thanks! Your order has been placed.</p>
      </div>
      <a class="btn-secondary" href="<?= APP_URL ?>catalog.php">Continue shopping</a>
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
          <strong><?= (int)$paidUnits ?> units</strong>
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
              $unitsEach = (int)round(((float)$it["price"]) * 10);
              $cover = (string)($it["cover_image"] ?? "");
              $coverUrl = asset_url($cover);
            ?>
            <article class="cart-card">
              <a class="cart-card-link" href="<?= APP_URL ?>product.php?id=<?= (int)$it["book_id"] ?>">
                <div class="cart-img">
                  <?php if ($coverUrl): ?>
                    <img src="<?= h($coverUrl) ?>" alt="<?= h($it["title"]) ?>">
                  <?php endif; ?>
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

<?php require_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
