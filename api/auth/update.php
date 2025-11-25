<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../../vendor/autoload.php';

use Ramsey\Uuid\Uuid;

function usernameValidation($username) {
    if (!empty($username)) {
        if (strlen($username) < 3 || strlen($username) > 20)
            return ['status' => false, 'message' => 'Username must be 3–20 characters'];
    }
    return ['status' => true];
}

function passwordValidation($newPassword, $oldPassword) {
    // Tidak boleh kirim hanya salah satu
    if (!empty($oldPassword) && empty($newPassword))
        return ['status' => false, 'message' => 'New password required'];

    if (!empty($newPassword) && empty($oldPassword))
        return ['status' => false, 'message' => 'Old password required'];

    if (!empty($newPassword) && strlen($newPassword) < 6)
        return ['status' => false, 'message' => 'New password must be at least 6 characters'];

    return ['status' => true];
}

function imageValidation($file) {
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE)
        return ['status' => true];

    if ($file['error'] !== UPLOAD_ERR_OK)
        return ['status' => false, 'message' => 'Failed to upload image'];

    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed))
        return ['status' => false, 'message' => 'Only JPG, JPEG, PNG allowed'];

    if ($file['size'] > 2 * 1024 * 1024)
        return ['status' => false, 'message' => 'Image exceeds 2MB'];

    if (!getimagesize($file['tmp_name']))
        return ['status' => false, 'message' => 'Invalid image'];

    return ['status' => true];
}

function updateUser($username, $oldPassword, $newPassword, $token, $file) {
    global $conn;

    if (empty($token))
        return ['status' => false, 'message' => 'Authorization token not provided'];

    foreach ([
        usernameValidation($username),
        passwordValidation($newPassword, $oldPassword),
        imageValidation($file)
    ] as $check) {
        if (!$check['status']) return $check;
    }

    // get user by token
    $stmt = $conn->prepare("
        SELECT u.id, u.password, u.profile_photo
        FROM user u
        JOIN token t ON u.id = t.user_id
        WHERE t.token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0)
        return ['status' => false, 'message' => 'Invalid token'];

    $user = $res->fetch_assoc();

    // Prepare update fields
    $update = [];
    $params = [];
    $types = "";

    // username
    if (!empty($username)) {
        $update[] = "username=?";
        $params[] = $username;
        $types .= "s";
    }

    // password update
    if (!empty($oldPassword) && !empty($newPassword)) {
        if (!password_verify($oldPassword, $user['password'])) {
            return ['status' => false, 'message' => 'Old password is incorrect'];
        }

        $update[] = "password=?";
        $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
        $types .= "s";
    }

    // profile photo
    if (!empty($file) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = Uuid::uuid4()->toString() . "." . $ext;

        $uploadDir = __DIR__ . "/../storage/images/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName);

        $update[] = "profile_photo=?";
        $params[] = $newFileName;
        $types .= "s";
    }

    if (empty($update))
        return ['status' => false, 'message' => 'No fields to update'];

    // finalize update
    $updateQuery = "UPDATE user SET " . implode(",", $update) . " WHERE id=?";
    $params[] = $user['id'];
    $types .= "i";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return ['status' => true, 'message' => 'User updated successfully'];
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === "POST" && isset($_POST['_method']) && strtoupper($_POST['_method']) === "PUT") {
    $method = "PUT";
}

switch ($method) {
    case "PUT":
        $headers = getallheaders();
        $auth = $headers["Authorization"] ?? "";

        if (!preg_match('/Bearer\s(\S+)/', $auth, $match)) {
            http_response_code(401);
            echo json_encode(['message' => 'No token']);
            exit;
        }

        $token = $match[1];

        $username    = $_POST['username'] ?? "";
        $oldPassword = $_POST['old_password'] ?? "";
        $newPassword = $_POST['new_password'] ?? "";
        $file        = $_FILES['profile_photo'] ?? null;

        $result = updateUser($username, $oldPassword, $newPassword, $token, $file);
        http_response_code($result['status'] ? 200 : 400);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method Not Allowed']);
        break;
}
