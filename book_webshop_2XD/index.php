<?php

$username = "Guest";

require __DIR__ . "/includes/config.php";


$stmt = $pdo->query("
  SELECT b.id, b.title, b.price, b.cover_image, a.name AS author
  FROM book b
  JOIN author a ON a.id = b.author_id
  ORDER BY id ASC
");

$books = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Webshop</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="fontsheet" href="https://fonts.google.com/specimen/Urbanist?query=urbanist&categoryFilters=Feeling:%2FExpressive%2FCalm">
  <script src="script.js" defer></script>
</head>
<body>

<?php include 'includes/header.php'; ?>

<main>
  <section class="intro">
    <h2>Your one-stop shop for books!</h2>
    <p>Discover a wide range of books across various genres.</p>
  </section>

  <section class="featured-books">
    <h2>best selling books this week</h2>
    <div class="book-list">
      <?php
      $top = array_slice($books, 0, 3);
      foreach ($top as $book) {
        echo "<div class='book-item'>
                <img src='" . htmlspecialchars($book['cover_image']) . "' alt='" . htmlspecialchars($book['title']) . "'>
                <h3>" . htmlspecialchars(ucwords($book['title'])) . "</h3>
                <p>by " . htmlspecialchars($book['author']) . "</p>
                <p class='price'>
                  Price: €" . number_format((float)$book['price'], 2, ',', '.') . "
                <span class='units'>(" . round($book['price'] * 10) . " units)</span>
                </p>
              </div>";
      }
      ?>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>

