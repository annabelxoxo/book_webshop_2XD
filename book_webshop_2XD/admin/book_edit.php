<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$bookId = (int)($_GET["id"] ?? 0);

$error = "";
$data = [
  "title" => "",
  "price" => "",
  "cover_image" => "",
  "description" => "",
  "author_id" => "",
];


$selectedGenres = []; 


$authors = [];
try {
  $stmt = $pdo->query("SELECT id, name FROM author ORDER BY name ASC");
  $authors = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load authors: " . $e->getMessage();
}

$genres = [];
try {
  $stmt = $pdo->query("SELECT id, name FROM genre ORDER BY name ASC");
  $genres = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load genres: " . $e->getMessage();
}

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

    $data["title"] = (string)($row["title"] ?? "");
    $data["price"] = (string)($row["price"] ?? "");
    $data["cover_image"] = (string)($row["cover_image"] ?? "");
    $data["description"] = (string)($row["description"] ?? "");
    $data["author_id"] = (string)($row["author_id"] ?? "");

    $stmt = $pdo->prepare("SELECT genre_id FROM book_genre WHERE book_id = ?");
    $stmt->execute([$bookId]);
    $selectedGenres = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

  } catch (Throwable $e) {
    $error = "Could not load book: " . $e->getMessage();
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $data["title"] = trim((string)($_POST["title"] ?? ""));
  $data["price"] = trim((string)($_POST["price"] ?? ""));
  $data["cover_image"] = trim((string)($_POST["cover_image"] ?? ""));
  $data["description"] = trim((string)($_POST["description"] ?? ""));
  $data["author_id"] = (string)((int)($_POST["author_id"] ?? 0));

  $postedGenres = $_POST["genres"] ?? [];
  if (!is_array($postedGenres)) $postedGenres = [];
  $selectedGenres = array_values(array_unique(array_filter(array_map('intval', $postedGenres), fn($x) => $x > 0)));

  if ($data["title"] === "") {
    $error = "Title is required.";
  } elseif ($data["author_id"] === "0") {
    $error = "Author is required.";
  } elseif ($data["price"] === "" || !is_numeric(str_replace(",", ".", $data["price"]))) {
    $error = "Price must be a number.";
  } else {

    $priceFloat = (float)str_replace(",", ".", $data["price"]);

    try {
      $pdo->beginTransaction();

      if ($bookId > 0) {
        $upd = $pdo->prepare("
          UPDATE book
          SET title=?, price=?, cover_image=?, description=?, author_id=?
          WHERE id=?
          LIMIT 1
        ");
        $upd->execute([
          $data["title"],
          $priceFloat,
          $data["cover_image"],
          $data["description"],
          (int)$data["author_id"],
          $bookId
        ]);

      } else {
        $ins = $pdo->prepare("
          INSERT INTO book (title, price, cover_image, description, author_id)
          VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([
          $data["title"],
          $priceFloat,
          $data["cover_image"],
          $data["description"],
          (int)$data["author_id"]
        ]);
        $bookId = (int)$pdo->lastInsertId();
      }

      $del = $pdo->prepare("DELETE FROM book_genre WHERE book_id = ?");
      $del->execute([$bookId]);

 
      if (!empty($selectedGenres)) {
        $insBg = $pdo->prepare("INSERT INTO book_genre (book_id, genre_id) VALUES (?, ?)");
        foreach ($selectedGenres as $gid) {
          $insBg->execute([$bookId, $gid]);
        }
      }

      $pdo->commit();

      header("Location: /book_webshop_2XD/book_webshop_2XD/admin/books.php");
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Could not save book: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $bookId > 0 ? "Edit book" : "Add book" ?> - Admin</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2><?= $bookId > 0 ? "Edit book" : "Add new book" ?></h2>
        <p class="admin-sub">Update book info and genres.</p>
      </div>

      <a class="btn-secondary" href="book_webshop_2XD/admin/books.php">← Back to books</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="contact-form">
      <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" value="<?= h($data["title"]) ?>" required>
      </div>

      <div class="form-group">
        <div>
          <label>Author *</label>
          <select name="author_id" required>
            <option value="0">-- choose author --</option>
            <?php foreach ($authors as $a): ?>
              <option value="<?= (int)$a["id"] ?>" <?= ((string)$a["id"] === (string)$data["author_id"]) ? "selected" : "" ?>>
                <?= h($a["name"]) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label>Price (€) *</label>
          <input type="text" name="price" value="<?= h($data["price"]) ?>" placeholder="e.g. 12.99" required>
        </div>
      </div>

      <div class="form-group">
        <label>Cover image path</label>
        <input type="text" name="cover_image" value="<?= h($data["cover_image"]) ?>" placeholder="e.g. images/mybook.png">
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="6"><?= h($data["description"]) ?></textarea>
      </div>


      <div class="form-group">
        <label>Genres</label>

        <?php if (empty($genres)): ?>
          <p>No genres found. Add genres first.</p>
        <?php else: ?>
          <div>
            <?php foreach ($genres as $g): ?>
              <?php $gid = (int)$g["id"]; ?>
              <label>
                <input
                  type="checkbox"
                  name="genres[]"
                  value="<?= $gid ?>"
                  <?= in_array($gid, $selectedGenres, true) ? "checked" : "" ?>
                >
                <?= h($g["name"]) ?>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-primary">
        <?= $bookId > 0 ? "Save changes" : "Create book" ?>
      </button>
    </form>

  </div>
</main>

<?php include __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
