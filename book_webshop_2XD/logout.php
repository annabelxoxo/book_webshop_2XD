<?php
require __DIR__ . "/includes/config.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// alle sessie-variabelen leegmaken
$_SESSION = [];

// sessiecookie verwijderen
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params["path"],
    $params["domain"],
    $params["secure"],
    $params["httponly"]
  );
}

// sessie vernietigen
session_destroy();

// ✅ ALTIJD via APP_URL redirecten
header("Location: " . APP_URL . "login.php");
exit;
