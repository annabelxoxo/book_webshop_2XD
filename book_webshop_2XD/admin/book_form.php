<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
  header("Location: " . APP_URL . "login.php?redirect=" . urlencode("admin/book_form.php"));
  exit;
}
