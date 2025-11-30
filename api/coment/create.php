<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

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

function validateComment($content) {
    if ($content === null || trim($content) === '') {
        return "Komentar tidak boleh kosong.";
    }
    if (strlen($content) > 100) {
        return "Komentar maksimal 100 karakter.";
    }
    return null;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            $data = $_POST;
        }

        $tokenParam = $data['token'] ?? null;

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } elseif (!empty($tokenParam)) {
            $token = $tokenParam;
        } else {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Authorization header not found."));
            exit;
        }

        if (!validateToken($token)) {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Invalid token."));
            exit;
        }

        $userId = getUserIdFromToken($token);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(array("success" => false, "message" => "Invalid token."));
            exit;
        }

        $postId = intval($data['post_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Invalid post ID."));
            exit;
        }

        $contentError = validateComment($content);
        if ($contentError !== null) {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => $contentError));
            exit;
        }

        global $conn;
        $stmt = $conn->prepare("INSERT INTO comment (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $postId, $userId, $content);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Failed to save comment."));
            exit;
        }

        echo json_encode(array("success" => true, "message" => "Comment created successfully."));
        break;

    default:
        http_response_code(405);
        echo json_encode(array("success" => false, "message" => "Method not allowed."));
        break;
}
