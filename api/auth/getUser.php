<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/middleware.php';

function getUserFromToken($token) {
    global $conn, $env;

    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.email, u.role, u.verified_status, u.created_at FROM users u JOIN token t ON u.user_id = t.user_id WHERE t.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Invalid token."]);
                exit;
            }

            $user = getUserFromToken($token);

            $user['profile_picture'] = isset($user['profile_picture']) ? $env['BASE_URL'] . '/api/storage/images/' . $user['profile_picture'] : $env['BASE_URL'] . '/api/storage/images/' . 'default.jpg';

            if ($user) {
                header('Content-Type: application/json');
                echo json_encode(["success" => true, "data" => $user]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "User not found."]);
            }
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