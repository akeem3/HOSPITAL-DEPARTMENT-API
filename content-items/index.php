<?php
require_once '../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = getDbConnection();

$chapterId = $_GET['chapter_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($chapterId) {
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
        echo json_encode(['error' => 'Missing chapter_id']);
        http_response_code(400);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
