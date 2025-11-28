<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

// Database connection
$servername = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'goldlab_auth';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

error_log("=== LOGOUT ATTEMPT ===");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed', 405);
    }

    $input = getInputData();
    $deviceId = $input['deviceId'] ?? '';

    error_log("Logout attempt - Device: $deviceId");

    if (empty($deviceId)) {
        throw new Exception('Device ID is required', 400);
    }

    // Connect to database
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed', 500);
    }

    // Update status to '0' (logged out) for this device
    $stmt = $conn->prepare("UPDATE users SET status = '0' WHERE deviceId = ?");
    $stmt->bind_param("s", $deviceId);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        error_log("SUCCESS: Logged out device $deviceId (affected rows: $affected)");
        echo json_encode([
            'error' => false,
            'message' => 'Logout successful',
            'deviceId' => $deviceId
        ]);
    } else {
        throw new Exception('Logout failed', 500);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

function getInputData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    return $_POST;
}
?>
