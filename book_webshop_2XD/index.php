<?php

$username = "Guest";
$books = [
    [
        "title" => "the sun and her flowers",
        "author" => "Rupi Kaur",
        "price" => 11.39,
        "genre" => "poetry",
        "cover_image" =>  "images/the sun and her flower.jpg"
    ],
[
    "title" => "2am thoughts",
    "author" => "Rupi Kaur",
    "price" => 15.96,
    "genre" => "poetry",
    "cover_image" => "images/2 am thoughts.jpg"
],
[
    "title" => "a court of mist and fury",
    "author" => "Sarah J. Maas",
    "price" => 7.32,
    "genre" => "fiction",
    "cover_image" => "images/a qourt of mist and fury.jpg"
],
[
    "title" => "every word you cannot say",
    "author" => "lain S.Thomas",
    "price" => 11.39,
    "genre" => "poetry",
    "cover_image" => "images/every word you cannot say.jpg"
],
[
    "title" => "fourth wing",
    "author" => "Rebecca Yarros",
    "price" => 12.31,
    "genre" => "fiction",
    "cover_image" => "images/fourth wing.jpg"
],
[
    "title" => "it ends with us",
    "author" => "Colleen Hoover",
    "price" => 9.99,
    "genre" => "romance",
    "cover_image" => "images/it ends with us.jpg"
],
[
    "title" => "legends & lattes",
    "author" => "Travis Baldree",
    "price" => 8.07,
    "genre" => "fiction",
    "cover_image" => "images/legends and lattes.jpg"
],
[
    "title" => "milk and honey",
    "author" => "Rupi Kaur",
    "price" => 15.96,
    "genre" => "poetry",
    "cover_image" => "images/milk and honey.jpg"
],
[
    "title" => "quicksilver",
    "author" => "Callie Hart",
    "price" => 18.39,
    "genre" => "fiction",
    "cover_image" => "images/quicksilver.jpg"
],
[
    "title" => "the Serpent and the wings of night",
    "author" => "Carissa Broadbent",
    "price" => 24.99,
    "genre" => "fiction",
    "cover_image" => "images/the serpent and the wings of night.jpeg"
],
[
    "title" => "Vampire Diaries: the awakening",
    "author" => "L.J. Smith",
    "price" => 15.99,
    "genre" => "fiction",
    "cover_image" => "images/vampire diaries.jpg"
],
[
    "title" => "you've reached Sam",
    "author" => "Dustin Thao",
    "price" => 10.99,
    "genre" => "fiction",
    "cover_image" => "images/you've reached sam.jpeg"
]
];
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
    <header>
        <h1>Welcome to the Book Webshop</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="catalog.php">Catalog</a></li>
                <li><a href="cart.php"><img src="images/shopping cart icon.webp" alt="shopping cart icon">Cart</a></li>
                <li><a href="wishlist.php"><img src="images/wishlist icon.png" alt="wishlist icon">Wishlist</a></li>

                <li><a href="profile.php"><img src="images/profile icon.png" alt="Profile icon"> Profile</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section class="intro">
            <h2>Your one-stop shop for books!</h2>
            <p>Discover a wide range of books across various genres. Whether you're looking for fiction, non-fiction, or  romance, we've got you covered.</p>
        </section>
        <section class="featured-books">
            <h2>best selling books this week</h2>
            <div class="book-list">
                <?php
                for ($i = 0; $i < 3; $i++) {
                    $book = $books[$i];
                    echo "<div class='book-item'>
                            <img src='{$book['cover_image']}' alt='{$book['title']} cover'>
                            <h3>" . ucwords($book['title']) . "</h3>
                            <p>by {$book['author']}</p>
                            <p>Price: $" . number_format($book['price'], 2) . "</p>
                          </div>";
                }
                ?>
            </div>
    </main>

<footer>
    <div class="footer-container">

        <div class="footer-left">
            <p>&copy; 2026 Book Webshop</p>
            <p><a href="mailto:r1039110@student.thomasmore.be">r1039110@student.thomasmore.be</a></p>
            <p><a href="tel:+3248450862">04 83 45 08 62</a></p>
        </div>

    <div class="footer-right">
            <p class="about the shop">Stay connected with us on social media for the latest updates and offers!</p>
             <div class="socials">
                <a href="https://facebook.com/uitsprakenannabel.smeulders" target="_blank">
                    <img src="images/facebook.png" alt="Facebook">
                </a>
                <a href="https://www.instagram.com/smeuldersannabel/" target="_blank">
                    <img src="images/instagram.png" alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@annabel_happycow" target="_blank">
                    <img src="images/tiktok.png" alt="Tiktok">
                </a>
            </div> 
    </div>

    </div>
</footer>