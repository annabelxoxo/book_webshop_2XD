<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$error = "";
$orders = [];

try {
  $stmt = $pdo->query("
    SELECT
      o.id,
      o.user_id,
      u.name AS user_name,
      u.email AS user_email,
      o.status,
      o.currency,
      o.subtotal,
      o.shipping_cost,
      o.tax_amount,
      o.discount_total,
      o.grand_total,
      o.created_at
    FROM `order` o
    JOIN user u ON u.id = o.user_id
    ORDER BY o.id DESC
    LIMIT 50
  ");
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
  <title>Orders - Admin</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2>Orders</h2>
        <p class="admin-sub">Latest 50 orders</p>
      </div>
      <a class="btn-secondary" href="book_webshop_2XD/admin/dashboard.php">← Back to dashboard</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <section class="profile-box">
      <h3 >Order list</h3>

      <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
      <?php else: ?>
        <div>
          <table class="admin-order-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td>
                    #<?= (int)$o["id"] ?>
                  </td>
                  <td>
                    <strong><?= h($o["user_name"]) ?></strong><br>
                    <small style="color:#777;"><?= h($o["user_email"]) ?></small>
                  </td>
                  <td>
                     <span class="admin-orders-status <?= h($o["status"]) ?>">
                      <?= h($o["status"]) ?>
                    </span>
                  </td>
                  <td>
                    <?= h($o["currency"] ?? "EUR") ?>
                    <?= number_format((float)$o["grand_total"], 2, ",", ".") ?>
                  </td>
                  <td>
                    <?= h($o["created_at"]) ?>
                  </td>
                 <td class="admin-orders-actions">
                    <a class="btn-secondary" href="book_webshop_2XD/admin/order_view.php?id=<?= (int)$o["id"] ?>">
                     View
                    </a>
                 </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<?php include __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>