<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');

// Parse input data
$data = [];
if (!empty($input)) {
    parse_str($input, $data);
}

error_log("Request - Method: $method, Data: " . json_encode($data));

// Handle LOGOUT requests
if ($method === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    error_log("Logout - Device: $deviceId");
    
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
    $deviceId = $data['deviceId'] ?? 'unknown';
    
    error_log("Login attempt - Username: $username, Device: $deviceId");
    
    // Simple hardcoded authentication
    $valid_users = [
        'admin' => 'admin123',
        'userx' => 'userx123', 
        'test' => 'test123'
    ];
    
    if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
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
        $response = [
            'error' => true,
            'message' => 'Invalid username or password'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Default response
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => 'None - using simple authentication',
    'endpoints' => [
        'login' => 'POST with: name, password, deviceId',
        'logout' => 'POST with: deviceId'
    ]
]);
?>
