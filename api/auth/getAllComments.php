<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

function getAllComments() {
    global $conn;

    $stmt = $conn->prepare("SELECT comment_id, post_id, user_id, content, created_at FROM comments ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    return ["success" => true, "data" => $comments];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token) || !isAdmin($token) && !isSuperAdmin($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized access."]);
                exit;
            }

            try {
                $response = getAllComments();
            } catch (Exception $e) {
                http_response_code(500);
                $response = ["success" => false, "message" => "Server error: " . $e->getMessage()];
            }

            header('Content-Type: application/json');
            http_response_code($response['success'] ? 200 : 400);
            echo json_encode($response);
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Authorization header not found."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}