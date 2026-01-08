<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/orders.php"));
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

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css" />
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2>Orders</h2>
        <p class="admin-sub">Latest 50 orders</p>
      </div>
      <a class="btn-secondary" href="<?= APP_URL ?>admin/dashboard.php">← Back to dashboard</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <section class="profile-box">
      <h3>Order list</h3>

      <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
      <?php else: ?>
        <div>
          <table class="admin-order-table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <?php $unitsPaid = (int)round(((float)($o["grand_total"] ?? 0)) * 10); ?>
                <tr>
                  <td>
                    <strong><?= h($o["user_name"]) ?></strong><br>
                    <small style="color:#777;"><?= h($o["user_email"]) ?></small>
                  </td>
                  <td>
                    <span class="admin-orders-status <?= h($o["status"] ?? "") ?>">
                      <?= h($o["status"] ?? "") ?>
                    </span>
                  </td>
                  <td>
                    <strong><?= $unitsPaid ?> units</strong><br>
                    <small style="color:#777;">
                      €<?= number_format((float)($o["grand_total"] ?? 0), 2, ",", ".") ?>
                    </small>
                  </td>
                  <td>
                    <?= h($o["created_at"] ?? "") ?>
                  </td>
                  <td class="admin-orders-actions">
                    <a class="btn-secondary" href="<?= APP_URL ?>admin/order_view.php?id=<?= (int)$o["id"] ?>">
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

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
