<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Route requests
$path = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');

// Parse input data
$data = json_decode($input, true);
if (!$data) {
    parse_str($input, $data);
}

error_log("Request - Method: $method, Path: $path, Data: " . json_encode($data));

// Handle LOGOUT requests (from Android app)
if ($method === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Logout attempt - Device: $deviceId");
    
    // Logout logic - typically you would:
    // 1. Remove device from active sessions in database
    // 2. Clear session tokens
    // 3. Update user status
    
    // For now, just return success
    $response = [
        'error' => false,
        'message' => 'Logout Successfull'
    ];
    
    echo json_encode($response);
    exit;
}

// Handle LOGIN requests
if ($method === 'POST' && isset($data['name'])) {
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Login attempt - Username: $username, Device: $deviceId");
    
    // Simple authentication
    $valid_users = [
        'admin' => 'admin123',
        'user' => 'user123', 
        'test' => 'test123'
    ];
    
    if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
        // Login successful
        $response = [
            'error' => false,
            'message' => 'Login successful',
            'user' => [
                'id' => 1,
                'name' => $username,
                'email' => $username . '@goldlab.com',
                'gender' => 'male',
                'deviceId' => $deviceId,
                'status' => '1'
            ]
        ];
    } else {
        // Login failed
        $response = [
            'error' => true,
            'message' => 'Invalid username or password'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Default response for other requests
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'login' => 'POST with name, password, deviceId',
        'logout' => 'POST with deviceId'
    ]
]);
?>
