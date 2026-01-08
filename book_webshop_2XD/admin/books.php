<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

$q = trim((string)($_GET["q"] ?? ""));
$authorId = (int)($_GET["author_id"] ?? 0);

$success = "";
$error = "";


$authors = [];
try {
  $aStmt = $pdo->query("SELECT id, name FROM author ORDER BY name ASC");
  $authors = $aStmt->fetchAll();
} catch (Throwable $e) {
  $authors = [];
}


$sql = "
  SELECT b.id, b.title, b.price, b.cover_image, a.name AS author_name
  FROM book b
  JOIN author a ON a.id = b.author_id
  WHERE 1=1
";
$params = [];

if ($q !== "") {
  $sql .= " AND (b.title LIKE ? OR a.name LIKE ?)";
  $like = "%" . $q . "%";
  $params[] = $like;
  $params[] = $like;
}

if ($authorId > 0) {
  $sql .= " AND b.author_id = ?";
  $params[] = $authorId;
}

$sql .= " ORDER BY b.id DESC";

$books = [];
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $books = $stmt->fetchAll();
} catch (Throwable $e) {
  $books = [];
  $error = "Could not load books.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Books</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="cart-head" >
      <div>
        <h2>Manage books</h2>
        <p class="cart-sub">Search, add, edit or delete books.</p>
      </div>

      <div>
        <a class="btn-secondary" href="book_webshop_2XD/admin/dashboard.php">← Dashboard</a>
        <a class="btn-primary" href="book_webshop_2XD/admin/book_create.php">+ Add book</a>
      </div>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="get" class="catalog-form">
      <div class="control">
        <label>Search</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Title or author..." />
      </div>

      <div class="control">
        <label>Author</label>
        <select name="author_id">
          <option value="0">All authors</option>
          <?php foreach ($authors as $a): ?>
            <option value="<?= (int)$a["id"] ?>" <?= $authorId === (int)$a["id"] ? "selected" : "" ?>>
              <?= htmlspecialchars($a["name"]) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="control-btns">
        <button class="btn-primary" type="submit">Filter</button>
        <a class="btn-secondary" href="book_webshop_2XD/admin/books.php">Reset</a>
      </div>
    </form>

    <?php if (empty($books)): ?>
      <div class="empty-state">
        <p>No books found.</p>
      </div>
    <?php else: ?>


      <div class="catalog-grid">
        <?php foreach ($books as $b): ?>
          <?php $units = (int)round(((float)$b["price"]) * 10); ?>
          <article class="catalog-card">
            <a class="catalog-card-link" href="book_webshop_2XD/product.php?id=<?= (int)$b["id"] ?>">
              <div class="catalog-img">
                <img src="<?= htmlspecialchars($b["cover_image"]) ?>" alt="<?= htmlspecialchars($b["title"]) ?>">
              </div>
              <div class="catalog-info">
                <h3><?= htmlspecialchars(ucwords($b["title"])) ?></h3>
                <p class="catalog-author"><?= htmlspecialchars($b["author_name"]) ?></p>
                <p class="catalog-price">
                  €<?= number_format((float)$b["price"], 2, ",", ".") ?>
                  <span class="catalog-units">(<?= $units ?> units)</span>
                </p>
                <span class="catalog-cta">Open product →</span>
              </div>
            </a>

            <div class="wishlist-actions">
              <a class="btn-secondary" href="book_webshop_2XD/admin/book_edit.php?id=<?= (int)$b["id"] ?>">
                Edit
              </a>

              <a class="btn-secondary"
                 href="book_webshop_2XD/admin/book_delete.php?id=<?= (int)$b["id"] ?>"
                 onclick="return confirm('Delete this book?')">
                Delete
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . "/../includes/footer.php"; ?>

</body>
</html>
