<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

function activateUser($verificationToken, $verificationCode) {
    global $conn, $env;

    $stmt = $conn->prepare("SELECT user_id, verified_status, verification_code FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $verificationToken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ["success" => false, "message" => "Token verifikasi tidak valid."];
    }

    $user = $result->fetch_assoc();
    if ($user['verified_status']) {
        return ["success" => false, "message" => "Akun sudah terverifikasi."];
    }

    if ($user['verification_code'] !== $verificationCode) {
        return ["success" => false, "message" => "Kode verifikasi tidak valid."];
    }

    $stmt = $conn->prepare("UPDATE users SET verified_status = 1, verification_token = NULL, verification_code = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user['user_id']);
    if ($stmt->execute()) {
        return ["success" => true, "message" => "Akun berhasil diaktifkan."];
    } else {
        return ["success" => false, "message" => "Gagal mengaktifkan akun. Silakan coba lagi."];
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        $verificationToken = trim($data['verificationToken'] ?? '');
        $verificationCode = trim($data['verificationCode'] ?? '');

        try {
            $response = activateUser($verificationToken, $verificationCode);
        } catch (Exception $e) {
            http_response_code(500);
            $response = ["success" => false, "message" => "Terjadi kesalahan server: " . $e->getMessage()];
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