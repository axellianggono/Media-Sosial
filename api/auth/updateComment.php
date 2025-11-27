<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';

function updateCommentContent($commentId, $userId, $content) {
    global $conn;

    $stmt = $conn->prepare("UPDATE comment SET content = ? WHERE comment_id = ? AND user_id = ?");
    $stmt->bind_param("sii", $content, $commentId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        return ["success" => true, "message" => "Comment updated successfully."];
    } else {
        return ["success" => false, "message" => "Comment not found or not owned by user."];
    }
}

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
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Invalid token."]);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Authorization header not found."]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $commentId = intval($data['comment_id'] ?? 0);
        $content = trim($data['content'] ?? '');

        if ($commentId <= 0 || $content === '') {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid data."]);
            exit;
        }

        $userId = getUserIdFromToken($token);
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["success" => false, "message" => "Invalid token."]);
            exit;
        }

        try {
            $response = updateCommentContent($commentId, $userId, $content);
        } catch (Exception $e) {
            http_response_code(500);
            $response = ["success" => false, "message" => "Server error: " . $e->getMessage()];
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
