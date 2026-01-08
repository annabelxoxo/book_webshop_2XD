<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if(!isset($_SESSION["user_id"])){
    header("Location: /book_webshop_2XD/login.php");
    exit;
}
$userId = (int)$_SESSION["user_id"];


$success = "";
$error = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);

try {

  if ($action === 'remove' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmt->execute([$userId, $id]);
    $success = "Book removed from cart.";
  }

  if ($action === 'clear') {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $success = "Cart cleared.";
  }

  if ($action === 'update_qty' && $id > 0) {
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    
  $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ? LIMIT 1");
    $stmt->execute([$qty, $userId, $id]);
    $success = "Quantity updated.";
  }

  if ($action === 'move_to_wishlist' && $id > 0) {
    $pdo->beginTransaction();
    $del = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ? LIMIT 1");
    $del->execute([$userId, $id]);

    $ins = $pdo->prepare("
      INSERT INTO wishlist (user_id, book_id, added_at)
      VALUES (?, ?, NOW())
      ON DUPLICATE KEY UPDATE added_at = NOW()
    ");

    $ins->execute([$userId, $id]);

    $pdo->commit();
      $success = "Moved to wishlist.";
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = "Something went wrong: " . $e->getMessage();
  
  }
}

$items = [];
try {
  $stmt = $pdo->prepare("
    SELECT c.book_id AS id,
    c.quantity,
    b.title,
    b.price,
    b.cover_image,
    a.name AS author_name
    FROM cart c
    JOIN book b ON b.id = c.book_id
    JOIN author a ON a.id = b.author_id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC, c.book_id DESC");
  $stmt->execute([$userId]);
  $items = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load cart items: " . $e->getMessage();
}


$totalEuro = 0.0;
$totalUnits = 0;
$totalQty = 0;


foreach ($items as $it) {

  $qty = (int)$it['quantity'];
  $price = (float)$it['price'];

  $totalQty += $qty;
  $totalEuro += $price * $qty;
  $totalUnits += (int)round($price * 10) * $qty;

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cart - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
  <div class="container">

    <section class="cart-head">
      <div>
        <h2>Shopping cart</h2>
        <p class="cart-sub">Review your items before checkout.</p>
      </div>

      <?php if (!empty($items)): ?>
        <form method="post">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="btn-secondary">Clear cart</button>
        </form>
      <?php endif; ?>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (empty($items)): ?>
      <div class="cart-empty">
        <p>Your cart is empty.</p>
        <a class="btn-primary" href="book_webshop_2XD/catalog.php">Browse catalog</a>
      </div>
    <?php else: ?>

      <div class="cart-layout">

        <section class="cart-items">
          <?php foreach ($items as $b): ?>
            <?php
              $id = (int)$b['id'];
              $qty = (int)$b['quantity'];

              $unitsEach = (int)round(((float)$b['price']) * 10);
              $lineEuro = ((float)$b['price']) * $qty;
              $lineUnits = $unitsEach * $qty;
            ?>
            <article class="cart-card">
              <a class="cart-card-link" href="book_webshop_2XD/product.php?id=<?= $id ?>">
                <div class="cart-img">
                  <img src="<?= htmlspecialchars($b['cover_image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
                </div>

                <div class="cart-info">
                  <h3><?= htmlspecialchars(ucwords($b['title'])) ?></h3>
                  <p class="cart-author"><?= htmlspecialchars($b['author_name']) ?></p>
                  <p class="cart-meta">Paperback | English</p>

                  <p class="cart-price">
                    €<?= number_format((float)$b['price'], 2, ',', '.') ?>
                    <span>(<?= $unitsEach ?> units)</span>
                  </p>
                </div>
              </a>

              <div class="cart-actions">
                <form method="post" class="cart-qty-form">
                  <input type="hidden" name="action" value="update_qty">
                  <input type="hidden" name="id" value="<?= $id ?>">

                  <label for="qty-<?= $id ?>">Qty</label>
                  <input id="qty-<?= $id ?>" type="number" name="qty" min="1" value="<?= $qty ?>">
                  <button type="submit" class="btn-secondary">Update</button>
                </form>

                <div class="cart-line-total">
                  <div class="cart-line-euro">€<?= number_format($lineEuro, 2, ',', '.') ?></div>
                  <div class="cart-line-units"><?= (int)$lineUnits ?> units</div>
                </div>

                <div class="cart-btn-row">
                  <form method="post">
                    <input type="hidden" name="action" value="move_to_wishlist">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn-secondary">Move to wishlist</button>
                  </form>

                  <form method="post">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn-secondary">Remove</button>
                  </form>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </section>

        <aside class="cart-summary">
          <h3>Order summary</h3>

          <div class="cart-summary-row">
            <span>Items</span>
            <strong><?= (int)$totalQty ?></strong>
          </div>

          <div class="cart-summary-row">
            <span>Total</span>
            <strong>€<?= number_format($totalEuro, 2, ',', '.') ?></strong>
          </div>

          <div class="cart-summary-row cart-summary-sub">
            <span>Units</span>
            <strong><?= (int)$totalUnits ?> units</strong>
          </div>

          <p class="cart-rate">1€ = 10 units</p>

          <button class="btn-primary cart-checkout" type="button" disabled>
            Checkout (coming soon)
          </button>

          <a class="btn-secondary cart-back" href="catalog.php">← Continue shopping</a>
        </aside>

      </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>