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
        // Check if user is already logged in on another device
        $existingSession = $db->getUserActiveSession($user['id']);
        
        if ($existingSession) {
            $existingDevice = $existingSession['device_id'];
            
            // If trying to login from same device, allow it
            if ($existingDevice === $deviceId) {
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
                // User is already logged in on another device
                $response = [
                    'error' => true,
                    'message' => 'Account is already active on another device. Please logout from the other device first.'
                ];
                
                echo json_encode($response);
                exit;
            }
        } else {
            // No existing session, create new one
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
        }
    } else {
        $response = [
            'error' => true,
            'message' => 'Invalid username or password'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Handle FORCE LOGIN - allows login and kicks out other devices
if ($method === 'POST' && isset($data['name']) && isset($data['forceLogin']) && $data['forceLogin'] === 'true') {
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Force login attempt - Username: $username, Device: $deviceId");
    
    $user = $db->authenticate($username, $password);
    
    if ($user) {
        // Force logout from all other devices and login on current device
        $db->createSession($user['id'], $deviceId);
        
        $response = [
            'error' => false,
            'message' => 'Login successful (other devices were logged out)',
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

// Handle CHECK USER SESSION - check if user has active session on any device
if ($method === 'GET' && isset($_GET['username'])) {
    $username = $_GET['username'] ?? '';
    
    $user = $db->authenticate($username, ''); // We just need user ID
    if ($user) {
        $activeDevice = $db->getUserActiveDevice($user['id']);
        
        if ($activeDevice) {
            $response = [
                'error' => false,
                'hasActiveSession' => true,
                'activeDevice' => $activeDevice,
                'message' => 'User has active session on device: ' . $activeDevice
            ];
        } else {
            $response = [
                'error' => false,
                'hasActiveSession' => false,
                'message' => 'No active session for this user'
            ];
        }
    } else {
        $response = [
            'error' => true,
            'message' => 'User not found'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Default response
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => 'SQLite',
    'features' => 'Single device per user enforced',
    'endpoints' => [
        'login' => 'POST with name, password, deviceId',
        'force_login' => 'POST with name, password, deviceId, forceLogin=true',
        'logout' => 'POST with deviceId',
        'check_session' => 'GET with deviceId parameter',
        'check_user' => 'GET with username parameter'
    ]
]);
?>
