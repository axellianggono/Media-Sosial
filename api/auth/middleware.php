<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

function validateToken($token) {
    global $conn;

    $stmt = $conn->prepare("SELECT user_id FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function getUserRole($token) {
    global $conn;

    $stmt = $conn->prepare("SELECT u.role FROM users u JOIN token t ON u.user_id = t.user_id WHERE t.token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $user = $result->fetch_assoc();
    return $user['role'];
}

function isSuperAdmin($token) {
    $role = getUserRole($token);
    return $role === 'superadmin';
}

function isAdmin($token) {
    $role = getUserRole($token);
    return $role === 'admin';
}

function isUser($token) {
    $role = getUserRole($token);
    return $role === 'user';
}