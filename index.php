<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Test database connection
$databaseStatus = 'disabled';
$dbInfo = null;

try {
    require_once 'database.php';
    $db = new Database();
    $db->testConnection();
    $dbInfo = $db->getDatabaseInfo();
    $databaseStatus = 'connected';
    error_log("✅ MySQL database is connected: " . json_encode($dbInfo));
} catch (Exception $e) {
    error_log("❌ Database error: " . $e->getMessage());
    $databaseStatus = 'error: ' . $e->getMessage();
    $db = null;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = file_get_contents('php://input');

// Parse input data
$data = [];
if (!empty($input)) {
    parse_str($input, $data);
}

// Handle LOGOUT requests
if ($method === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    
    if ($db) {
        $db->logoutSession($deviceId);
    }
    
    $response = [
        'error' => false,
        'message' => 'Logout Successfull',
        'database' => $databaseStatus,
        'db_info' => $dbInfo
    ];
    
    echo json_encode($response);
    exit;
}

// Handle LOGIN requests
if ($method === 'POST' && isset($data['name'])) {
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? 'unknown';
    
    // Simple hardcoded authentication as fallback
    $valid_users = [
        'admin' => 'admin123',
        'user' => 'user123', 
        'test' => 'test123'
    ];
    
    if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
        
        // Try to use database if available
        if ($db) {
            try {
                $user = $db->authenticate($username, $password);
                if ($user) {
                    $db->createSession($user['id'], $deviceId);
                    
                    $response = [
                        'error' => false,
                        'message' => 'Login successful (MySQL database)',
                        'user' => [
                            'id' => $user['id'],
                            'name' => $user['username'],
                            'email' => $user['email'],
                            'deviceId' => $deviceId,
                            'status' => '1'
                        ],
                        'database' => $databaseStatus,
                        'db_info' => $dbInfo
                    ];
                } else {
                    throw new Exception("Database authentication failed");
                }
            } catch (Exception $e) {
                error_log("Database login failed: " . $e->getMessage());
                // Fall back to simple authentication
                $response = [
                    'error' => false,
                    'message' => 'Login successful (fallback auth)',
                    'user' => [
                        'id' => 1,
                        'name' => $username,
                        'email' => $username . '@goldlab.com',
                        'deviceId' => $deviceId,
                        'status' => '1'
                    ],
                    'database' => 'fallback: ' . $e->getMessage()
                ];
            }
        } else {
            // No database - use simple authentication
            $response = [
                'error' => false,
                'message' => 'Login successful (simple auth)',
                'user' => [
                    'id' => 1,
                    'name' => $username,
                    'email' => $username . '@goldlab.com',
                    'deviceId' => $deviceId,
                    'status' => '1'
                ],
                'database' => 'disabled'
            ];
        }
    } else {
        $response = [
            'error' => true,
            'message' => 'Invalid username or password',
            'database' => $databaseStatus
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Default response
echo json_encode([
    'status' => 'GoldLab Auth Server',
    'database' => $databaseStatus,
    'db_info' => $dbInfo,
    'timestamp' => date('Y-m-d H:i:s'),
    'message' => 'MySQL database detected'
]);
?>
