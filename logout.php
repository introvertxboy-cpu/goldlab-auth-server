<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enhanced logging
file_put_contents('php://stderr', "=== LOGOUT REQUEST ===\n");
file_put_contents('php://stderr', "Time: " . date('Y-m-d H:i:s') . "\n");
file_put_contents('php://stderr', "IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n");
file_put_contents('php://stderr', "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // Get input data with validation
    $input = getInputData();
    $deviceId = $input['deviceId'] ?? '';

    file_put_contents('php://stderr', "Device ID: " . ($deviceId ?: 'NOT PROVIDED') . "\n");

    // Validate required fields
    if (empty($deviceId)) {
        throw new Exception('Device ID is required', 400);
    }

    if (strlen($deviceId) > 255) {
        throw new Exception('Device ID is too long', 400);
    }

    // Process logout
    $result = processLogout($deviceId);

    if ($result['success']) {
        file_put_contents('php://stderr', "SUCCESS: Logged out device: $deviceId\n");
        
        sendResponse([
            'error' => false,
            'message' => 'Logout successful',
            'deviceId' => $deviceId,
            'timestamp' => time()
        ], 200);
    } else {
        throw new Exception($result['message'] ?? 'Logout failed', 500);
    }

} catch (Exception $e) {
    handleError($e);
}

/**
 * Get and validate input data from multiple sources
 */
function getInputData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Handle JSON input
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data', 400);
        }
        return $input ?? [];
    }
    
    // Handle form data
    return $_POST;
}

/**
 * Process the logout request
 */
function processLogout($deviceId) {
    // In a real implementation, you would:
    // 1. Connect to database
    // 2. Update user status to '0' for this deviceId
    // 3. Clear the deviceId field or keep it for tracking
    
    file_put_contents('php://stderr', "Processing logout for device: $deviceId\n");
    
    // Simulate database operation
    // $stmt = $conn->prepare("UPDATE users SET status = '0' WHERE deviceId = ?");
    // $result = $stmt->execute([$deviceId]);
    
    $simulatedSuccess = true; // Replace with actual database operation
    
    if ($simulatedSuccess) {
        return [
            'success' => true,
            'message' => 'Device logged out successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Device not found or already logged out'
        ];
    }
}

/**
 * Send standardized JSON response
 */
function sendResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Handle errors with proper logging and response
 */
function handleError(Exception $e) {
    $errorCode = $e->getCode() ?: 500;
    $errorMessage = $e->getMessage();
    
    // Log the error with stack trace for internal errors
    if ($errorCode >= 500) {
        file_put_contents('php://stderr', "ERROR [$errorCode]: $errorMessage\n");
        file_put_contents('php://stderr', "Stack trace: " . $e->getTraceAsString() . "\n");
        $userMessage = 'Internal server error';
    } else {
        file_put_contents('php://stderr', "CLIENT ERROR [$errorCode]: $errorMessage\n");
        $userMessage = $errorMessage;
    }
    
    sendResponse([
        'error' => true,
        'message' => $userMessage,
        'code' => $errorCode,
        'timestamp' => time()
    ], $errorCode);
}

/**
 * Security headers for additional protection
 */
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>
