<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';


function deleteAccount($token) {
    global $conn;

    // Get user ID from token
    $stmt = $conn->prepare("SELECT user_id FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ["success" => false, "message" => "Invalid token."];
    }

    $row = $result->fetch_assoc();
    $userId = intval($row['user_id']);

    // Delete user account
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Account deleted successfully."];
    } else {
        return ["success" => false, "message" => "Failed to delete account."];
    }
}


switch ($_SERVER['REQUEST_METHOD']) {
    case 'DELETE':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $response = deleteAccount($token);
        } else {
            $response = ["success" => false, "message" => "Authorization token not provided."];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}