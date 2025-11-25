<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';

function getUser($token) {
    global $conn, $env;

    if (empty($token)) {
        return ['status' => false, 'message' => 'Token tidak disediakan.'];
    }

    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.email, u.profile_photo, u.role, t.created_at 
        FROM user u
        JOIN token t ON u.id = t.user_id
        WHERE t.token = ?
    ");

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        return ['status' => false, 'message' => 'Token tidak valid atau tidak ditemukan.'];
    }

    $user = $result->fetch_assoc();

    $user['profile_photo'] = $env['BASE_URL'] . '/api/storage/images/' . ($user['profile_photo'] ?: 'default.jpg');

    return [
        'status' => true,
        'user' => $user
    ];
}


switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization token not provided']);
            exit;
        }

        $token = $matches[1];
        $userResult = getUser($token);

        if ($userResult['status']) {
            http_response_code(200);
            echo json_encode(['data' => $userResult['user']]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => $userResult['message']]);
        }
        break;


    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}
