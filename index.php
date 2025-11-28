<?php
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

// Handle logout requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    
    // Include database for logout
    try {
        require_once 'database.php';
        $db = new Database();
        $db->logoutSession($deviceId);
        $logoutSuccess = true;
    } catch (Exception $e) {
        $logoutSuccess = false;
    }
    
    $response = [
        'error' => false,
        'message' => 'Logout Successfull',
        'database' => $logoutSuccess ? 'active' : 'disabled'
    ];
    
    echo json_encode($response);
    exit;
}

// Default response for other requests
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'login' => 'POST to /login.php with: name, password, deviceId',
        'force_login' => 'POST to /login.php with: name, password, deviceId, forceLogin=true',
        'logout' => 'POST to /index.php with: deviceId'
    ],
    'single_device_enforcement' => 'ACTIVE'
]);
?>
