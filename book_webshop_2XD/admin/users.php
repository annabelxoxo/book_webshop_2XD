<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$success = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";

  // prevent admin from changing themselves
  $selfId = (int)($_SESSION["user_id"] ?? 0);

  if ($action === "set_role") {
    $id = (int)($_POST["id"] ?? 0);
    $role = (string)($_POST["role"] ?? "");

    if ($id < 1) {
      $error = "Invalid user.";
    } elseif (!in_array($role, ["user", "admin"], true)) {
      $error = "Invalid role.";
    } elseif ($selfId > 0 && $id === $selfId && $role !== "admin") {
      $error = "You cannot remove your own admin role.";
    } else {
      try {
        $stmt = $pdo->prepare("UPDATE user SET role = ? WHERE id = ? LIMIT 1");
        $stmt->execute([$role, $id]);
        $success = "Role updated.";
      } catch (Throwable $e) {
        $error = "Could not update role: " . $e->getMessage();
      }
    }
  }

  if ($action === "delete_user") {
    $id = (int)($_POST["id"] ?? 0);

    if ($id < 1) {
      $error = "Invalid user.";
    } elseif ($selfId > 0 && $id === $selfId) {
      $error = "You cannot delete your own account.";
    } else {
      try {
        
        $stmt = $pdo->prepare("DELETE FROM user WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $success = $stmt->rowCount() ? "User deleted." : "User not found.";
      } catch (Throwable $e) {
        $error = "Could not delete user: " . $e->getMessage();
      }
    }
  }
}


$q = trim((string)($_GET["q"] ?? ""));
$roleFilter = trim((string)($_GET["role"] ?? "")); 

$where = [];
$params = [];

if ($q !== "") {
  $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
  $params[] = "%{$q}%";
  $params[] = "%{$q}%";
}

if (in_array($roleFilter, ["user", "admin"], true)) {
  $where[] = "u.role = ?";
  $params[] = $roleFilter;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";


$users = [];
try {

  $stmt = $pdo->prepare("
    SELECT
      u.id,
      u.name,
      u.email,
      u.role,
      u.created_at
    FROM user u
    $whereSql
    ORDER BY u.id DESC
    LIMIT 200
  ");
  $stmt->execute($params);
  $users = $stmt->fetchAll();
} catch (Throwable $e) {
  $error = "Could not load users: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Users - Admin</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/../includes/header.php"; ?>

<main>
  <div class="container admin-shell">

    <section class="admin-head">
      <div>
        <h2>Manage users</h2>
        <p class="admin-sub">Search users, change roles, and delete accounts.</p>
      </div>

      <div class="admin-actions">
        <a class="btn-secondary" href="book_webshop_2XD/admin/dashboard.php">← Back to dashboard</a>
      </div>
    </section>

    <?php if ($error): ?>
      <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="success"><?= h($success) ?></p>
    <?php endif; ?>

    <section class="admin-orders-card">
      <h3>Filters</h3>

      <form method="get" class="admin-users-filters">
        <div class="control">
          <label for="q">Search</label>
          <input id="q" type="text" name="q" value="<?= h($q) ?>" placeholder="Name or email">
        </div>

        <div class="control">
          <label for="role">Role</label>
          <select id="role" name="role">
            <option value="" <?= $roleFilter==="" ? "selected" : "" ?>>All</option>
            <option value="user" <?= $roleFilter==="user" ? "selected" : "" ?>>User</option>
            <option value="admin" <?= $roleFilter==="admin" ? "selected" : "" ?>>Admin</option>
          </select>
        </div>

        <div class="control-btns">
          <button class="btn-primary" type="submit">Apply</button>
          <a class="btn-secondary" href="book_webshop_2XD/admin/users.php">Reset</a>
        </div>
      </form>
    </section>

    <section class="admin-users-card">
      <h3 >Users (<?= count($users) ?>)</h3>

      <?php if (empty($users)): ?>
        <p >No users found.</p>
      <?php else: ?>
        <div >
          <table class="admin-users-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td>#<?= (int)$u["id"] ?></td>

                  <td>
                    <strong><?= h($u["name"]) ?></strong><br>
                    <small><?= h($u["email"]) ?></small>
                  </td>

                  <td>
                    <span class="admin-user-role <?= h($u["role"]) ?>">
                      <?= h($u["role"]) ?>
                    </span>
                  </td>

                  <td>
                    <small><?= h($u["created_at"] ?? "") ?></small>
                  </td>

                  <td>
                    <div class="admin-users-actions">
                      <form method="post">
                        <input type="hidden" name="action" value="set_role">
                        <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">

                        <select name="role">
                          <option value="user" <?= ($u["role"] ?? "") === "user" ? "selected" : "" ?>>user</option>
                          <option value="admin" <?= ($u["role"] ?? "") === "admin" ? "selected" : "" ?>>admin</option>
                        </select>

                        <button class="btn-secondary" type="submit">Update role</button>
                      </form>

                      <form method="post">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">
                        <button
                          class="btn-secondary"
                          type="submit"
                          onclick="return confirm('Delete this user? This cannot be undone.')"
                        >
                          Delete
                        </button>
                      </form>
                    </div>
                  </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<?php include __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
