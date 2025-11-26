<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

function getAllPosts() {
    global $conn;

    $stmt = $conn->prepare("SELECT p.post_id, p.user_id, p.title, p.caption AS content, p.picture AS image_url, p.created_at, u.username FROM post p JOIN users u ON u.user_id = p.user_id ORDER BY p.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return ["success" => true, "data" => $posts];
}

function getPostsByUser($userId) {
    global $conn;

    $stmt = $conn->prepare("SELECT p.post_id, p.user_id, p.title, p.caption AS content, p.picture AS image_url, p.created_at, u.username FROM post p JOIN users u ON u.user_id = p.user_id WHERE p.user_id = ? ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    return ["success" => true, "data" => $posts];
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
                    $response = getAllPosts();
                } else {
                    $response = getPostsByUser($userId);
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
