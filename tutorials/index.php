<?php
require_once '../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = getDbConnection();

$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) {
        // Get one tutorial
        $stmt = $conn->prepare("SELECT * FROM tutorials WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $tutorialResult = $stmt->get_result();

        if ($tutorialResult->num_rows === 0) {
            echo json_encode(['error' => 'Tutorial not found']);
            http_response_code(404);
            exit;
        }

        $tutorial = $tutorialResult->fetch_assoc();

        // Fetch chapters
        $stmt = $conn->prepare("SELECT * FROM chapters WHERE tutorial_id = ? ORDER BY order_num ASC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $chaptersResult = $stmt->get_result();

        $tutorial['chapters'] = [];

        while ($chapter = $chaptersResult->fetch_assoc()) {
            $chapterId = $chapter['id'];

            // Fetch content items for chapter
            $stmtItems = $conn->prepare("SELECT * FROM content_items WHERE chapter_id = ? ORDER BY order_num ASC");
            $stmtItems->bind_param("i", $chapterId);
            $stmtItems->execute();
            $itemsResult = $stmtItems->get_result();

            $chapter['contentItems'] = [];
            while ($item = $itemsResult->fetch_assoc()) {
                $chapter['contentItems'][] = $item;
            }

            $tutorial['chapters'][] = $chapter;
        }

        echo json_encode($tutorial);
    } else {
        // Return all tutorials
        $result = $conn->query("SELECT * FROM tutorials ORDER BY created_at DESC");
        $tutorials = [];

        while ($row = $result->fetch_assoc()) {
            $tutorials[] = $row;
        }

        echo json_encode($tutorials);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
