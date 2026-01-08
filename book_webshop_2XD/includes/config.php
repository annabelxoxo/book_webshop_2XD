<?php
declare(strict_types=1);
// Web root van jouw project (URL-pad)
define('APP_URL', '/book_webshop_2XD/book_webshop_2XD/');


$DB_HOST = "localhost";
$DB_NAME = "online_book_webshop_dat";
$DB_USER = "root";
$DB_PASS = "";

try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (PDOException $e) {
  die("DB connectie mislukt.");
}
