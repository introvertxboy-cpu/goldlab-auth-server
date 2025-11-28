<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
$servername = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'goldlab_auth';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

error_log("=== LOGOUT ATTEMPT ===");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $deviceId = $_POST['deviceId'] ?? '';
    
    error_log("Device: $deviceId");
    
    if (empty($deviceId)) {
        echo json_encode(['error' => true, 'message' => 'Device ID required']);
        exit;
    }
    
    try {
        $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
        
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }
        
        $stmt = $conn->prepare("UPDATE users SET status = '0' WHERE deviceId = ?");
        $stmt->bind_param("s", $deviceId);
        $stmt->execute();
        
        error_log("SUCCESS: Logged out device $deviceId");
        
        echo json_encode([
            'error' => false,
            'message' => 'Logout successful',
            'deviceId' => $deviceId
        ]);
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        error_log("ERROR: " . $e->getMessage());
        echo json_encode(['error' => true, 'message' => 'Logout failed']);
    }
    
} else {
    echo json_encode(['error' => true, 'message' => 'Only POST method allowed']);
}
?>
