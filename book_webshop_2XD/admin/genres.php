<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/genres.php"));
  exit;
}

$success = "";
$error = "";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add") {
  $name = trim((string)($_POST["name"] ?? ""));

  if ($name === "") {
    $error = "Genre name is required.";
  } else {
    $stmt = $pdo->prepare("INSERT INTO genre (name) VALUES (?)");
    try {
      $stmt->execute([$name]);
      $success = "Genre added successfully.";
    } catch (PDOException $e) {
      $error = "This genre already exists.";
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete") {
  $id = (int)($_POST["id"] ?? 0);

  if ($id > 0) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM book_genre WHERE genre_id = ?");
    $check->execute([$id]);

    if ((int)$check->fetchColumn() > 0) {
      $error = "Cannot delete genre that is linked to books.";
    } else {
      $del = $pdo->prepare("DELETE FROM genre WHERE id = ? LIMIT 1");
      $del->execute([$id]);
      $success = $del->rowCount() ? "Genre deleted." : "Genre not found.";
    }
  }
}

$stmt = $pdo->query("
  SELECT g.id, g.name, COUNT(bg.book_id) AS book_count
  FROM genre g
  LEFT JOIN book_genre bg ON bg.genre_id = g.id
  GROUP BY g.id, g.name
  ORDER BY g.name ASC
");
$genres = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Genres</title>

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css">
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container">

    <section class="admin-head">
      <div>
        <h2>Manage genres</h2>
        <p class="admin-sub">Add or remove book genres</p>
      </div>

      <a class="btn-secondary" href="<?= APP_URL ?>admin/dashboard.php">← Back to dashboard</a>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <section class="profile-box admin-genre-add">
      <h2>Add new genre</h2>

      <form method="post">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="Genre name" required>
        <button type="submit" class="btn-primary">Add</button>
      </form>
    </section>

    <section class="profile-box">
      <h2>Existing genres</h2>

      <?php if (empty($genres)): ?>
        <p>No genres found.</p>
      <?php else: ?>
        <div>
          <?php foreach ($genres as $g): ?>
            <div>
              <div>
                <strong><?= h($g["name"]) ?></strong><br>
                <small><?= (int)$g["book_count"] ?> book(s)</small>
              </div>

              <div class="admin-genre-actions">
                <form method="post">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$g["id"] ?>">
                  <button
                    type="submit"
                    class="btn-secondary"
                    onclick="return confirm('Delete this genre?')"
                    <?= ((int)$g["book_count"] > 0) ? "disabled title='Remove linked books first'" : "" ?>
                  >
                    Delete
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
