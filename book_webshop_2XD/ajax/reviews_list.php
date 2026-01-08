<?php
require __DIR__ . "/../includes/config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json; charset=UTF-8");

$bookId = (int)($_GET["book_id"] ?? 0);
if ($bookId < 1) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid book_id"]);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT r.user_id, r.rating, r.comment, r.created_at, u.name AS user_name
    FROM review r
    JOIN user u ON u.id = r.user_id
    WHERE r.book_id = ?
    ORDER BY r.created_at DESC
    LIMIT 50
  ");
  $stmt->execute([$bookId]);

  echo json_encode(["ok" => true, "reviews" => $stmt->fetchAll()]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Could not load reviews."]);
}
