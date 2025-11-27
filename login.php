<?php
// Login endpoint - same functionality as index.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    parse_str($input, $data);
}

$username = $data['name'] ?? '';
$password = $data['password'] ?? '';
$deviceId = $data['deviceId'] ?? '';

error_log("Login.php - Username: $username");

// Simple authentication
$valid_users = [
    'admin' => 'admin123',
    'user' => 'user123', 
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
?>
