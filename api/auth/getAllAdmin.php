<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';

function getAllAdmins($token) {
    global $conn, $env;

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

    // Fetch all admin users
    $stmt = $conn->prepare("
        SELECT id, username, email, profile_photo, role, created_at 
        FROM user 
        WHERE role = 'admin'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $admins = [];
    while ($admin = $result->fetch_assoc()) {
        $admin['profile_photo'] = $env['BASE_URL'] . '/api/storage/images/' . ($admin['profile_photo'] ?: 'default.jpg');
        $admins[] = $admin;
    }
    $stmt->close();

    return [
        'status' => true,
        'admins' => $admins
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
        $adminResult = getAllAdmins($token);

        if ($adminResult['status']) {
            http_response_code(200);
            echo json_encode(['data' => $adminResult['admins']]);
        } else {
            http_response_code(403);
            echo json_encode(['message' => $adminResult['message']]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}