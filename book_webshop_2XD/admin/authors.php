<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/authors.php"));
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  try {
    if ($action === "add_author") {
      $name = trim((string)($_POST["name"] ?? ""));
      if ($name === "") {
        $error = "Author name is required.";
      } else {
        $stmt = $pdo->prepare("INSERT INTO author (name) VALUES (?)");
        $stmt->execute([$name]);
        $success = "Author added.";
      }
    }

    if ($action === "update_author") {
      $id = (int)($_POST["id"] ?? 0);
      $name = trim((string)($_POST["name"] ?? ""));

      if ($id < 1) {
        $error = "Invalid author.";
      } elseif ($name === "") {
        $error = "Author name is required.";
      } else {
        $stmt = $pdo->prepare("UPDATE author SET name = ? WHERE id = ? LIMIT 1");
        $stmt->execute([$name, $id]);
        $success = "Author updated.";
      }
    }

    if ($action === "delete_author") {
      $id = (int)($_POST["id"] ?? 0);

      if ($id < 1) {
        $error = "Invalid author.";
      } else {
        $check = $pdo->prepare("SELECT COUNT(*) FROM book WHERE author_id = ?");
        $check->execute([$id]);
        $bookCount = (int)($check->fetchColumn() ?? 0);

        if ($bookCount > 0) {
          $error = "You cannot delete this author because there are still books linked to them.";
        } else {
          $stmt = $pdo->prepare("DELETE FROM author WHERE id = ? LIMIT 1");
          $stmt->execute([$id]);
          $success = $stmt->rowCount() ? "Author deleted." : "Author not found.";
        }
      }
    }

  } catch (Throwable $e) {
    $error = "Something went wrong: " . $e->getMessage();
  }
}

$q = trim((string)($_GET["q"] ?? ""));

$where = "";
$params = [];

if ($q !== "") {
  $where = "WHERE a.name LIKE ?";
  $params[] = "%{$q}%";
}

$authors = [];
try {
  $stmt = $pdo->prepare("
    SELECT
      a.id,
      a.name,
      COUNT(b.id) AS book_count
    FROM author a
    LEFT JOIN book b ON b.author_id = a.id
    $where
    GROUP BY a.id, a.name
    ORDER BY a.name ASC
    LIMIT 500
  ");
  $stmt->execute($params);
  $authors = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load authors: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Authors - Admin</title>

  <link rel="stylesheet" href="<?= APP_URL ?>css/styles.css" />
</head>
<body>

<?php require_once __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container admin-shell">

    <section class="admin-head">
      <div>
        <h2>Manage authors</h2>
        <p class="admin-sub">Add, edit, search and delete authors.</p>
      </div>

      <div class="admin-actions">
        <a class="btn-secondary" href="<?= APP_URL ?>admin/dashboard.php">← Back to dashboard</a>
      </div>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <section class="admin-authors-card">
      <div class="admin-authors-top">
        <h3>Filters</h3>

        <form method="get" class="admin-authors-filters">
          <div class="control">
            <label for="q">Search</label>
            <input id="q" type="text" name="q" value="<?= h($q) ?>" placeholder="Author name">
          </div>

          <div class="control-btns">
            <button class="btn-primary" type="submit">Apply</button>
            <a class="btn-secondary" href="<?= APP_URL ?>admin/authors.php">Reset</a>
          </div>
        </form>
      </div>

      <hr class="admin-authors-divider">

      <h3>Add new author</h3>
      <form method="post" class="admin-authors-add">
        <input type="hidden" name="action" value="add_author">
        <div class="control">
          <label for="name">Name</label>
          <input id="name" type="text" name="name" placeholder="e.g. Stephen King" required>
        </div>
        <button class="btn-primary" type="submit">Add author</button>
      </form>
    </section>

    <section class="admin-authors-card">
      <h3>Authors (<?= count($authors) ?>)</h3>

      <?php if (empty($authors)): ?>
        <p class="admin-empty">No authors found.</p>
      <?php else: ?>
        <div class="admin-author-list">
          <?php foreach ($authors as $a): ?>
            <div class="admin-author-card">

              <div class="admin-author-info">
                <strong><?= h($a["name"]) ?></strong>
                <small>#<?= (int)$a["id"] ?> · <?= (int)$a["book_count"] ?> book(s)</small>

                <?php if ((int)$a["book_count"] > 0): ?>
                  <small class="admin-authors-hint">
                    Tip: you must remove or reassign the books first.
                  </small>
                <?php endif; ?>
              </div>

              <div class="admin-author-actions">

                <form method="post" class="admin-author-edit">
                  <input type="hidden" name="action" value="update_author">
                  <input type="hidden" name="id" value="<?= (int)$a["id"] ?>">
                  <input type="text" name="name" value="<?= h($a["name"]) ?>" required>
                  <button class="btn-secondary" type="submit">Save</button>
                </form>

                <form method="post">
                  <input type="hidden" name="action" value="delete_author">
                  <input type="hidden" name="id" value="<?= (int)$a["id"] ?>">
                  <button
                    class="btn-secondary"
                    type="submit"
                    onclick="return confirm('Delete this author? This cannot be undone.')"
                    <?= ((int)$a["book_count"] > 0) ? "disabled title='Remove books first'" : "" ?>
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
