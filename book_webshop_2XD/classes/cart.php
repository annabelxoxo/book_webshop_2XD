<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = []; 
}
if (!isset($_SESSION['wishlist'])) {
  $_SESSION['wishlist'] = []; 
}

$success = "";
$error = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      unset($_SESSION['cart'][$id]);
      $success = "Book removed from cart.";
    }
  }

  if ($action === 'clear') {
    $_SESSION['cart'] = [];
    $success = "Cart cleared.";
  }

  if ($action === 'update_qty') {
    $id = (int)($_POST['id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    if ($id > 0 && isset($_SESSION['cart'][$id])) {
      $_SESSION['cart'][$id] = $qty;
      $success = "Quantity updated.";
    }
  }


  if ($action === 'move_to_wishlist') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0 && isset($_SESSION['cart'][$id])) {
      unset($_SESSION['cart'][$id]);

      if (!in_array($id, $_SESSION['wishlist'], true)) {
        $_SESSION['wishlist'][] = $id;
      }
      $success = "Moved to wishlist.";
    }
  }
}


$cart = $_SESSION['cart'];               
$cartIds = array_keys($cart);
$books = [];

if (!empty($cartIds)) {
  $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

  $stmt = $pdo->prepare("
    SELECT b.id, b.title, b.price, b.cover_image, a.name AS author_name
    FROM book b
    JOIN author a ON a.id = b.author_id
    WHERE b.id IN ($placeholders)
  ");
  $stmt->execute($cartIds);
  $rows = $stmt->fetchAll();

  $byId = [];
  foreach ($rows as $r) $byId[(int)$r['id']] = $r;


  foreach ($cartIds as $id) {
    if (isset($byId[$id])) $books[] = $byId[$id];
  }
}


$totalEuro = 0.0;
$totalUnits = 0;

foreach ($books as $b) {
  $id = (int)$b['id'];
  $qty = (int)($cart[$id] ?? 1);

  $lineEuro = ((float)$b['price']) * $qty;
  $lineUnits = (int)round(((float)$b['price']) * 10) * $qty;

  $totalEuro += $lineEuro;
  $totalUnits += $lineUnits;
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

      <?php if (!empty($_SESSION['cart'])): ?>
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

    <?php if (empty($books)): ?>
      <div class="cart-empty">
        <p>Your cart is empty.</p>
        <a class="btn-primary" href="book_webshop_2XD/catalog.php">Browse catalog</a>
      </div>
    <?php else: ?>

      <div class="cart-layout">

        <section class="cart-items">
          <?php foreach ($books as $b): ?>
            <?php
              $id = (int)$b['id'];
              $qty = (int)($cart[$id] ?? 1);
              $unitsEach = (int)round(((float)$b['price']) * 10);

              $lineEuro = ((float)$b['price']) * $qty;
              $lineUnits = $unitsEach * $qty;
            ?>
            <article class="cart-card">
              <a class="cart-card-link" href="product.php?id=<?= $id ?>">
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
            <strong><?= array_sum(array_map('intval', $_SESSION['cart'])) ?></strong>
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