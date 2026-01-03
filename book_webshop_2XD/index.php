<?php
require __DIR__ . "/includes/config.php";

$stmt = $pdo->query("
  SELECT b.id, b.title, b.price, b.cover_image, a.name AS author
  FROM book b
  JOIN author a ON a.id = b.author_id
  ORDER BY b.id ASC
");

$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Webshop</title>
  <link rel="stylesheet" href="css/styles.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<main>
  <div class="container">

    <section class="intro">
      <h2>Your one-stop shop for books!</h2>
      <p>Discover a wide range of books across various genres.</p>
    </section>

    <section class="featured-books">
      <h2>Best selling books this week</h2>

      <div class="book-list">
        <?php
        $top = array_slice($books, 0, 3);

        foreach ($top as $book) {
          $units = (int) round(((float)$book['price']) * 10);

          echo "
          <a class='book-item' href='product.php?id=".(int)$book['id']."'>
            <img src='".htmlspecialchars($book['cover_image'])."' alt='".htmlspecialchars($book['title'])."'>
            <h3>".htmlspecialchars(ucwords($book['title']))."</h3>
            <p>by ".htmlspecialchars($book['author'])."</p>
            <p class='price'>
              €".number_format((float)$book['price'], 2, ',', '.')."
              <span class='units'>(".$units." units)</span>
            </p>
          </a>";
        }
        ?>
      </div>
    </section>

    <section class="why-do-we-read">
      <h2>WHY DO WE READ BOOKS?</h2>

      <div class="reasons-grid">
        <div class="reason-card">
          <h3>To understand the world and ourselves</h3>
          <p>Books let us step outside our own limited experience and learn how people think, love, fail and grow.</p>
        </div>

        <div class="reason-card">
          <h3>To build empathy</h3>
          <p>Stories help us see through someone else’s eyes and can strengthen empathy and emotional intelligence.</p>
        </div>

        <div class="reason-card">
          <h3>To preserve knowledge</h3>
          <p>Books store ideas across generations so we don’t need to relearn everything from scratch.</p>
        </div>

        <div class="reason-card">
          <h3>To think more deeply</h3>
          <p>Reading trains focus and critical thinking. Books slow us down and challenge our minds.</p>
        </div>

        <div class="reason-card">
          <h3>To escape and feel</h3>
          <p>Sometimes we read for comfort, excitement and rest. Stories help regulate emotions.</p>
        </div>

        <div class="reason-card">
          <h3>To explore possible lives</h3>
          <p>Books let us live many lives in one lifetime. You can be a ruler, a rebel, a scientist, a lover, a villain, a survivor—without the real-world cost. This exploration helps us choose who we want to become.</p>
        </div>

        <div class="reason-card">
          <h3>To improve language and communication skills</h3>
          <p>Reading expands vocabulary and improves grammar and writing skills. It helps people express their thoughts more clearly and confidently.</p>
        </div>

        <div class="reason-card">
          <h3>To develop imagination and creativity</h3>
          <p>Books encourage us to imagine places, characters, and ideas. This creativity helps with problem-solving and thinking in new ways.</p>
        </div>
      </div>
    </section>

  </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
