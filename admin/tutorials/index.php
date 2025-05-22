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

// GET: All tutorials or one
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM tutorials WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Tutorial not found']);
            exit;
        }

        echo json_encode($result->fetch_assoc());
    } else {
        $result = $conn->query("SELECT * FROM tutorials ORDER BY created_at DESC");
        $tutorials = [];
        while ($row = $result->fetch_assoc()) {
            $tutorials[] = $row;
        }
        echo json_encode($tutorials);
    }
}

// POST: Create tutorial
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO tutorials (title, description, thumbnail, category, duration) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data['title'], $data['description'], $data['thumbnail'], $data['category'], $data['duration']);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        echo json_encode(['id' => $newId]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create tutorial']);
    }
}

// PUT: Update tutorial
else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("UPDATE tutorials SET title=?, description=?, thumbnail=?, category=?, duration=? WHERE id=?");
    $stmt->bind_param("sssssi", $data['title'], $data['description'], $data['thumbnail'], $data['category'], $data['duration'], $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update tutorial']);
    }
}

// DELETE: Delete tutorial
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
    $stmt = $conn->prepare("DELETE FROM tutorials WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete tutorial']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
