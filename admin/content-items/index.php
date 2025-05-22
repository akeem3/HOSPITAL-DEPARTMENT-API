<?php
require_once '../../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = getDbConnection();
$id = $_GET['id'] ?? null;
$chapterId = $_GET['chapter_id'] ?? null;

// GET content item(s)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM content_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
    } else if ($chapterId) {
        $stmt = $conn->prepare("SELECT * FROM content_items WHERE chapter_id = ? ORDER BY order_num ASC");
        $stmt->bind_param("i", $chapterId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode($items);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id or chapter_id']);
    }
}

// POST new content item
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("INSERT INTO content_items (chapter_id, title, type, content, description, duration, order_num) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isssssi",
        $data['chapter_id'],
        $data['title'],
        $data['type'],
        $data['content'],
        $data['description'],
        $data['duration'],
        $data['order_num']
    );

    if ($stmt->execute()) {
        echo json_encode(['id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create content item']);
    }
}

// PUT update
else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("UPDATE content_items SET title = ?, type = ?, content = ?, description = ?, duration = ?, order_num = ? WHERE id = ?");
    $stmt->bind_param(
        "ssssssi",
        $data['title'],
        $data['type'],
        $data['content'],
        $data['description'],
        $data['duration'],
        $data['order_num'],
        $id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update content item']);
    }
}

// DELETE
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
    $stmt = $conn->prepare("DELETE FROM content_items WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete content item']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
