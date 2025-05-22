<?php
require_once '../../db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$conn = getDbConnection();

$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (
    !isset($data['id']) ||
    !isset($data['title']) ||
    !isset($data['description']) ||
    !isset($data['thumbnail']) ||
    !isset($data['category']) ||
    !isset($data['duration'])
) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$tutorialId = $data['id'];
$title = $data['title'];
$description = $data['description'];
$thumbnail = $data['thumbnail'];
$category = $data['category'];
$duration = $data['duration'];
$chapters = $data['chapters'] ?? [];

// Update the tutorial
$stmt = $conn->prepare("
    UPDATE tutorials 
    SET title = ?, description = ?, thumbnail = ?, category = ?, duration = ?, updated_at = NOW() 
    WHERE id = ?
");
$stmt->bind_param("sssssi", $title, $description, $thumbnail, $category, $duration, $tutorialId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update tutorial"]);
    exit;
}

// Delete existing chapters and content items
$conn->query("DELETE FROM content_items WHERE chapter_id IN (SELECT id FROM chapters WHERE tutorial_id = $tutorialId)");
$conn->query("DELETE FROM chapters WHERE tutorial_id = $tutorialId");

// Re-insert chapters and content items
foreach ($chapters as $chapter) {
    $chapterTitle = $chapter['title'];
    $chapterDescription = $chapter['description'] ?? '';
    $chapterOrder = $chapter['order_num'];

    $stmt = $conn->prepare("
        INSERT INTO chapters (tutorial_id, title, description, order_num) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("issi", $tutorialId, $chapterTitle, $chapterDescription, $chapterOrder);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to insert chapter"]);
        exit;
    }

    $chapterId = $conn->insert_id;

    foreach ($chapter['contentItems'] as $item) {
        $itemTitle = $item['title'];
        $itemType = $item['type'];
        $itemContent = $item['content'];
        $itemDescription = $item['description'] ?? '';
        $itemDuration = $item['duration'] ?? '';
        $itemOrder = $item['order_num'];

        $stmt = $conn->prepare("
            INSERT INTO content_items 
            (chapter_id, title, type, content, description, duration, order_num) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssssi", $chapterId, $itemTitle, $itemType, $itemContent, $itemDescription, $itemDuration, $itemOrder);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to insert content item"]);
            exit;
        }
    }
}

echo json_encode(["success" => true, "message" => "Tutorial updated successfully"]);
