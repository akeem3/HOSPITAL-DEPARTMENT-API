<?php
require_once '../db.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = getDbConnection();

$tutorialId = $_GET['tutorial_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($tutorialId) {
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
        echo json_encode(['error' => 'Missing tutorial_id']);
        http_response_code(400);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>
