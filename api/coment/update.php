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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'PUT':
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
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

        $commentId = intval($data['comment_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($commentId <= 0) {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Invalid data."));
            exit;
        }
        if ($content === '') {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Komentar tidak boleh kosong."));
            exit;
        }
        if (strlen($content) > 100) {
            http_response_code(400);
            echo json_encode(array("success" => false, "message" => "Komentar maksimal 100 karakter."));
            exit;
        }

        global $conn;
        $stmt = $conn->prepare("SELECT user_id FROM comment WHERE comment_id = ?");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            http_response_code(404);
            echo json_encode(array("success" => false, "message" => "Comment not found."));
            exit;
        }
        $row = $res->fetch_assoc();
        if (intval($row['user_id']) !== $userId) {
            http_response_code(403);
            echo json_encode(array("success" => false, "message" => "You do not have permission to update this comment."));
            exit;
        }

        $upd = $conn->prepare("UPDATE comment SET content = ? WHERE comment_id = ?");
        $upd->bind_param("si", $content, $commentId);
        if (!$upd->execute()) {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Failed to update comment."));
            exit;
        }

        echo json_encode(array("success" => true, "message" => "Comment updated successfully."));
        break;

    default:
        http_response_code(405);
        echo json_encode(array("success" => false, "message" => "Method not allowed."));
        break;
}
