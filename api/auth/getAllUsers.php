<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

function getAllUsers() {
    global $conn;

    $stmt = $conn->prepare("SELECT user_id, username, email, role, created_at FROM users");
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return ["success" => true, "data" => $users];
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
                $response = getAllUsers();
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