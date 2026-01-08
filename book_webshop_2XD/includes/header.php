<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']);

$profileHref = $isLoggedIn ? 'book_webshop_2XD/profile.php' : 'book_webshop_2XD/login.php';
$wishlistHref = $isLoggedIn ? 'book_webshop_2XD/wishlist.php' : 'book_webshop_2XD/login.php';
$cartHref = $isLoggedIn ? 'book_webshop_2XD/classes/cart.php' : 'book_webshop_2XD/login.php';
?>

<header class="main-header">
  <div class="container header-inner">

    <div class="header-logo">
      <a href="book_webshop_2XD/index.php">Books.</a>
    </div>

    <form class="header-search" action="book_webshop_2XD/catalog.php" method="get">
      <input type="text" name="q" placeholder="Search by name, title or author">
    </form>

    <div class="header-right">

    <nav class="header-nav">
      <a href="book_webshop_2XD/index.php">Home</a>

       <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <a href="book_webshop_2XD/admin/dashboard.php">Dashboard</a>
       <?php endif; ?>

      <a href="book_webshop_2XD/catalog.php">Catalog</a>
      <a href="book_webshop_2XD/bestseller.php">Bestseller</a>
      <a href="book_webshop_2XD/contact.php">Contact</a>
    </nav>

      <div class="header-icons">
        <a href="<?= $cartHref ?>" class="icon-btn">
          <img src="book_webshop_2XD/images/shopping cart icon.webp" alt="Cart">
        </a>

        <a href="<?= $wishlistHref ?>" class="icon-btn">
          <img src="book_webshop_2XD/images/wishlist icon.png" alt="Wishlist">
        </a>

        <a href="<?= $profileHref ?>" class="icon-btn">
          <img src="book_webshop_2XD/images/profile icon.png" alt="Profile">
        </a>
      </div>

    </div>

  </div>
</header>
