<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

$postId = intval($_GET['id'] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid post ID."]);
    exit;
}

$query = "
    SELECT p.post_id, p.user_id, p.title, p.caption AS content, p.picture AS image_url,
           p.created_at, u.username, u.email, u.profile_picture,
           l.city, l.country,
           COUNT(c.comment_id) AS comment_count
    FROM post p
    JOIN users u ON u.user_id = p.user_id
    LEFT JOIN location l ON l.location_id = p.location_id
    LEFT JOIN comment c ON c.post_id = p.post_id
    WHERE p.post_id = ?
    GROUP BY p.post_id
";

global $conn;
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Post not found."]);
    exit;
}

$post = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "data" => $post
]);
