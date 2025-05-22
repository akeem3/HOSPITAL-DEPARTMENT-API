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

// GET - Retrieve admin users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) {
        $stmt = $conn->prepare("SELECT id, username, email FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        $result = $conn->query("SELECT id, username, email FROM admin_users");
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        echo json_encode($users);
    }
}

// POST - Create a new admin user
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $username = $data['username'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hash the password
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Username already exists']);
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $result = $conn->query("SELECT id, username, email FROM admin_users WHERE id = $newId");
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
    }
}

// PUT - Update an existing admin user
else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $types = '';
    $params = [];
    
    if (isset($data['username'])) {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $data['username'], $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
            exit;
        }
        
        $updateFields[] = "username = ?";
        $types .= "s";
        $params[] = $data['username'];
    }
    
    if (isset($data['email'])) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $data['email'], $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already exists']);
            exit;
        }
        
        $updateFields[] = "email = ?";
        $types .= "s";
        $params[] = $data['email'];
    }
    
    if (isset($data['password'])) {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $updateFields[] = "password = ?";
        $types .= "s";
        $params[] = $password;
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }
    
    $query = "UPDATE admin_users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $types .= "i";
    $params[] = $id;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $result = $conn->query("SELECT id, username, email FROM admin_users WHERE id = $id");
        $user = $result->fetch_assoc();
        echo json_encode($user);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user']);
    }
}

// DELETE - Delete an admin user
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $id) {
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user']);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>