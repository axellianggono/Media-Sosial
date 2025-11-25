<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';


function logout($token) {
    global $conn;

    // Prepare and execute the query to delete the token
    $stmt = $conn->prepare("DELETE FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    if ($stmt->execute()) {
        return [
            'status' => true,
            'message' => 'User logged out successfully'
        ];
    } else {
        return [
            'status' => false,
            'message' => 'Failed to log out user'
        ];
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization token not provided']);
            exit;
        }

        $token = $matches[1];
        $logoutResult = logout($token);

        if ($logoutResult['status']) {
            http_response_code(200);
            echo json_encode(['message' => $logoutResult['message']]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => $logoutResult['message']]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        break;
}
