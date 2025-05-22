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
$tutorialId = $_GET['tutorial_id'] ?? null;

// GET chapters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM chapters WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else if ($tutorialId) {
        $stmt = $conn->prepare("SELECT * FROM chapters WHERE tutorial_id = ? ORDER BY order_num ASC");
        $stmt->bind_param("i", $tutorialId);
        $stmt->execute();
        $result = $stmt->get_result();
        $chapters = [];
        while ($row = $result->fetch_assoc()) {
            $chapters[] = $row;
        }
        echo json_encode($chapters);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Missing tutorial_id or id']);
    }
}

// POST create new chapter
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO chapters (tutorial_id, title, description, order_num) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $data['tutorial_id'], $data['title'], $data['description'], $data['order_num']);

    if ($stmt->execute()) {
        echo json_encode(['id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create chapter']);
    }
}

// PUT update chapter
else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("UPDATE chapters SET title = ?, description = ?, order_num = ? WHERE id = ?");
    $stmt->bind_param("ssii", $data['title'], $data['description'], $data['order_num'], $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update chapter']);
    }
}

// DELETE chapter
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
    $stmt = $conn->prepare("DELETE FROM chapters WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete chapter']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
