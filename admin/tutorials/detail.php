<?php
require_once '../../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$conn = getDbConnection();

$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $id) {
    // Fetch tutorial
    $stmt = $conn->prepare("SELECT * FROM tutorials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Tutorial not found']);
        exit;
    }

    $tutorial = $result->fetch_assoc();

    // Fetch chapters
    $stmt = $conn->prepare("SELECT * FROM chapters WHERE tutorial_id = ? ORDER BY order_num ASC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $chaptersResult = $stmt->get_result();

    $chapters = [];
    while ($chapter = $chaptersResult->fetch_assoc()) {
        // Fetch content items for each chapter
        $chapterId = $chapter['id'];
        $stmt2 = $conn->prepare("SELECT * FROM content_items WHERE chapter_id = ? ORDER BY order_num ASC");
        $stmt2->bind_param("i", $chapterId);
        $stmt2->execute();
        $itemsResult = $stmt2->get_result();

        $contentItems = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $contentItems[] = $item;
        }

        $chapter['contentItems'] = $contentItems;
        $chapters[] = $chapter;
    }

    $tutorial['chapters'] = $chapters;
    echo json_encode($tutorial);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid ID']);
}

$conn->close();
