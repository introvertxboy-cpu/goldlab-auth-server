<?php
// Include the database class
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

// Route requests
$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');

// Parse input data
$data = json_decode($input, true);
if (!$data) {
    parse_str($input, $data);
}

error_log("Request - Method: $method, Data: " . json_encode($data));

// Handle LOGOUT requests
if ($method === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Logout attempt - Device: $deviceId");
    
    if ($db->logoutSession($deviceId)) {
        $response = [
            'error' => false,
            'message' => 'Logout Successfull'
        ];
    } else {
        $response = [
            'error' => true,
            'message' => 'Logout failed - no active session'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Handle LOGIN requests
if ($method === 'POST' && isset($data['name'])) {
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Login attempt - Username: $username, Device: $deviceId");
    
    $user = $db->authenticate($username, $password);
    
    if ($user) {
        // Create session
        $db->createSession($user['id'], $deviceId);
        
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
            'user' => [
                'id' => $user['id'],
                'name' => $user['username'],
                'email' => $user['email'],
                'deviceId' => $deviceId
            ],
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

// Handle ADMIN - Get active sessions
if ($method === 'GET' && isset($_GET['admin']) && $_GET['admin'] === 'sessions') {
    $sessions = $db->getActiveSessions();
    $response = [
        'error' => false,
        'active_sessions' => $sessions,
        'count' => count($sessions)
    ];
    echo json_encode($response);
    exit;
}

// Default response
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => 'SQLite',
    'endpoints' => [
        'login' => 'POST with name, password, deviceId',
        'logout' => 'POST with deviceId',
        'check_session' => 'GET with deviceId parameter',
        'admin_sessions' => 'GET with ?admin=sessions'
    ]
]);
?>
