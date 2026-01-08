<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$error = "";
$success = "";


$items = [];
try {
  $stmt = $pdo->prepare("
    SELECT
      c.book_id,
      c.quantity,
      b.title,
      b.price,
      b.cover_image,
      a.name AS author_name
    FROM cart c
    JOIN book b ON b.id = c.book_id
    JOIN author a ON a.id = b.author_id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC, c.book_id DESC
  ");
  $stmt->execute([$userId]);
  $items = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load cart: " . $e->getMessage();
}


$totalQty = 0;
$subtotal = 0.0;
$totalUnits = 0;

foreach ($items as $it) {
  $qty = (int)$it["quantity"];
  $price = (float)$it["price"];

  $totalQty += $qty;
  $subtotal += $price * $qty;

  $unitsEach = (int)round($price * 10);
  $totalUnits += $unitsEach * $qty;
}

$shippingCost  = 0.0;
$taxAmount     = 0.0;
$discountTotal = 0.0;
$grandTotal    = $subtotal;

$userUnits = 0;
try {
  $stmt = $pdo->prepare("SELECT units FROM user WHERE id = ? LIMIT 1");
  $stmt->execute([$userId]);
  $userUnits = (int)($stmt->fetchColumn() ?? 0);
} catch (Throwable $e) {
  $error = "Could not load user units: " . $e->getMessage();
}


$addresses = [];
try {
  $stmt = $pdo->prepare("
    SELECT id, full_name, street, house_number, house_number_addition, postal_code, city
    FROM address
    WHERE user_id = ? AND type = 'shipping'
    ORDER BY id DESC
  ");
  $stmt->execute([$userId]);
  $addresses = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load addresses: " . $e->getMessage();
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "place_order") {

  $shippingAddressId = (int)($_POST["shipping_address_id"] ?? 0);

  if (empty($items)) {
    $error = "Your cart is empty.";
  } elseif ($totalUnits <= 0) {
    $error = "Invalid total.";
  } elseif ($shippingAddressId < 1) {
    $error = "Please choose a shipping address.";
  } elseif ($userUnits < $totalUnits) {
    $error = "Not enough units. You have {$userUnits} units, you need {$totalUnits} units.";
  } else {

    try {
      $pdo->beginTransaction();


      $chkAddr = $pdo->prepare("SELECT id FROM address WHERE id = ? AND user_id = ? AND type='shipping' LIMIT 1");
      $chkAddr->execute([$shippingAddressId, $userId]);
      if (!$chkAddr->fetchColumn()) {
        throw new Exception("Invalid shipping address.");
      }

      $upd = $pdo->prepare("
        UPDATE user
        SET units = units - ?
        WHERE id = ? AND units >= ?
      ");
      $upd->execute([$totalUnits, $userId, $totalUnits]);

      if ($upd->rowCount() === 0) {
        throw new Exception("Not enough units (race condition).");
      }

  
      $insOrder = $pdo->prepare("
        INSERT INTO `order`
          (user_id, status, currency, subtotal, shipping_cost, tax_amount, discount_total, grand_total, created_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, NOW())
      ");

      $status = "paid";      
      $currency = "UNITS";   

      $insOrder->execute([
        $userId,
        $status,
        $currency,
        $subtotal,
        $shippingCost,
        $taxAmount,
        $discountTotal,
        $grandTotal
      ]);

      $orderId = (int)$pdo->lastInsertId();

      $insItem = $pdo->prepare("
        INSERT INTO order_book (order_id, book_id, quantity, price)
        VALUES (?, ?, ?, ?)
      ");

      foreach ($items as $it) {
        $insItem->execute([
          $orderId,
          (int)$it["book_id"],
          (int)$it["quantity"],
          (float)$it["price"]
        ]);
      }

      $clr = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
      $clr->execute([$userId]);

      $pdo->commit();

      header("Location: /book_webshop_2XD/checkout_success.php?id=" . $orderId);
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Could not place order: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include __DIR__ . "/includes/header.php"; ?>

<main>
  <div class="container">

    <section class="cart-head">
      <div>
        <h2>Checkout</h2>
        <p class="cart-sub">Pay with units and place your order.</p>
      </div>
      <a class="btn-secondary" href="book_webshop_2XD/classes/cart.php">← Back to cart</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if (empty($items)): ?>
      <div class="cart-empty">
        <p>Your cart is empty.</p>
        <a class="btn-primary" href="book_webshop_2XD/catalog.php">Browse catalog</a>
      </div>
    <?php else: ?>

      <div class="cart-layout">

        <section class="cart-items">
          <?php foreach ($items as $it): ?>
            <?php
              $qty = (int)$it["quantity"];
              $price = (float)$it["price"];
              $line = $price * $qty;
              $unitsEach = (int)round($price * 10);
              $unitsLine = $unitsEach * $qty;
            ?>
            <article class="cart-card">
              <a class="cart-card-link" href="book_webshop_2XD/product.php?id=<?= (int)$it["book_id"] ?>">
                <div class="cart-img">
                  <img src="<?= h($it["cover_image"]) ?>" alt="<?= h($it["title"]) ?>">
                </div>
                <div class="cart-info">
                  <h3><?= h(ucwords($it["title"])) ?></h3>
                  <p class="cart-author"><?= h($it["author_name"]) ?></p>
                  <p class="cart-meta">Qty: <strong><?= $qty ?></strong></p>

                  <p class="cart-price">
                    €<?= number_format($price, 2, ",", ".") ?>
                    <span>(<?= $unitsEach ?> units)</span>
                  </p>

                  
                </div>
              </a>
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
            <span>Total units</span>
            <strong><?= (int)$totalUnits ?> units</strong>
          </div>

          <div class="cart-summary-row cart-summary-sub">
            <span>Your units</span>
            <strong><?= (int)$userUnits ?> units</strong>
          </div>

          <hr>

          <h3>Shipping address</h3>

          <?php if (empty($addresses)): ?>
            <p>No shipping address found.</p>
            <a class="btn-secondary" href="book_webshop_2XD/address_form.php">Add shipping address</a>
          <?php else: ?>
            
            <form method="post">
                <input type="hidden" name="action" value="place_order">

                <label>Choose address</label>
                 <select name="shipping_address_id" required>
                    <option value="">-- choose --</option>
                    <?php foreach ($addresses as $a): ?>
                    <option value="<?= (int)$a["id"] ?>">
                    <?= h($a["full_name"]) ?> —
                    <?= h($a["street"]) ?> <?= h($a["house_number"]) ?>
                    <?= h($a["house_number_addition"]) ?>,
                    <?= h($a["postal_code"]) ?> <?= h($a["city"]) ?>
                    </option>
                <?php endforeach; ?>
                </select>

                <button class="btn-primary checkout-pay" type="submit">
                Pay with units
                </button>

                <p class="checkout-units-note">
                1€ = 10 units (units-only checkout)
                </p>
            </form>


          <?php endif; ?>

        </aside>

      </div>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
