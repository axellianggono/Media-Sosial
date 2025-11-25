<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

function generateToken() {
    return Uuid::uuid4()->toString();
}

function saveUserToken($userId, $token) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM token WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO token (user_id, token) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $token);
    return $stmt->execute();
}

function login($email, $password) {
    global $conn;

    // Prepare and execute the query to find the user by email
    $stmt = $conn->prepare("SELECT id, username, email, password, verified FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return [
            'status' => false,
            'message' => 'Invalid email or password'
        ];
    }

    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        return [
            'status' => false,
            'message' => 'Invalid email or password'
        ];
    }

    if (!$user['verified']) {
        return [
            'status' => false,
            'message' => 'Account not verified. Please check your email.'
        ];
    }

    $token = generateToken();
    if (!saveUserToken($user['id'], $token)) {
        return [
            'status' => false,
            'message' => 'Failed to generate user token'
        ];
    }
    
    return [
        'status' => true,
        'message' => 'Login successful',
        'data' => [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'token' => $token
        ]
    ];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $loginResult = login($username, $password);

        if ($loginResult['status']) {
            http_response_code(200);
        } else {
            http_response_code(401);
        }
        echo json_encode([
            'message' => $loginResult['message'],
            'data' => $loginResult['data'] ?? null
        ]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}