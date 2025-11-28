<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enhanced logging
error_log("=== INDEX.PHP ACCESS ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'));
error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Get request details
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Route handling - ONLY handle root path, delegate to other files
if ($path === '/' || $path === '/index.php') {
    // This is the root endpoint - show API info only
    if ($method === 'GET') {
        showApiInfo();
    } else {
        // For POST to root, redirect to appropriate endpoint
        showMethodInfo();
    }
} else {
    // For other paths, show 404 or redirect info
    showNotFound();
}

/**
 * Show API information
 */
function showApiInfo() {
    $response = [
        'status' => 'GoldLab Auth Server is running',
        'service' => 'Authentication API',
        'version' => '1.0',
        'timestamp' => date('c'),
        'endpoints' => [
            'GET /' => 'This information page',
            'POST /login.php' => [
                'description' => 'User authentication',
                'parameters' => [
                    'name' => 'string (required)',
                    'password' => 'string (required)', 
                    'deviceId' => 'string (required)'
                ],
                'example' => [
                    'name' => 'testuser',
                    'password' => 'testpass',
                    'deviceId' => 'device123'
                ]
            ],
            'POST /logout.php' => [
                'description' => 'User logout',
                'parameters' => [
                    'deviceId' => 'string (required)'
                ]
            ]
        ],
        'test_credentials' => [
            'testuser' => 'testpass',
            'admin' => 'admin123'
        ],
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}

/**
 * Show method information for non-GET requests to root
 */
function showMethodInfo() {
    http_response_code(405);
    $response = [
        'error' => true,
        'message' => 'Method not allowed on root endpoint',
        'code' => 405,
        'suggested_endpoints' => [
            'POST /login.php' => 'For user authentication',
            'POST /logout.php' => 'For user logout'
        ],
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}

/**
 * Show 404 for unknown paths
 */
function showNotFound() {
    http_response_code(404);
    $response = [
        'error' => true,
        'message' => 'Endpoint not found',
        'code' => 404,
        'available_endpoints' => [
            'GET /' => 'API information',
            'POST /login.php' => 'User login', 
            'POST /logout.php' => 'User logout'
        ],
        'requested_path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>
