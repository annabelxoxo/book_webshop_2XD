<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$bookId = (int)($_GET["id"] ?? 0);

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  $redirect = "admin/book_edit.php" . ($bookId > 0 ? "?id=" . $bookId : "");
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode($redirect));
  exit;
}

$error = "";

$data = [
  "title" => "",
  "price" => "",
  "cover_image" => "",
  "description" => "",
  "author_id" => "",
];

$selectedGenres = [];

/* ===== LOAD AUTHORS ===== */
$authors = [];
try {
  $stmt = $pdo->query("SELECT id, name FROM author ORDER BY name ASC");
  $authors = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load authors.";
}

/* ===== LOAD GENRES ===== */
$genres = [];
try {
  $stmt = $pdo->query("SELECT id, name FROM genre ORDER BY name ASC");
  $genres = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load genres.";
}

/* ===== LOAD BOOK (EDIT) ===== */
if ($bookId > 0) {
  try {
    $stmt = $pdo->prepare("
      SELECT id, title, price, cover_image, description, author_id
      FROM book
      WHERE id = ?
      LIMIT 1
    ");
    $stmt->execute([$bookId]);
    $row = $stmt->fetch();

    if (!$row) {
      http_response_code(404);
      die("Book not found.");
    }

    $data["title"] = (string)$row["title"];
    $data["price"] = (string)$row["price"];
    $data["cover_image"] = (string)($row["cover_image"] ?? "");
    $data["description"] = (string)($row["description"] ?? "");
    $data["author_id"] = (string)$row["author_id"];

    $stmt = $pdo->prepare("SELECT genre_id FROM book_genre WHERE book_id = ?");
    $stmt->execute([$bookId]);
    $selectedGenres = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));

  } catch (Throwable $e) {
    $error = "Could not load book.";
  }
}

/* ===== SAVE ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $data["title"] = trim($_POST["title"] ?? "");
  $data["price"] = trim($_POST["price"] ?? "");
  $data["cover_image"] = trim($_POST["cover_image"] ?? "");
  $data["description"] = trim($_POST["description"] ?? "");
  $data["author_id"] = (string)((int)($_POST["author_id"] ?? 0));

  // normalize cover path
  if ($data["cover_image"] !== "") {
    $data["cover_image"] = preg_replace('#^book_webshop_2XD/#', '', $data["cover_image"]);
  }

  $postedGenres = $_POST["genres"] ?? [];
  if (!is_array($postedGenres)) $postedGenres = [];
  $selectedGenres = array_values(array_unique(array_map("intval", $postedGenres)));

  if ($data["title"] === "") {
    $error = "Title is required.";
  } elseif ($data["author_id"] === "0") {
    $error = "Author is required.";
  } elseif ($data["price"] === "" || !is_numeric(str_replace(",", ".", $data["price"]))) {
    $error = "Price must be numeric.";
  } else {

    $priceFloat = (float)str_replace(",", ".", $data["price"]);

    try {
      $pdo->beginTransaction();

      $upd = $pdo->prepare("
        UPDATE book
        SET title=?, price=?, cover_image=?, description=?, author_id=?
        WHERE id=?
        LIMIT 1
      ");
      $upd->execute([
        $data["title"],
        $priceFloat,
        ($data["cover_image"] !== "" ? $data["cover_image"] : null),
        $data["description"],
        (int)$data["author_id"],
        $bookId
      ]);

      $pdo->prepare("DELETE FROM book_genre WHERE book_id = ?")
          ->execute([$bookId]);

      if ($selectedGenres) {
        $ins = $pdo->prepare("INSERT INTO book_genre (book_id, genre_id) VALUES (?, ?)");
        foreach ($selectedGenres as $gid) {
          if ($gid > 0) $ins->execute([$bookId, $gid]);
        }
      }

      $pdo->commit();

      header("Location: " . APP_URL . "admin/books.php");
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Could not save book.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit book - Admin</title>
  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2>Edit book</h2>
        <p class="admin-sub">Update book information</p>
      </div>

      <a class="btn-secondary" href="<?= APP_URL ?>admin/books.php">← Back to books</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="contact-form">

      <label>Title *</label>
      <input type="text" name="title" value="<?= h($data["title"]) ?>" required>

      <label>Author *</label>
      <select name="author_id" required>
        <option value="0">-- choose author --</option>
        <?php foreach ($authors as $a): ?>
          <option value="<?= (int)$a["id"] ?>" <?= ((string)$a["id"] === $data["author_id"]) ? "selected" : "" ?>>
            <?= h($a["name"]) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Price (€) *</label>
      <input type="text" name="price" value="<?= h($data["price"]) ?>" required>

      <label>Cover image path</label>
      <input type="text" name="cover_image" value="<?= h($data["cover_image"]) ?>">

      <label>Description</label>
      <textarea name="description" rows="6"><?= h($data["description"]) ?></textarea>

      <label>Genres</label>
      <div>
        <?php foreach ($genres as $g): ?>
          <?php $gid = (int)$g["id"]; ?>
          <label>
            <input type="checkbox" name="genres[]" value="<?= $gid ?>"
              <?= in_array($gid, $selectedGenres, true) ? "checked" : "" ?>>
            <?= h($g["name"]) ?>
          </label>
        <?php endforeach; ?>
      </div>

      <button type="submit" class="btn-primary">Save changes</button>
    </form>

  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
