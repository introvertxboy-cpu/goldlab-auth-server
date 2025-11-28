<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Simple debug logging
file_put_contents('php://stderr', "Login attempt: " . date('Y-m-d H:i:s') . "\n");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get raw POST data (Android sends as form data)
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $deviceId = $_POST['deviceId'] ?? '';
    
    file_put_contents('php://stderr', "Received - Name: $name, Device: $deviceId\n");
    
    // Input validation
    if (empty($name) || empty($password) || empty($deviceId)) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // SIMPLE HARDCODED AUTH - CHANGE THESE!
    $valid_users = [
        'testuser' => ['password' => 'testpass', 'email' => 'test@example.com', 'gender' => 'male', 'id' => 1],
        'admin' => ['password' => 'admin123', 'email' => 'admin@example.com', 'gender' => 'male', 'id' => 2],
    ];
    
    if (isset($valid_users[$name]) && $password === $valid_users[$name]['password']) {
        // Login successful
        $user = $valid_users[$name];
        
        echo json_encode([
            'error' => false,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $name,
                'email' => $user['email'],
                'gender' => $user['gender'],
                'deviceId' => $deviceId,
                'status' => '1'
            ]
        ]);
        
        file_put_contents('php://stderr', "Login SUCCESS for: $name\n");
        
    } else {
        // Login failed
        echo json_encode([
            'error' => true, 
            'message' => 'Invalid username or password'
        ]);
        
        file_put_contents('php://stderr', "Login FAILED for: $name\n");
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        'error' => true,
        'message' => 'Only POST method allowed'
    ]);
}
?>
