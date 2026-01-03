<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


$stmt = $pdo->query("
  SELECT
    b.id,
    b.title,
    b.price,
    b.cover_image,
    a.name AS author,
    g.name AS genre
  FROM book b
  JOIN author a ON a.id = b.author_id
  LEFT JOIN book_genre bg ON bg.book_id = b.id
  LEFT JOIN genre g ON g.id = bg.genre_id
  ORDER BY g.name ASC, b.title ASC
");

$rows = $stmt->fetchAll();


$byGenre = [];

foreach ($rows as $book) {
  $genreName = $book['genre'] ?: 'Other';
  $byGenre[$genreName][] = $book;
}

foreach ($byGenre as $g => $list) {
  $byGenre[$g] = array_slice($list, 0, 5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bestsellers - Book Webshop</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
<?php include 'includes/header.php'; ?>

<main>
  <section class="catalog-hero">
    <h2>Bestsellers</h2>
    <p>Top 5 books per genre.</p>
  </section>

  <div class="container">
    <?php if (!$byGenre): ?>
      <p class="empty-state">No books found.</p>
    <?php endif; ?>

    <?php foreach ($byGenre as $genreName => $list): ?>
      <section class="bestseller-genre">
        <div class="bestseller-genre-head">
          <h3><?= htmlspecialchars($genreName) ?></h3>
          <span class="units-note">1€ = 10 units</span>
        </div>

        <div class="bestseller-row">
          <?php $rank = 1; ?>
          <?php foreach ($list as $book): ?>
            <?php $units = (int)round(((float)$book['price']) * 10); ?>

            <article class="bestseller-item">
              <div class="bestseller-rank"><?= $rank++ ?></div>

              <a class="bestseller-cover" href="product.php?id=<?= (int)$book['id'] ?>">
                <img
                  src="<?= htmlspecialchars($book['cover_image']) ?>"
                  alt="<?= htmlspecialchars($book['title']) ?>"
                />
              </a>

              <div class="bestseller-meta">
                <h4 class="bestseller-title"><?= htmlspecialchars($book['title']) ?></h4>
                <p class="bestseller-author"><?= htmlspecialchars($book['author']) ?></p>

                <p class="bestseller-price">
                  €<?= number_format((float)$book['price'], 2, ',', '.') ?>
                  <span class="bestseller-units">(<?= $units ?> units)</span>
                </p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
