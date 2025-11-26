<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

function promoteUserToAdmin($userId) {
    global $conn;

    $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        return ["success" => true, "message" => "User promoted to admin successfully."];
    } else {
        return ["success" => false, "message" => "Failed to promote user. User may not exist."];
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $headers = getallheaders();

        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token) || !isSuperAdmin($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized access."]);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Authorization header not found."]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $userId = intval($data['userId'] ?? 0);

        try {
            $response = promoteUserToAdmin($userId);
        } catch (Exception $e) {
            http_response_code(500);
            $response = ["success" => false, "message" => "Server error: " . $e->getMessage()];
        }
        
        header('Content-Type: application/json');
        http_response_code($response['success'] ? 200 : 400);
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}