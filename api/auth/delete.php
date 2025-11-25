<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';


function deleteAccount($token) {
    global $conn;

    $stmt = $conn->prepare("SELECT user_id FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid authentication token']);
        return;
    }

    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['message' => 'Account deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to delete account']);
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'DELETE':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization token not provided']);
            exit;
        }

        $token = $matches[1];
        deleteAccount($token);
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}
