<?php
require_once 'database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Initialize database
try {
    $db = new Database();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Database initialization failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');

// Parse input data - handle both JSON and form data
$data = [];
if (!empty($input)) {
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        parse_str($input, $data);
    }
}

error_log("=== NEW REQUEST ===");
error_log("Method: " . $method);
error_log("Data: " . print_r($data, true));

// Handle LOGOUT requests
if ($method === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Logout request for device: " . $deviceId);
    
    if ($db->logoutSession($deviceId)) {
        $response = [
            'error' => false,
            'message' => 'Logout Successfull'
        ];
    } else {
        $response = [
            'error' => true,
            'message' => 'No active session found'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Handle LOGIN requests
if ($method === 'POST' && isset($data['name']) && isset($data['password'])) {
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? 'unknown_device';
    
    error_log("Login attempt - Username: " . $username . ", Device: " . $deviceId);
    
    // Authenticate user
    $user = $db->authenticate($username, $password);
    
    if ($user) {
        error_log("Authentication successful for user: " . $username);
        
        // Create new session (automatically logs out other devices)
        $sessionCreated = $db->createSession($user['id'], $deviceId);
        
        if ($sessionCreated) {
            error_log("Session created successfully for device: " . $deviceId);
            
            $response = [
                'error' => false,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['username'],
                    'email' => $user['email'],
                    'gender' => 'male',
                    'deviceId' => $deviceId,
                    'status' => '1'
                ]
            ];
        } else {
            error_log("Failed to create session for device: " . $deviceId);
            $response = [
                'error' => true,
                'message' => 'Failed to create session'
            ];
        }
    } else {
        error_log("Authentication failed for user: " . $username);
        $response = [
            'error' => true,
            'message' => 'Invalid username or password'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Handle SESSION CHECK requests
if ($method === 'GET' && isset($_GET['deviceId'])) {
    $deviceId = $_GET['deviceId'] ?? '';
    $user = $db->getActiveUserByDevice($deviceId);
    
    if ($user) {
        $response = [
            'error' => false,
            'user' => $user,
            'message' => 'Active session found'
        ];
    } else {
        $response = [
            'error' => true,
            'message' => 'No active session'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Default response
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'login' => 'POST with: name, password, deviceId',
        'logout' => 'POST with: deviceId',
        'check_session' => 'GET with: deviceId parameter'
    ]
]);
?>
