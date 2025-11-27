<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

use Ramsey\Uuid\Uuid;

function validateUsername($username) {
    if (empty($username) || strlen($username) < 3 || strlen($username) > 20) {
        return "Username harus antara 3 hingga 20 karakter.";
    }
    return null;
}

function validatePasswordChange($oldPassword, $newPassword) {
    if (empty($oldPassword) || empty($newPassword)) {
        return "Old password and new password cannot be empty.";
    }
    if (strlen($newPassword) < 8) {
        return "New password must be at least 8 characters long.";
    }
    return null;
}

function validateImage($profileImage) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if ($profileImage['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded, so no validation needed
    }
    if ($profileImage['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading image.";
    }
    if (!in_array($profileImage['type'], $allowedTypes)) {
        return "Invalid image type. Only JPG, PNG, and GIF are allowed.";
    }
    if ($profileImage['size'] > 2 * 1024 * 1024) { // 2MB limit
        return "Image size must be less than 2MB.";
    }
    return null;
}

function saveProfileImage($profileImage, $userId) {
    $extension = pathinfo($profileImage['name'], PATHINFO_EXTENSION);
    $name = Uuid::uuid4()->toString();
    $newFileName = $name . '.' . $extension;
    $uploadDir = __DIR__ . '/../storage/images/';
    $uploadPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($profileImage['tmp_name'], $uploadPath)) {
        return null;
    }

    return $newFileName;
}

function updateProfile($token, $profileImage, $newUsername, $oldPassword, $newPassword) {
    global $conn;

    // Get user ID from token
    $stmt = $conn->prepare("SELECT user_id FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ["success" => false, "message" => "Invalid token."];
    }

    $user = $result->fetch_assoc();
    $userId = $user['user_id'];

    // update profile image if provided
    if ($profileImage && $profileImage['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageError = validateImage($profileImage);
        if ($imageError !== null) {
            return ["success" => false, "message" => $imageError];
        }

        $savedImageName = saveProfileImage($profileImage, $userId);
        if ($savedImageName === null) {
            return ["success" => false, "message" => "Failed to save profile image."];
        }

        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $savedImageName, $userId);
        if (!$stmt->execute()) {
            return ["success" => false, "message" => "Failed to update profile image."];
        }
    }

    // update username if provided
    if (!empty($newUsername)) {
        $usernameError = validateUsername($newUsername);
        if ($usernameError !== null) {
            return ["success" => false, "message" => $usernameError];
        }

        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE user_id = ?");
        $stmt->bind_param("si", $newUsername, $userId);
        if (!$stmt->execute()) {
            return ["success" => false, "message" => "Failed to update username."];
        }
    }

    // update password if provided
    if (!empty($oldPassword) && !empty($newPassword)) {
        $passwordError = validatePasswordChange($oldPassword, $newPassword);
        if ($passwordError !== null) {
            return ["success" => false, "message" => $passwordError];
        }

        // verify old password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!password_verify($oldPassword, $user['password'])) {
            return ["success" => false, "message" => "Old password is incorrect."];
        }

        // update to new password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashedNewPassword, $userId);
        if (!$stmt->execute()) {
            return ["success" => false, "message" => "Failed to update password."];
        }
    }

    return ["success" => true, "message" => "Profile updated successfully."];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Invalid token."]);
                exit;
            }

            $profileImage = $_FILES['profile_picture'] ?? null;
            $newUsername = $_POST['username'] ?? null;
            $oldPassword = $_POST['old_password'] ?? null;
            $newPassword = $_POST['new_password'] ?? null;

            $result = updateProfile($token, $profileImage, $newUsername, $oldPassword, $newPassword);

            if ($result['success']) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Authorization header not found."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}
