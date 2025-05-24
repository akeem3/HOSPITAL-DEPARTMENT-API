<?php
require_once '../../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// GET all blog images
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $result = $conn->query("SELECT * FROM blog_images ORDER BY created_at DESC");
  $slides = [];
  while ($row = $result->fetch_assoc()) {
    $slides[] = $row;
  }
  echo json_encode($slides);
}

// POST new blog image
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = json_decode(file_get_contents("php://input"), true);
  if (!isset($data['image_url'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Image URL is required']);
    exit;
  }

  $stmt = $conn->prepare("INSERT INTO blog_images (image_url) VALUES (?)");
  $stmt->bind_param("s", $data['image_url']);
  if ($stmt->execute()) {
    echo json_encode(['id' => $conn->insert_id]);
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add image']);
  }
}

// DELETE blog image
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
  $id = $_GET['id'];
  $stmt = $conn->prepare("DELETE FROM blog_images WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    echo json_encode(['success' => true]);
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete image']);
  }
}

$conn->close();
?>
