<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  die("Book not found.");
}


$stmt = $pdo->prepare("
  SELECT
    b.id,
    b.title,
    b.price,
    b.cover_image,
    b.description,
    b.author_id,
    a.name AS author
  FROM book b
  JOIN author a ON a.id = b.author_id
  WHERE b.id = ?
");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
  http_response_code(404);
  die("Book not found.");
}

$units = (int)round(((float)$book['price']) * 10);


if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];

$success = "";
$error = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add_to_cart') {
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = 0;
    $_SESSION['cart'][$id] += $qty;

    $success = "Added to cart!";
  }

  if ($action === 'add_to_wishlist') {
    if (!in_array($id, $_SESSION['wishlist'], true)) {
      $_SESSION['wishlist'][] = $id;
      $success = "Added to wishlist!";
    } else {
      $success = "This book is already in your wishlist.";
    }
  }
}


$recStmt = $pdo->prepare("
  SELECT b.id, b.title, b.price, b.cover_image, a.name AS author
  FROM book b
  JOIN author a ON a.id = b.author_id
  WHERE b.id <> ?
  ORDER BY (b.author_id = ?) DESC, RAND()
  LIMIT 6
");
$recStmt->execute([$id, (int)$book['author_id']]);
$recs = $recStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($book['title']) ?> - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>

<body>
<?php include 'includes/header.php'; ?>

<main>
  <div class="container">

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <section class="product-shell">

      <!-- LEFT -->
      <div class="product-media">

        <a class="back-to-catalog" href="book_webshop_2XD/catalog.php">← Back to catalog</a>

        <div class="product-cover">
          <img
            src="<?= htmlspecialchars($book['cover_image']) ?>"
            alt="<?= htmlspecialchars($book['title']) ?>"
          />
        </div>

        <?php if (!empty($book['description'])): ?>
          <div class="product-description">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($book['description'])) ?></p>
          </div>
        <?php endif; ?>

      </div>

      <!-- RIGHT -->
      <div class="product-content">

        <div class="product-head">
          <h1 class="product-title"><?= htmlspecialchars(ucwords($book['title'])) ?></h1>
          <p class="product-author">by <?= htmlspecialchars($book['author']) ?></p>
          <p class="product-meta">Paperback · English</p>
        </div>

        <div class="product-buybox">
          <div class="product-price-row">
            <div class="product-price">
              €<?= number_format((float)$book['price'], 2, ',', '.') ?>
              <span class="product-units">(<?= $units ?> units)</span>
            </div>
            <div class="product-rate">1€ = 10 units</div>
          </div>

          <div class="product-forms">
            <form method="post" class="product-cart-form">
              <input type="hidden" name="action" value="add_to_cart" />

              <label for="qty" class="product-qty-label">Quantity</label>
              <input id="qty" name="qty" type="number" min="1" value="1" class="product-qty" />

              <button type="submit" class="btn-primary product-btn">Add to cart</button>
            </form>

            <form method="post" class="product-wishlist-form">
              <input type="hidden" name="action" value="add_to_wishlist" />
              <button type="submit" class="btn-secondary product-btn">Add to wishlist</button>
            </form>
          </div>
        </div>

      </div>
    </section>

    <section class="product-recs">
      <div class="product-recs-head">
        <h2>You also might like</h2>
        <p>More books you may enjoy.</p>
      </div>

      <div class="rec-grid">
        <?php foreach ($recs as $r): ?>
          <?php $rUnits = (int)round(((float)$r['price']) * 10); ?>
          <article class="rec-card">
            <a href="product.php?id=<?= (int)$r['id'] ?>" class="rec-link">
              <div class="rec-img">
                <img src="<?= htmlspecialchars($r['cover_image']) ?>" alt="<?= htmlspecialchars($r['title']) ?>">
              </div>
              <div class="rec-info">
                <h3><?= htmlspecialchars(ucwords($r['title'])) ?></h3>
                <p class="rec-author"><?= htmlspecialchars($r['author']) ?></p>
                <p class="rec-price">
                  €<?= number_format((float)$r['price'], 2, ',', '.') ?>
                  <span>(<?= $rUnits ?> units)</span>
                </p>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
