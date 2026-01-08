<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']);

$profileHref  = $isLoggedIn ? APP_URL . 'profile.php'      : APP_URL . 'login.php';
$wishlistHref = $isLoggedIn ? APP_URL . 'wishlist.php'     : APP_URL . 'login.php';
$cartHref     = $isLoggedIn ? APP_URL . 'classes/cart.php' : APP_URL . 'login.php';
?>

<header class="main-header">
  <div class="container header-inner">

    <div class="header-logo">
      <a href="<?= APP_URL ?>index.php">Books.</a>
    </div>

    <form class="header-search" action="<?= APP_URL ?>catalog.php" method="get">
      <input type="text" name="q" placeholder="Search by name, title or author">
    </form>

    <div class="header-right">

      <nav class="header-nav">
        <a href="<?= APP_URL ?>index.php">Home</a>

        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="<?= APP_URL ?>admin/dashboard.php">Dashboard</a>
        <?php endif; ?>

        <a href="<?= APP_URL ?>catalog.php">Catalog</a>
        <a href="<?= APP_URL ?>bestseller.php">Bestseller</a>
        <a href="<?= APP_URL ?>contact.php">Contact</a>
      </nav>

      <div class="header-icons">
        <a href="<?= $cartHref ?>" class="icon-btn">
          <img src="<?= APP_URL ?>images/shopping cart icon.webp" alt="Cart">
        </a>

        <a href="<?= $wishlistHref ?>" class="icon-btn">
          <img src="<?= APP_URL ?>images/wishlist icon.png" alt="Wishlist">
        </a>

        <a href="<?= $profileHref ?>" class="icon-btn">
          <img src="<?= APP_URL ?>images/profile icon.png" alt="Profile">
        </a>
      </div>

    </div>
  </div>
</header>
