<?php
require __DIR__ . "/../includes/config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json; charset=UTF-8");

function out($ok, $arr = [], $code = 200){
  http_response_code($code);
  echo json_encode(array_merge(["ok"=>$ok], $arr), JSON_UNESCAPED_UNICODE);
  exit;
}

$bookId = (int)($_GET["book_id"] ?? 0);
if ($bookId < 1) out(false, ["error" => "Invalid book_id"], 400);

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

  out(true, ["reviews" => $stmt->fetchAll()]);
} catch (Throwable $e) {
  out(false, ["error" => "Could not load reviews."], 500);
}
