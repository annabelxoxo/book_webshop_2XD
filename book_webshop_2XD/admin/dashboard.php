<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function asset_url(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^(https?://|/)~i', $path)) return $path;
  return APP_URL . ltrim($path, '/');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/dashboard.php"));
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

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css" />
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container admin-shell">

    <section class="admin-head">
      <div>
        <h2>Admin dashboard</h2>
        <p class="admin-sub">Manage your store content.</p>
      </div>

      <div class="admin-actions">
        <a class="btn-secondary" href="<?= APP_URL ?>catalog.php">View store</a>

        <form method="post" action="<?= APP_URL ?>logout.php">
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
        <a class="admin-quick-card" href="<?= APP_URL ?>admin/books.php">
          <strong>Manage books</strong>
          <span>Add/edit/remove books</span>
        </a>

        <a class="admin-quick-card" href="<?= APP_URL ?>admin/authors.php">
          <strong>Manage authors</strong>
          <span>Add/edit authors</span>
        </a>

        <a class="admin-quick-card" href="<?= APP_URL ?>admin/genres.php">
          <strong>Manage genres</strong>
          <span>Edit genre list</span>
        </a>

        <a class="admin-quick-card" href="<?= APP_URL ?>admin/users.php">
          <strong>Manage users</strong>
          <span>View user accounts</span>
        </a>

        <a class="admin-quick-card" href="<?= APP_URL ?>admin/orders.php">
          <strong>Manage orders</strong>
          <span>View and process orders</span>
        </a>
      </div>
    </section>

    <section class="admin-recent">
      <div class="admin-recent-head">
        <h3>Recently added books</h3>
        <a class="btn-secondary" href="<?= APP_URL ?>admin/books.php">Open books</a>
      </div>

      <?php if (empty($recentBooks)): ?>
        <p style="color:#666;">No recent books found.</p>
      <?php else: ?>
        <div class="admin-recent-grid">
          <?php foreach ($recentBooks as $b): ?>
            <?php
              $units = (int)round(((float)$b["price"]) * 10);
              $coverUrl = asset_url((string)($b["cover_image"] ?? ""));
            ?>
            <article class="admin-book-card">
              <a class="admin-book-link" href="<?= APP_URL ?>product.php?id=<?= (int)$b["id"] ?>">
                <div class="admin-book-img">
                  <?php if ($coverUrl): ?>
                    <img src="<?= h($coverUrl) ?>" alt="<?= h($b["title"]) ?>">
                  <?php endif; ?>
                </div>
                <div class="admin-book-info">
                  <h4><?= h(ucwords((string)$b["title"])) ?></h4>
                  <p class="admin-book-author"><?= h($b["author_name"]) ?></p>
                  <p class="admin-book-price">
                    €<?= number_format((float)$b["price"], 2, ",", ".") ?>
                    <span>(<?= $units ?> units)</span>
                  </p>
                </div>
              </a>

              <div class="admin-book-actions">
                <a class="btn-secondary" href="<?= APP_URL ?>admin/book_edit.php?id=<?= (int)$b["id"] ?>">Edit</a>
                <a class="btn-secondary" href="<?= APP_URL ?>admin/book_delete.php?id=<?= (int)$b["id"] ?>">Delete</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
