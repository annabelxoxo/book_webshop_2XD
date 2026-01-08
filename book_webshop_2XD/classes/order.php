<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$userId = (int)$_SESSION["user_id"];
$error = "";
$orders = [];

try {
  $stmt = $pdo->prepare("
    SELECT
      o.id,
      o.status,
      o.currency,
      o.grand_total,
      o.created_at
    FROM `order` o
    WHERE o.user_id = ?
    ORDER BY o.id DESC
    LIMIT 200
  ");
  $stmt->execute([$userId]);
  $orders = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load orders: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My orders - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2>My orders</h2>
        <p class="admin-sub">Your order history (paid with units).</p>
      </div>

      <a class="btn-secondary" href="book_webshop_2XD/profile.php">← Back to profile</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
      <div class="cart-empty">
        <p>You have no orders yet.</p>
        <a class="btn-primary" href="book_webshop_2XD/catalog.php">Browse catalog</a>
      </div>
    <?php else: ?>

      <section class="admin-orders-card">
        <h3>Orders (<?= count($orders) ?>)</h3>

        <div class="admin-orders-table-wrap">
          <table class="admin-orders-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>
              <?php foreach ($orders as $o): ?>
                <?php
                  $unitsPaid = (int)round(((float)($o["grand_total"] ?? 0)) * 10);
                ?>
                <tr>
                  <td>#<?= (int)$o["id"] ?></td>

                  <td>
                    <span class="admin-orders-status <?= h($o["status"] ?? "") ?>">
                      <?= h($o["status"] ?? "") ?>
                    </span>
                  </td>

                  <td>
                    <strong><?= $unitsPaid ?> units</strong>
                    <br>
                    <small style="color:#777;">
                      €<?= number_format((float)($o["grand_total"] ?? 0), 2, ",", ".") ?> · <?= h($o["currency"] ?? "UNITS") ?>
                    </small>
                  </td>

                  <td>
                    <small><?= h($o["created_at"] ?? "") ?></small>
                  </td>

                  <td>
                    <a class="btn-secondary" href="book_webshop_2XD/checkout_success.php?id=<?= (int)$o["id"] ?>">
                      View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </section>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
