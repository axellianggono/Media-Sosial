<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../database.php';

function buildFilterQuery($sort, $email) {
    $orderBy = "p.created_at DESC";
    if ($sort === 'comments') {
        $orderBy = "comment_count DESC, p.created_at DESC";
    }

    $where = "";
    $params = [];
    $types = "";

    if ($email) {
        $where = "WHERE u.email = ?";
        $params[] = $email;
        $types .= "s";
    }

    return [$where, $orderBy, $params, $types];
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $page = intval($_GET['page'] ?? 1);
        $page = $page > 0 ? $page : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sort = $_GET['sort'] ?? 'latest';
        $email = $_GET['email'] ?? null;

        list($where, $orderBy, $params, $types) = buildFilterQuery($sort, $email);

        $query = "
            SELECT p.post_id, p.user_id, p.title, p.caption AS content, p.picture AS image_url,
                   p.created_at, u.username, u.email,
                   COUNT(c.comment_id) AS comment_count
            FROM post p
            JOIN users u ON u.user_id = p.user_id
            LEFT JOIN comment c ON c.post_id = p.post_id
            $where
            GROUP BY p.post_id
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ";

        global $conn;
        $stmt = $conn->prepare($query);

        if ($types) {
            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $posts = [];
        while ($row = $res->fetch_assoc()) {
            $posts[] = $row;
        }

        // total count
        $countQuery = "SELECT COUNT(*) AS total FROM post p JOIN users u ON u.user_id = p.user_id $where";
        $countStmt = $conn->prepare($countQuery);
        if ($types) {
            $ctypes = $email ? "s" : "";
            if ($ctypes) {
                $countStmt->bind_param($ctypes, $email);
            }
        }
        $countStmt->execute();
        $countRes = $countStmt->get_result()->fetch_assoc();
        $total = intval($countRes['total'] ?? 0);
        $totalPages = ceil($total / $limit);

        echo json_encode([
            "success" => true,
            "data" => $posts,
            "pagination" => [
                "page" => $page,
                "per_page" => $limit,
                "total" => $total,
                "total_pages" => $totalPages
            ]
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed."]);
        break;
}
