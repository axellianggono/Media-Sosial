<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

function getAllComments() {
    global $conn;

    $stmt = $conn->prepare("
        SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at, u.username, p.title AS post_title
        FROM comment c
        JOIN users u ON u.user_id = c.user_id
        LEFT JOIN post p ON p.post_id = c.post_id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    return ["success" => true, "data" => $comments];
}

function getCommentsByUser($userId) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at, u.username, p.title AS post_title
        FROM comment c
        JOIN users u ON u.user_id = c.user_id
        LEFT JOIN post p ON p.post_id = c.post_id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    return ["success" => true, "data" => $comments];
}

function getUserIdFromToken($token) {
    global $conn;

    $stmt = $conn->prepare("SELECT user_id FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $row = $result->fetch_assoc();
    return intval($row['user_id']);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized access."]);
                exit;
            }

            $role = getUserRole($token);
            $userId = getUserIdFromToken($token);

            try {
                if ($role === 'admin' || $role === 'superadmin') {
                    $response = getAllComments();
                } else {
                    $response = getCommentsByUser($userId);
                }
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
