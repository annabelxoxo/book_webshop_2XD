<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION["user_id"])) {
  header("Location: /book_webshop_2XD/login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];
$addressId = (int)($_GET["id"] ?? 0);

$error = "";
$success = "";

$data = [
  "type" => "shipping",
  "full_name" => "",
  "phone" => "",
  "street" => "",
  "house_number" => "",
  "house_number_addition" => "",
  "postal_code" => "",
  "city" => "",
  "region" => "",
  "country_code" => "BE", //nog uitzoeken hoe die country codes werken//
];

if ($addressId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM address WHERE id = ? AND user_id = ?");
  $stmt->execute([$addressId, $userId]);
  $row = $stmt->fetch();

  if (!$row) {
    http_response_code(404);
    die("Address not found.");
  }

if ($_SERVER["REQUEST_METHOD"] === "POST") {


  foreach ($data as $k => $v) {
    if ($k === "type") continue;
    $data[$k] = trim((string)($_POST[$k] ?? $v));
  }


  if (
    $data["full_name"] === "" ||
    $data["street"] === "" ||
    $data["house_number"] === "" ||
    $data["postal_code"] === "" ||
    $data["city"] === "" ||
    $data["country_code"] === ""
  ) {
    $error = "Please fill in all required fields.";
  } else {

  if ($addressId > 0) {

      $upd = $pdo->prepare("
        UPDATE address
        SET
          full_name=?,
          phone=?,
          street=?,
          house_number=?,
          house_number_addition=?,
          postal_code=?,
          city=?,
          region=?,
          country_code=?,
          updated_at=NOW()
        WHERE id=? AND user_id=?
      ");

      $upd->execute([
        $data["full_name"],
        $data["phone"],
        $data["street"],
        $data["house_number"],
        $data["house_number_addition"],
        $data["postal_code"],
        $data["city"],
        $data["region"],
        strtoupper($data["country_code"]),
        $addressId,
        $userId
      ]);

      header("Location: /book_webshop_2XD/profile.php");
      exit;

    } else {
 
    $check = $pdo->prepare("SELECT id FROM address WHERE user_id = ? AND type = ? LIMIT 1");
      $check->execute([$userId, $data["type"]]);
      $existingId = (int)($check->fetchColumn() ?? 0);

      if ($existingId > 0) {

        $upd = $pdo->prepare("
          UPDATE address
          SET
            full_name=?,
            phone=?,
            street=?,
            house_number=?,
            house_number_addition=?,
            postal_code=?,
            city=?,
            region=?,
            country_code=?,
            updated_at=NOW()
          WHERE id=? AND user_id=?
        ");

        $upd->execute([
          $data["full_name"],
          $data["phone"],
          $data["street"],
          $data["house_number"],
          $data["house_number_addition"],
          $data["postal_code"],
          $data["city"],
          $data["region"],
          strtoupper($data["country_code"]),
          $existingId,
          $userId
        ]);

      } else {

        $ins = $pdo->prepare("
          INSERT INTO address
            (user_id, type, full_name, phone, street, house_number, house_number_addition, postal_code, city, region, country_code, created_at, updated_at)
          VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $ins->execute([
          $userId,
          $data["type"],
          $data["full_name"],
          $data["phone"],
          $data["street"],
          $data["house_number"],
          $data["house_number_addition"],
          $data["postal_code"],
          $data["city"],
          $data["region"],
          strtoupper($data["country_code"])
        ]);
      }

      header("Location: /book_webshop_2XD/profile.php");
      exit;
    }
  }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $addressId > 0 ? "Edit address" : "Add address" ?> - Book Webshop</title>
  <base href="/book_webshop_2XD/">
  <link rel="stylesheet" href="book_webshop_2XD/css/styles.css" />
</head>
<body>

<?php include __DIR__ . "/includes/header.php"; ?>

<main>
  <div class="container">

    <a class="btn-secondary" href="book_webshop_2XD/profile.php">← Back to profile</a>

    <h2 "><?= $addressId > 0 ? "Edit address" : "Add address" ?></h2>

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" class="contact-form">

      <div class="form-group">
        <label>Full name *</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($data["full_name"]) ?>" required>
      </div>

      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($data["phone"]) ?>">
      </div>

      <div class="form-group">
        <label>Street *</label>
        <input type="text" name="street" value="<?= htmlspecialchars($data["street"]) ?>" required>
      </div>

      <div class="form-group" >
        <div>
          <label>House number *</label>
          <input type="text" name="house_number" value="<?= htmlspecialchars($data["house_number"]) ?>" required>
        </div>
        <div>
          <label>Addition</label>
          <input type="text" name="house_number_addition" value="<?= htmlspecialchars($data["house_number_addition"]) ?>">
        </div>
      </div>

      <div class="form-group">
        <div>
          <label>Postal code *</label>
          <input type="text" name="postal_code" value="<?= htmlspecialchars($data["postal_code"]) ?>" required>
        </div>
        <div>
          <label>City *</label>
          <input type="text" name="city" value="<?= htmlspecialchars($data["city"]) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <div>
          <label>Region</label>
          <input type="text" name="region" value="<?= htmlspecialchars($data["region"]) ?>">
        </div>
        <div>
          <label>Country code *</label>
          <input type="text" name="country_code" maxlength="2" value="<?= htmlspecialchars($data["country_code"]) ?>" required>
        </div>
      </div>

      <button type="submit" class="btn-primary">Save address</button>
    </form>

  </div>
</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
</body>
</html>