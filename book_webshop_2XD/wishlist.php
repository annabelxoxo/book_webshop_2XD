<?php
require __DIR__ . "/includes/config.php";

if(session_status() === PHP_SESSION_NONE){
    session_start();
}


if (!isset($_SESSION["user_id"])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}
$userId = (int)$_SESSION["user_id"];

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";
  $id = (int)($_POST["id"] ?? 0);

  try {
    if ($action === "remove" && $id > 0) {
      $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ? LIMIT 1");
      $stmt->execute([$userId, $id]);
      $success = "Book removed from wishlist.";
    }

    if ($action === "clear") {
      $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
      $stmt->execute([$userId]);
      $success = "Wishlist cleared.";
    }

    if ($action === "move_to_cart" && $id > 0) {
      $pdo->beginTransaction();

      $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ? LIMIT 1");
      $del->execute([$userId, $id]);

      $ins = $pdo->prepare("
        INSERT INTO cart (user_id, book_id, quantity, added_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
      ");
      $ins->execute([$userId, $id]);

      $pdo->commit();
      $success = "Moved to cart.";
    }

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $error = "Something went wrong: " . $e->getMessage();
  }
}

$books = [];
try {
  $stmt = $pdo->prepare("
    SELECT
      b.id,
      b.title,
      b.price,
      b.cover_image,
      a.name AS author_name
    FROM wishlist w
    JOIN book b ON b.id = w.book_id
    JOIN author a ON a.id = b.author_id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC, b.id DESC
  ");
  $stmt->execute([$userId]);
  $books = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load wishlist: " . $e->getMessage();
}

$wishlistIds = array_map(fn($r) => (int)$r["id"], $books);
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
            <a class="wishlist-link" href="book_webshop_2XD/product.php?id=<?= (int)$b['id'] ?>">
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