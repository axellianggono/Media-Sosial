<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

use Ramsey\Uuid\Uuid;

function generateToken($userId) {
    global $conn, $env;

    $token = Uuid::uuid4()->toString();

    // check if token already exists
    $stmt = $conn->prepare("SELECT token FROM token WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // update existing token
        $stmt = $conn->prepare("UPDATE token SET token = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("si", $token, $userId);
        $stmt->execute();
    } else {
        // insert new token
        $stmt = $conn->prepare("INSERT INTO token (user_id, token, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $userId, $token);
        $stmt->execute();
    }

    return $token;
}

function loginUser($email, $password) {
    global $conn, $env;

    $stmt = $conn->prepare("SELECT user_id, username, email, password, verified_status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ["success" => false, "message" => "Email atau password salah."];
    }

    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        return ["success" => false, "message" => "Email atau password salah."];
    }

    if (!$user['verified_status']) {
        return ["success" => false, "message" => "Akun belum terverifikasi. Silakan verifikasi email Anda."];
    }

    $token = generateToken($user['user_id']);

    return [
        "success" => true,
        "message" => "Login berhasil.",
        "data" => [
            "user_id" => $user['user_id'],
            "username" => $user['username'],
            "email" => $user['email'],
            "token" => $token
        ]
    ];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        try {
            $response = loginUser($email, $password);
        } catch (Exception $e) {
            http_response_code(500);
            $response = ["success" => false, "message" => "Terjadi kesalahan server: " . $e->getMessage()];
        }
        
        header('Content-Type: application/json');
        http_response_code($response['success'] ? 200 : 401);
        echo json_encode($response);
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}