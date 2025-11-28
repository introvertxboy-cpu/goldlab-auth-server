<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $deviceId = $_POST['deviceId'] ?? '';
    
    file_put_contents('php://stderr', "=== LOGOUT ===\n");
    file_put_contents('php://stderr', "Device: $deviceId\n");
    
    if (empty($deviceId)) {
        echo json_encode(['error' => true, 'message' => 'Device ID required']);
        exit;
    }
    
    file_put_contents('php://stderr', "SUCCESS: Logged out device $deviceId\n");
    
    echo json_encode([
        'error' => false,
        'message' => 'Logout successful'
    ]);
    
} else {
    echo json_encode(['error' => true, 'message' => 'Only POST method allowed']);
}
?>
