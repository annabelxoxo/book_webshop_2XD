<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = !empty($_SESSION['user_id']);

// ✅ dynamisch pad naar project-root (werkt in /, /admin, /classes, ...)
$root = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
$root = preg_replace('~/(admin|classes|ajax)(/.*)?$~', '', $root);
$root = $root . "/";

// links
$profileHref  = $isLoggedIn ? $root . 'profile.php'       : $root . 'login.php';
$wishlistHref = $isLoggedIn ? $root . 'wishlist.php'      : $root . 'login.php';
$cartHref     = $isLoggedIn ? $root . 'classes/cart.php'  : $root . 'login.php';
?>
<header class="main-header">
  <div class="container header-inner">

    <div class="header-logo">
      <a href="<?= $root ?>index.php">Books.</a>
    </div>

    <form class="header-search" action="<?= $root ?>catalog.php" method="get">
      <input type="text" name="q" placeholder="Search by name, title or author">
    </form>

    <div class="header-right">

      <nav class="header-nav">
        <a href="<?= $root ?>index.php">Home</a>

        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="<?= $root ?>admin/dashboard.php">Dashboard</a>
        <?php endif; ?>

        <a href="<?= $root ?>catalog.php">Catalog</a>
        <a href="<?= $root ?>bestseller.php">Bestseller</a>
        <a href="<?= $root ?>contact.php">Contact</a>
      </nav>

      <div class="header-icons">
        <a href="<?= htmlspecialchars($cartHref) ?>" class="icon-btn">
          <img src="<?= $root ?>images/shopping cart icon.webp" alt="Cart">
        </a>

        <a href="<?= htmlspecialchars($wishlistHref) ?>" class="icon-btn">
          <img src="<?= $root ?>images/wishlist icon.png" alt="Wishlist">
        </a>

        <a href="<?= htmlspecialchars($profileHref) ?>" class="icon-btn">
          <img src="<?= $root ?>images/profile icon.png" alt="Profile">
        </a>
      </div>

    </div>

  </div>
</header>
