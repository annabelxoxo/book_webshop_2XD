<?php
require __DIR__ . "/includes/config.php";

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

$success = "";
$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if($action === 'remove') {
        $removeId =(int)($_POST['id'] ?? 0);
        $_SESSION['wishlist'] = array_values(array_filter(
            $_SESSION['wishlist'],
            fn($x) => (int)$x !== $removeId 
        ));
        $success = "Book removed from wishlist.";
    }
    
if ($action === 'clear') {
    $_SESSION['wishlist'] = [];
    $success = "Wishlist cleared.";
  }

if ($action === 'move_to_cart') {
  $id = (int)($_POST['id'] ?? 0);

  if ($id > 0 && in_array($id, $_SESSION['wishlist'], true)) {


       $_SESSION['wishlist'] = array_values(array_filter(
        $_SESSION['wishlist'],
        fn($x) => (int)$x !== $id
      ));

      if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = 1;
      } else {
        $_SESSION['cart'][$id] += 1;
      }

      $success = "Moved to cart.";
  }
}
}

$wishlistIds = array_map('intval', $_SESSION['wishlist']);
$books = [];

if (!empty($wishlistIds)) {
    $placeholders = implode(',', array_fill(0, count($wishlistIds), '?'));
    $stmt = $pdo->prepare("
    SELECT b.id, b.title, b.price, b.cover_image, a.name AS author_name
    FROM book b
    JOIN author a ON b.author_id = a.id
    WHERE b.id IN ($placeholders)
    ");
    $stmt->execute($wishlistIds);
    $rows = $stmt->fetchAll();

  $byId = [];
  foreach ($rows as $r) $byId[(int)$r['id']] = $r;

  foreach ($wishlistIds as $wid) {
    if (isset($byId[$wid])) $books[] = $byId[$wid];
    }
  }



?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wishlist - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css">
</head>
<body>

<?php include 'includes/header.php'; ?>


<main>
  <div class="container">

    <section class="wishlist-head">
      <div>
        <h2>Wishlist</h2>
        <p class="wishlist-sub">Books you saved to maybe buy later.</p>
      </div>

      <?php if (!empty($wishlistIds)): ?>
        <form method="post">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="btn-secondary">Clear wishlist</button>
        </form>
      <?php endif; ?>
      </section>

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (empty($books)): ?>
      <div class="wishlist-empty">
        <p>Your wishlist is empty.</p>
        <a class="btn-primary" href="catalog.php">Browse catalog</a>
      </div>
    <?php else: ?>
      <div class="wishlist-grid">
        <?php foreach ($books as $b): ?>
          <?php $units = (int)round(((float)$b['price']) * 10); ?>
          <article class="wishlist-card">
            <a class="wishlist-link" href="product.php?id=<?= (int)$b['id'] ?>">
              <div class="wishlist-img">
                <img src="<?= htmlspecialchars($b['cover_image']) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
              </div>
              <div class="wishlist-info">
                <h3><?= htmlspecialchars(ucwords($b['title'])) ?></h3>
                <p class="wishlist-author"><?= htmlspecialchars($b['author_name']) ?></p>
                <p class="wishlist-meta">Paperback | English</p>
                <p class="wishlist-price">
                  €<?= number_format((float)$b['price'], 2, ',', '.') ?>
                  <span>(<?= $units ?> units)</span>
                </p>
              </div>
            </a>

            <div class="wishlist-actions">

                  <form method="post">
                    <input type="hidden" name="action" value="move_to_cart">
                    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">    
                    <button type="submit" class="btn-secondary">Move to cart</button>
                  </form>

            <form method="post">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn-secondary">Remove</button>
            </form>

            </div>

          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>