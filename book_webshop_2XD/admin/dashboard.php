<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function safeCount(PDO $pdo, string $sql): int {
  try {
    $stmt = $pdo->query($sql);
    return (int)($stmt->fetchColumn() ?? 0);
  } catch (Throwable $e) {
    return 0; 
  }
}


$totalBooks   = safeCount($pdo, "SELECT COUNT(*) FROM book");
$totalAuthors = safeCount($pdo, "SELECT COUNT(*) FROM author");
$totalUsers   = safeCount($pdo, "SELECT COUNT(*) FROM user");


$recentBooks = [];
try {
  $stmt = $pdo->query("
    SELECT b.id, b.title, b.price, b.cover_image, a.name AS author_name
    FROM book b
    JOIN author a ON a.id = b.author_id
    ORDER BY b.id DESC
    LIMIT 6
  ");
  $recentBooks = $stmt->fetchAll();
} catch (Throwable $e) {
  $recentBooks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container admin-shell">

    <section class="admin-head">
      <div>
        <h2>Admin dashboard</h2>
        <p class="admin-sub">Manage your store content.</p>
      </div>

      <div class="admin-actions">
        <a class="btn-secondary" href="book_webshop_2XD/catalog.php">View store</a>
        <form method="post" action="book_webshop_2XD/logout.php" >
          <button type="submit" class="btn-secondary">Logout</button>
        </form>
      </div>
    </section>

    <section class="admin-stats">
      <div class="admin-stat">
        <div class="admin-stat-label">Books</div>
        <div class="admin-stat-value"><?= (int)$totalBooks ?></div>
      </div>

      <div class="admin-stat">
        <div class="admin-stat-label">Authors</div>
        <div class="admin-stat-value"><?= (int)$totalAuthors ?></div>
      </div>

      <div class="admin-stat">
        <div class="admin-stat-label">Users</div>
        <div class="admin-stat-value"><?= (int)$totalUsers ?></div>
      </div>
    </section>

    <section class="admin-quick">
      <h3>Quick actions</h3>

      <div class="admin-quick-grid">
        <a class="admin-quick-card" href="book_webshop_2XD/admin/books.php">
          <strong>Manage books</strong>
          <span>Add/edit/remove books</span>
        </a>

        <a class="admin-quick-card" href="book_webshop_2XD/admin/authors.php">
          <strong>Manage authors</strong>
          <span>Add/edit authors</span>
        </a>

        <a class="admin-quick-card" href="book_webshop_2XD/admin/genres.php">
          <strong>Manage genres</strong>
          <span>Edit genre list</span>
        </a>

        <a class="admin-quick-card" href="book_webshop_2XD/admin/users.php">
          <strong>Manage users</strong>
          <span>View user accounts</span>
        </a>

        <a class="admin-quick-card" href="book_webshop_2XD/admin/orders.php">
          <strong>Manage orders</strong>
          <span>View and process orders</span>
        </a>

      </div>


    </section>

    <section class="admin-recent">
      <div class="admin-recent-head">
        <h3>Recently added books</h3>
        <a class="btn-secondary" href="book_webshop_2XD/admin/books.php">Open books</a>
      </div>

      <?php if (empty($recentBooks)): ?>
        <p style="color:#666;">No recent books found.</p>
      <?php else: ?>
        <div class="admin-recent-grid">
          <?php foreach ($recentBooks as $b): ?>
            <?php $units = (int)round(((float)$b["price"]) * 10); ?>
            <article class="admin-book-card">
              <a class="admin-book-link" href="book_webshop_2XD/product.php?id=<?= (int)$b["id"] ?>">
                <div class="admin-book-img">
                  <img src="<?= htmlspecialchars($b["cover_image"]) ?>" alt="<?= htmlspecialchars($b["title"]) ?>">
                </div>
                <div class="admin-book-info">
                  <h4><?= htmlspecialchars(ucwords($b["title"])) ?></h4>
                  <p class="admin-book-author"><?= htmlspecialchars($b["author_name"]) ?></p>
                  <p class="admin-book-price">
                    €<?= number_format((float)$b["price"], 2, ",", ".") ?>
                    <span>(<?= $units ?> units)</span>
                  </p>
                </div>
              </a>

              <div class="admin-book-actions">
                <a class="btn-secondary" href="book_webshop_2XD/admin/book_edit.php?id=<?= (int)$b["id"] ?>">Edit</a>
                <a class="btn-secondary" href="book_webshop_2XD/admin/book_delete.php?id=<?= (int)$b["id"] ?>">Delete</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>
</html>
