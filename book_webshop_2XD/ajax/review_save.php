<?php
require __DIR__ . "/../includes/config.php";

if (session_status() === PHP_SESSION_NONE) session_start();

header("Content-Type: application/json; charset=UTF-8");

function out($ok, $arr = [], $code = 200){
  http_response_code($code);
  echo json_encode(array_merge(["ok"=>$ok], $arr));
  exit;
}

if (empty($_SESSION["user_id"])) out(false, ["error"=>"Not logged in."], 401);

$userId = (int)$_SESSION["user_id"];

$bookId  = (int)($_POST["book_id"] ?? 0);
$rating  = (int)($_POST["rating"] ?? 0);
$comment = trim((string)($_POST["comment"] ?? ""));

if ($bookId < 1) out(false, ["error"=>"Invalid book."], 400);
if ($rating < 1 || $rating > 5) out(false, ["error"=>"Rating must be 1–5."], 400);
if ($comment === "" || mb_strlen($comment) > 1000) out(false, ["error"=>"Comment required (max 1000 chars)."], 400);

$stmt = $pdo->prepare("SELECT 1 FROM book WHERE id = ? LIMIT 1");
$stmt->execute([$bookId]);
if (!$stmt->fetchColumn()) out(false, ["error"=>"Book not found."], 404);

$stmt = $pdo->prepare("
  SELECT 1
  FROM `order` o
  JOIN order_book ob ON ob.order_id = o.id
  WHERE o.user_id = ? AND ob.book_id = ?
    AND (o.status = 'paid' OR o.status = 'completed')
  LIMIT 1
");
$stmt->execute([$userId, $bookId]);
if (!$stmt->fetchColumn()) out(false, ["error"=>"You can only review books you purchased."], 403);

try {
  $stmt = $pdo->prepare("
    INSERT INTO review (user_id, book_id, rating, comment, created_at)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
      rating = VALUES(rating),
      comment = VALUES(comment),
      created_at = NOW()
  ");
  $stmt->execute([$userId, $bookId, $rating, $comment]);

  $stmt = $pdo->prepare("SELECT name FROM user WHERE id = ? LIMIT 1");
  $stmt->execute([$userId]);
  $userName = (string)($stmt->fetchColumn() ?? "You");

  out(true, [
    "review" => [
      "user_id"   => $userId,
      "user_name" => $userName,
      "rating"    => $rating,
      "comment"   => $comment,
      "created_at"=> date("Y-m-d H:i:s")
    ]
  ]);

} catch (Throwable $e) {
  out(false, ["error"=>"Could not save review."], 500);
}
