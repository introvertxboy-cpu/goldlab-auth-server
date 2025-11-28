<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enhanced logging
error_log("=== LOGIN.PHP ACCESS ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed', 405);
    }

    // Get input data
    $input = getInputData();
    $name = $input['name'] ?? '';
    $password = $input['password'] ?? '';
    $deviceId = $input['deviceId'] ?? '';

    error_log("Login attempt - Name: $name, Device: $deviceId");

    // Validate input
    if (empty($name) || empty($password) || empty($deviceId)) {
        throw new Exception('Missing required fields: name, password, deviceId', 400);
    }

    // Simple authentication (Replace with database later)
    $valid_users = [
        'testuser' => [
            'password' => 'testpass', 
            'email' => 'test@example.com', 
            'gender' => 'male', 
            'id' => 1
        ],
        'admin' => [
            'password' => 'admin123', 
            'email' => 'admin@example.com', 
            'gender' => 'male', 
            'id' => 2
        ],
    ];

    if (isset($valid_users[$name]) && $password === $valid_users[$name]['password']) {
        $user = $valid_users[$name];
        error_log("SUCCESS: Login approved for $name with device $deviceId");
        
        sendResponse([
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
        ], 200);
    } else {
        error_log("FAILED: Invalid credentials for $name");
        throw new Exception('Invalid username or password', 401);
    }

} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    sendError($e->getMessage(), $e->getCode() ?: 500);
}

/**
 * Get input data from JSON or form data
 */
function getInputData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?? [];
    }
    
    return $_POST;
}

/**
 * Send success response
 */
function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'error' => true,
        'message' => $message,
        'code' => $code,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
    exit;
}
?>
