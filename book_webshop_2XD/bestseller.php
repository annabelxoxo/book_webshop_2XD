<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

function asset_url(string $path): string {
  $path = trim($path);
  if ($path === '') return '';
  if (preg_match('~^(https?://|/)~i', $path)) return $path;
  return APP_URL . ltrim($path, '/');
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

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>

<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

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
          <h3><?= h($genreName) ?></h3>
          <span class="units-note">1€ = 10 units</span>
        </div>

        <div class="bestseller-row">
          <?php $rank = 1; ?>
          <?php foreach ($list as $book): ?>
            <?php
              $units = (int)round(((float)$book['price']) * 10);
              $coverUrl = asset_url((string)($book['cover_image'] ?? ''));
            ?>

            <article class="bestseller-item">
              <div class="bestseller-rank"><?= $rank++ ?></div>

              <a class="bestseller-cover" href="<?= APP_URL ?>product.php?id=<?= (int)$book['id'] ?>">
                <?php if ($coverUrl): ?>
                  <img
                    src="<?= h($coverUrl) ?>"
                    alt="<?= h($book['title']) ?>"
                  />
                <?php endif; ?>
              </a>

              <div class="bestseller-meta">
                <h4 class="bestseller-title"><?= h($book['title']) ?></h4>
                <p class="bestseller-author"><?= h($book['author']) ?></p>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
