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


$success = "";
$error = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!isset($_SESSION["user_id"])) {
    header("Location: /book_webshop_2XD/login.php");
    exit;
  }

  $userId = (int)$_SESSION["user_id"];
  $action = $_POST['action'] ?? '';

  try {
  if ($action === 'add_to_cart') {
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    $stmt = $pdo->prepare("
      INSERT INTO cart (user_id, book_id, quantity) 
      VALUES (?, ?, ?) 
      ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ");
    $stmt->execute([$userId, $id, $qty, $qty]);

    $success = "Added to cart!";
  }

if ($action === 'add_to_wishlist') {
  $stmt = $pdo->prepare("
    INSERT INTO wishlist (user_id, book_id, added_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE added_at = NOW()
  ");
  $stmt->execute([$userId, $id]);

  $success = ($stmt->rowCount() === 1)
    ? "Added to wishlist!"
    : "This book is already in your wishlist.";
}

  } catch (Throwable $e) {
    $error = "Something went wrong: " . $e->getMessage();
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

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }


$reviews = [];
try {
$stmt = $pdo->prepare("
  SELECT r.user_id, r.rating, r.comment, r.created_at, u.name AS user_name
  FROM review r
  JOIN user u ON u.id = r.user_id
  WHERE r.book_id = ?
  ORDER BY r.created_at DESC
");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();
} catch (Throwable $e) {
  $reviews = [];
}

$hasBought = false;
$myReview = null;

if (!empty($_SESSION["user_id"])) {
  $userId = (int)$_SESSION["user_id"];

  $stmt = $pdo->prepare("
    SELECT 1
    FROM `order` o
    JOIN order_book ob ON ob.order_id = o.id
    WHERE o.user_id = ? AND ob.book_id = ?
      AND (o.status = 'paid' OR o.status = 'completed')
    LIMIT 1
  ");
  $stmt->execute([$userId, $id]);
  $hasBought = (bool)$stmt->fetchColumn();

  $stmt = $pdo->prepare("
    SELECT rating, comment
    FROM review
    WHERE user_id = ? AND book_id = ?
    LIMIT 1
  ");
  $stmt->execute([$userId, $id]);
  $myReview = $stmt->fetch();
}


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
            <a href="book_webshop_2XD/product.php?id=<?= (int)$r['id'] ?>" class="rec-link">
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

    <section class="reviews-shell" id="reviews">
  <div class="reviews-head">
    <div>
      <h2>Reviews</h2>
      <p>What other readers think.</p>
    </div>
  </div>

  <div id="reviewsList" class="reviews-list">
    <?php if (empty($reviews)): ?>
      <p class="reviews-empty">No reviews yet.</p>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <article class="review-card" data-user-id="<?= (int)$r["user_id"] ?>">
          <div class="review-top">
            <strong><?= h($r["user_name"]) ?></strong>
            <span class="review-rating"><?= (int)$r["rating"] ?>/5</span>
          </div>
          <p class="review-comment"><?= nl2br(h($r["comment"])) ?></p>
          <small class="review-date"><?= h($r["created_at"]) ?></small>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (empty($_SESSION["user_id"])): ?>
    <p class="reviews-note">Log in to leave a review.</p>

  <?php elseif (!$hasBought): ?>
    <p class="reviews-note">You can only review this book after purchasing it.</p>

  <?php else: ?>
    <div class="review-form-card">
      <h3><?= $myReview ? "Update your review" : "Add your review" ?></h3>

      <form id="reviewForm">
        <input type="hidden" name="book_id" value="<?= (int)$id ?>">

        <label>Rating</label>
        <select name="rating" required>
          <?php for ($i=5; $i>=1; $i--): ?>
            <option value="<?= $i ?>" <?= ($myReview && (int)$myReview["rating"] === $i) ? "selected" : "" ?>>
              <?= $i ?>
            </option>
          <?php endfor; ?>
        </select>

        <label>Comment</label>
        <textarea name="comment" rows="4" required><?= $myReview ? h($myReview["comment"]) : "" ?></textarea>

        <button type="submit" class="btn-primary">Save review</button>
        <p id="reviewMsg" class="reviews-msg" style="display:none;"></p>
      </form>
    </div>
  <?php endif; ?>
</section>


  </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
(function(){
  const form = document.getElementById('reviewForm');
  if(!form) return;

  const msg = document.getElementById('reviewMsg');
  const list = document.getElementById('reviewsList');

  function esc(s){
    return String(s).replace(/[&<>"']/g, m => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[m]));
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    msg.style.display = "none";
    msg.className = "reviews-msg";

    const fd = new FormData(form);

    try {
      const res = await fetch('book_webshop_2XD/ajax/review_save.php', {
        method: 'POST',
        body: fd
      });

      const data = await res.json().catch(() => null);

      if(!res.ok || !data || !data.ok){
        const err = (data && data.error) ? data.error : "Could not save review.";
        msg.textContent = err;
        msg.classList.add("reviews-msg-error");
        msg.style.display = "block";
        return;
      }

      msg.textContent = "Saved!";
      msg.classList.add("reviews-msg-success");
      msg.style.display = "block";

      const r = data.review;

      const empty = list.querySelector('.reviews-empty');
      if(empty) empty.remove();

      let card = list.querySelector(`.review-card[data-user-id="${r.user_id}"]`);

      if(!card){
        card = document.createElement('article');
        card.className = "review-card";
        card.dataset.userId = r.user_id;
        list.prepend(card);
      }

      card.innerHTML = `
        <div class="review-top">
          <strong>${esc(r.user_name)}</strong>
          <span class="review-rating">${esc(r.rating)}/5</span>
        </div>
        <p class="review-comment">${esc(r.comment).replace(/\n/g,"<br>")}</p>
        <small class="review-date">${esc(r.created_at)}</small>
      `;

    } catch (e) {
      msg.textContent = "Network error.";
      msg.classList.add("reviews-msg-error");
      msg.style.display = "block";
    }
  });
})();
</script>


</body>
</html>
