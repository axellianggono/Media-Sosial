<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

$postId = intval($_GET['post_id'] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid post ID."]);
    exit;
}

$query = "
    SELECT c.comment_id, c.content, c.created_at, c.user_id,
           u.username, u.profile_picture
    FROM comment c
    JOIN users u ON u.user_id = c.user_id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
";

global $conn;
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $comments
]);
