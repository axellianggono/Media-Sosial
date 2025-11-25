<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';


function verifyUser($token, $code) {
    global $conn;

    // Prepare and execute the query to find the user by token
    $stmt = $conn->prepare("SELECT id, verification_code, verified FROM user WHERE verify_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return [
            'status' => false,
            'message' => 'Invalid verification token'
        ];
    }

    $user = $result->fetch_assoc();

    if ($user['verified']) {
        return [
            'status' => false,
            'message' => 'User already verified'
        ];
    }

    if ($user['verification_code'] !== $code) {
        return [
            'status' => false,
            'message' => 'Invalid verification code'
        ];
    }

    // Update user to set verified to true
    $updateStmt = $conn->prepare("UPDATE user SET verified = 1 WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    if ($updateStmt->execute()) {
        return [
            'status' => true,
            'message' => 'User verified successfully'
        ];
    } else {
        return [
            'status' => false,
            'message' => 'Failed to verify user'
        ];
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $token = $data['verify_token'] ?? '';
        $code = $data['verify_code'] ?? '';

        $result = verifyUser($token, $code);

        if ($result['status']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        echo json_encode(['message' => $result['message']]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}