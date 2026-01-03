<?php
require __DIR__ . "/includes/config.php";

$search = trim($_GET['q'] ?? '');
$genreId = (int)($_GET['genre'] ?? 0);

$sql = "
  SELECT DISTINCT
    b.id, b.title, b.price, b.cover_image,
    a.name AS author
  FROM book b
  JOIN author a ON a.id = b.author_id
  LEFT JOIN book_genre bg ON bg.book_id = b.id
  LEFT JOIN genre g ON g.id = bg.genre_id
  WHERE 1=1
";

$params = [];

if ($search !== '') {
  $sql .= " AND (b.title LIKE ? OR a.name LIKE ?)";
  $like = "%" . $search . "%";
  $params[] = $like;
  $params[] = $like;
}

if ($genreId > 0) {
  $sql .= " AND g.id = ?";
  $params[] = $genreId;
}

$sql .= " ORDER BY b.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$genresStmt = $pdo->query("SELECT id, name FROM genre ORDER BY name ASC");
$genres = $genresStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Catalog - Book Webshop</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
<?php include 'includes/header.php'; ?>

<main>
  <div class="container">

    <section class="catalog-hero">
      <h2>Catalog</h2>
      <p>Search, filter, and discover your next favourite book.</p>
    </section>

    <section class="catalog-controls">
      <form method="get" class="catalog-form">

        <div class="control">
          <label for="genre">Genre</label>
          <select id="genre" name="genre">
            <option value="0">All genres</option>
            <?php foreach ($genres as $g): ?>
              <option value="<?= (int)$g['id'] ?>" <?= $genreId === (int)$g['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="control control-btns">
          <button type="submit" class="btn-primary">Apply</button>
          <a class="btn-secondary" href="catalog.php">Reset</a>
        </div>
      </form>

      <div class="catalog-meta">
        <span><?= count($books) ?> results</span>
        <span class="units-note">1€ = 10 units</span>
      </div>
    </section>

    <section class="catalog-grid">
      <?php if (!$books): ?>
        <p class="empty-state">No books found. Try another search or genre.</p>
      <?php endif; ?>

      <?php foreach ($books as $book): ?>
        <?php $units = (int) round(((float)$book['price']) * 10); ?>

        <article class="catalog-card">
          <a href="product.php?id=<?= (int)$book['id'] ?>" class="catalog-card-link">
            <div class="catalog-img">
              <img
                src="<?= htmlspecialchars($book['cover_image']) ?>"
                alt="<?= htmlspecialchars($book['title']) ?>"
              />
            </div>

            <div class="catalog-info">
              <h3><?= htmlspecialchars(ucwords($book['title'])) ?></h3>
              <p class="catalog-author">by <?= htmlspecialchars($book['author']) ?></p>

              <p class="catalog-price">
                €<?= number_format((float)$book['price'], 2, ',', '.') ?>
                <span class="catalog-units">(<?= $units ?> units)</span>
              </p>

              <span class="catalog-cta">View details →</span>
            </div>
          </a>
        </article>

      <?php endforeach; ?>
    </section>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
