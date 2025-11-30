<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

use Ramsey\Uuid\Uuid;

function getUserIdFromToken($token) {
    global $conn;

    $stmt = $conn->prepare("SELECT user_id FROM token WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    return intval($row['user_id']);
}

function validateTitle($title) {
    if ($title === '' || $title === null) {
        return "Title is required.";
    }
    if (strlen($title) > 100) {
        return "Title must be at most 100 characters.";
    }
    return null;
}

function validateImage($image) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (empty($image['name']) || $image['error'] === UPLOAD_ERR_NO_FILE || empty($image['tmp_name']) || $image['size'] === 0) {
        return "NO_FILE";
    }
    if ($image['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading image.";
    }
    if (!in_array($image['type'], $allowedTypes)) {
        return "Invalid image type. Only JPG, PNG, and GIF are allowed.";
    }
    if ($image['size'] > 2 * 1024 * 1024) {
        return "Image size must be less than 2MB.";
    }
    return null;
}

function saveImage($image) {
    $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
    $name = Uuid::uuid4()->toString();
    $newFileName = $name . '.' . $extension;
    $uploadDir = __DIR__ . '/../storage/images/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            return null;
        }
    }
    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            return null;
        }
    }
    $uploadPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
        $data = @file_get_contents($image['tmp_name']);
        if ($data === false) {
            return null;
        }
        if (@file_put_contents($uploadPath, $data) === false) {
            return null;
        }
    }

    return $newFileName;
}

function createLocation($lat, $lon) {
    if ($lat === null || $lon === null || $lat === '' || $lon === '') {
        return null;
    }
    global $conn;
    $stmt = $conn->prepare("INSERT INTO location (latitude, longitude) VALUES (?, ?)");
    $stmt->bind_param("dd", $lat, $lon);
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return null;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } elseif (!empty($_POST['token'])) {
            $token = $_POST['token'];
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Authorization header not found."]);
            exit;
        }

        if (!validateToken($token)) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid token."]);
            exit;
        }

        $userId = getUserIdFromToken($token);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid token."]);
            exit;
        }

        $postId = intval($_POST['post_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $lat = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
        $lon = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
        $image = $_FILES['picture'] ?? null;

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid post ID."]);
            exit;
        }

        $titleError = validateTitle($title);
        if ($titleError !== null) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $titleError]);
            exit;
        }

        $imageError = $image ? validateImage($image) : "NO_FILE";
        if ($imageError !== null && $imageError !== "NO_FILE") {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => $imageError]);
            exit;
        }

        // verify ownership
        global $conn;
        $stmt = $conn->prepare("SELECT user_id FROM post WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Post not found."]);
            exit;
        }
        $row = $res->fetch_assoc();
        if (intval($row['user_id']) !== $userId) {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "You do not have permission to update this post."]);
            exit;
        }

        if ($imageError !== "NO_FILE") {
            $savedImage = saveImage($image);
            if (!$savedImage) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to save image."]);
                exit;
            }

            $stmt = $conn->prepare("UPDATE post SET picture = ? WHERE post_id = ?");
            $stmt->bind_param("si", $savedImage, $postId);
            $stmt->execute();
        }

        if ($lat !== null && $lon !== null) {
            $locationId = createLocation($lat, $lon);
            $stmt = $conn->prepare("UPDATE post SET location_id = ? WHERE post_id = ?");
            $stmt->bind_param("ii", $locationId, $postId);
            $stmt->execute();
        }

        $stmt = $conn->prepare("UPDATE post SET title = ?, caption = ? WHERE post_id = ?");
        $stmt->bind_param("ssi", $title, $caption, $postId);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Post updated successfully."]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}
