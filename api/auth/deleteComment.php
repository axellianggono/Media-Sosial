<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';
require __DIR__ . '/../auth/middleware.php';


function deleteComment($comment_id) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM comment WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        return ["success" => true, "message" => "Comment deleted successfully."];
    } else {
        return ["success" => false, "message" => "Comment not found or could not be deleted."];
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'DELETE':
        $headers = getallheaders();

        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            if (!validateToken($token) || !isAdmin($token) && !isSuperAdmin($token)) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized access."]);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Authorization header not found."]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $comment_id = intval($data['comment_id'] ?? 0);

        try {
            $response = deleteComment($comment_id);
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
