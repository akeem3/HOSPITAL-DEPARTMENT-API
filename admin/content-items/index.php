<?php
require_once '../../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = getDbConnection();
$id = $_GET['id'] ?? null;

// GET - Retrieve content items
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM content_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Content item not found']);
            exit;
        }
        
        $contentItem = $result->fetch_assoc();
        echo json_encode($contentItem);
    } else {
        $chapterId = $_GET['chapter_id'] ?? null;
        
        if (!$chapterId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing chapter_id parameter']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT * FROM content_items WHERE chapter_id = ? ORDER BY order_num ASC");
        $stmt->bind_param("i", $chapterId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $contentItems = [];
        while ($row = $result->fetch_assoc()) {
            $contentItems[] = $row;
        }
        
        echo json_encode($contentItems);
    }
}

// POST - Create a new content item
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['title']) || !isset($data['type']) || !isset($data['content']) || !isset($data['chapter_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $title = $data['title'];
    $type = $data['type'];
    $content = $data['content'];
    $description = $data['description'] ?? '';
    $chapterId = $data['chapter_id'];
    $orderNum = $data['order_num'] ?? 1;
    $duration = $data['duration'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO content_items (title, type, content, description, chapter_id, order_num, duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiis", $title, $type, $content, $description, $chapterId, $orderNum, $duration);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $result = $conn->query("SELECT * FROM content_items WHERE id = $newId");
        $contentItem = $result->fetch_assoc();
        echo json_encode($contentItem);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create content item']);
    }
}

// PUT - Update an existing content item
else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $types = '';
    $params = [];
    
    if (isset($data['title'])) {
        $updateFields[] = "title = ?";
        $types .= "s";
        $params[] = $data['title'];
    }
    
    if (isset($data['type'])) {
        $updateFields[] = "type = ?";
        $types .= "s";
        $params[] = $data['type'];
    }
    
    if (isset($data['content'])) {
        $updateFields[] = "content = ?";
        $types .= "s";
        $params[] = $data['content'];
    }
    
    if (isset($data['description'])) {
        $updateFields[] = "description = ?";
        $types .= "s";
        $params[] = $data['description'];
    }
    
    if (isset($data['order_num'])) {
        $updateFields[] = "order_num = ?";
        $types .= "i";
        $params[] = $data['order_num'];
    }
    
    if (isset($data['duration'])) {
        $updateFields[] = "duration = ?";
        $types .= "s";
        $params[] = $data['duration'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }
    
    $query = "UPDATE content_items SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $result = $conn->query("SELECT * FROM content_items WHERE id = $id");
        $contentItem = $result->fetch_assoc();
        echo json_encode($contentItem);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update content item']);
    }
}

// DELETE - Delete a content item
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
    $stmt = $conn->prepare("DELETE FROM content_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete content item']);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>