<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/book_create.php"));
  exit;
}

function h($s){
  return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
}

$error = "";

$data = [
  "title" => "",
  "price" => "",
  "cover_image" => "",
  "description" => "",
  "author_id" => "",
  "serie_id" => "",
  "serie_number" => "",
];

$selectedGenres = [];

$authors = [];
$stmt = $pdo->query("SELECT id, name FROM author ORDER BY name ASC");
$authors = $stmt->fetchAll();

$genres = [];
$stmt = $pdo->query("SELECT id, name FROM genre ORDER BY name ASC");
$genres = $stmt->fetchAll();

$series = [];
try {
  $stmt = $pdo->query("SELECT id, name FROM serie ORDER BY name ASC");
  $series = $stmt->fetchAll();
} catch (Throwable $e) {
  $series = [];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $data["title"] = trim($_POST["title"] ?? "");
  $data["price"] = trim($_POST["price"] ?? "");
  $data["cover_image"] = trim($_POST["cover_image"] ?? "");
  $data["description"] = trim($_POST["description"] ?? "");
  $data["author_id"] = (int)($_POST["author_id"] ?? 0);
  $data["serie_id"] = (int)($_POST["serie_id"] ?? 0);
  $data["serie_number"] = trim((string)($_POST["serie_number"] ?? ""));

  $postedGenres = $_POST["genres"] ?? [];
  if (!is_array($postedGenres)) $postedGenres = [];

  $selectedGenres = array_values(
    array_unique(
      array_filter(
        array_map('intval', $postedGenres),
        fn($x) => $x > 0
      )
    )
  );

  if ($data["title"] === "") {
    $error = "Title is required.";
  } elseif ($data["author_id"] < 1) {
    $error = "Author is required.";
  } elseif ($data["price"] === "" || !is_numeric(str_replace(",", ".", $data["price"]))) {
    $error = "Price must be a valid number.";
  } else {

    $price = (float)str_replace(",", ".", $data["price"]);

    try {
      $pdo->beginTransaction();

      $coverPath = null;

      if (!empty($_FILES["cover_image_file"]["name"])) {
        $f = $_FILES["cover_image_file"];

        if ($f["error"] !== UPLOAD_ERR_OK) {
          throw new Exception("Upload failed.");
        }

        $ext = strtolower(pathinfo($f["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg","jpeg","png","webp"];

        if (!in_array($ext, $allowed, true)) {
          throw new Exception("Only JPG, PNG or WebP allowed.");
        }

        $uploadDir = __DIR__ . "/../images/";
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        $newName = "book_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $dest = $uploadDir . $newName;

        if (!move_uploaded_file($f["tmp_name"], $dest)) {
          throw new Exception("Could not save uploaded image.");
        }

        // ✅ pad zoals gebruikt door de site
        $coverPath = "images/" . $newName;
      }

      $ins = $pdo->prepare("
        INSERT INTO book (title, price, cover_image, description, author_id, serie_id, serie_number)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");

      $ins->execute([
        $data["title"],
        $price,
        $coverPath,
        $data["description"],
        (int)$data["author_id"],
        ($data["serie_id"] > 0 ? $data["serie_id"] : null),
        ($data["serie_number"] !== "" ? (int)$data["serie_number"] : null),
      ]);

      $bookId = (int)$pdo->lastInsertId();

      if (!empty($selectedGenres)) {
        $stmt = $pdo->prepare("
          INSERT INTO book_genre (book_id, genre_id)
          VALUES (?, ?)
        ");
        foreach ($selectedGenres as $gid) {
          $stmt->execute([$bookId, $gid]);
        }
      }

      $pdo->commit();

      header("Location: " . APP_URL . "admin/books.php");
      exit;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $error = "Could not create book: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add book - Admin</title>

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2>Add new book</h2>
        <p class="admin-sub">Create a new book and assign genres.</p>
      </div>

      <a class="btn-secondary" href="<?= APP_URL ?>admin/books.php">
        ← Back to books
      </a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="contact-form" enctype="multipart/form-data">

      <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" value="<?= h($data["title"]) ?>" required>
      </div>

      <div class="form-group">
        <label>Author *</label>
        <select name="author_id" required>
          <option value="0">-- choose author --</option>
          <?php foreach ($authors as $a): ?>
            <option
              value="<?= (int)$a["id"] ?>"
              <?= ((int)$data["author_id"] === (int)$a["id"]) ? "selected" : "" ?>
            >
              <?= h($a["name"]) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Price (€) *</label>
        <input type="text" name="price" value="<?= h($data["price"]) ?>" placeholder="e.g. 14.99" required>
      </div>

      <div class="form-group">
        <label>Cover image (optional)</label>
        <input type="file" name="cover_image_file" accept="image/*">
        <small>JPG/PNG/WebP recommended.</small>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="6"><?= h($data["description"]) ?></textarea>
      </div>

      <div class="form-group">
        <label>Genres (optional)</label>

        <?php if (empty($genres)): ?>
          <p class="admin-empty">No genres found.</p>
        <?php else: ?>
          <div class="admin-genres-checkboxes">
            <?php foreach ($genres as $g): ?>
              <?php $gid = (int)$g["id"]; ?>
              <label class="admin-genre-check">
                <input
                  type="checkbox"
                  name="genres[]"
                  value="<?= $gid ?>"
                  <?= in_array($gid, $selectedGenres, true) ? "checked" : "" ?>
                >
                <span><?= h($g["name"]) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label>Series (optional)</label>
        <select name="serie_id">
          <option value="0">-- none --</option>
          <?php foreach ($series as $s): ?>
            <option
              value="<?= (int)$s["id"] ?>"
              <?= ((string)$data["serie_id"] === (string)$s["id"]) ? "selected" : "" ?>
            >
              <?= h($s["name"]) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Series number (optional)</label>
        <input type="number" min="1" name="serie_number" value="<?= h($data["serie_number"]) ?>" placeholder="e.g. 1">
      </div>

      <button type="submit" class="btn-primary">
        Create book
      </button>

    </form>

  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
