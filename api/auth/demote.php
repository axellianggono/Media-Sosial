<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';

function demoteToGuest($token, $emailToDemote) {
    global $conn;

    // Validate token and get user role
    $stmt = $conn->prepare("
        SELECT u.role 
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
    if ($user['role'] !== 'superadmin') {
        return ['status' => false, 'message' => 'Akses ditolak.'];
    }

    // Demote user to guest
    $stmt = $conn->prepare("UPDATE user SET role = 'guest' WHERE email = ?");
    $stmt->bind_param("s", $emailToDemote);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        return ['status' => true, 'message' => 'User demoted to guest successfully.'];
    } else {
        $stmt->close();
        return ['status' => false, 'message' => 'Failed to demote user. User may not exist.'];
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
        $data = json_decode(file_get_contents('php://input'), true);
        $emailToDemote = $data['email'] ?? '';

        if (empty($emailToDemote)) {
            http_response_code(400);
            echo json_encode(['message' => 'Email to demote is required']);
            exit;
        }

        $demoteResult = demoteToGuest($token, $emailToDemote);

        if ($demoteResult['status']) {
            http_response_code(200);
            echo json_encode(['message' => $demoteResult['message']]);
        } else {
            http_response_code(400);
            echo json_encode(['message' => $demoteResult['message']]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}