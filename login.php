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

error_log("=== LOGIN ATTEMPT ===");

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

    // Connect to database
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error, 500);
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, email, password, gender, deviceId, status FROM users WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("FAILED: User not found - $name");
        throw new Exception('Invalid username or password', 401);
    }

    $user = $result->fetch_assoc();
    
    // Verify password
    if ($password !== $user['password']) {
        error_log("FAILED: Wrong password for - $name");
        throw new Exception('Invalid username or password', 401);
    }

    // DEVICE ID CHECK - CORE LOGIC
    $current_device = $user['deviceId'];
    $current_status = $user['status'];
    
    error_log("Current device in DB: " . ($current_device ?: 'NULL') . ", Status: $current_status");

    // Check if another device is already logged in
    if (!empty($current_device) && $current_device !== $deviceId && $current_status === '1') {
        error_log("BLOCKED: Device $current_device is already logged in for user $name");
        
        echo json_encode([
            'error' => true,
            'message' => 'Another device is already logged in. Please logout first.'
        ]);
    } else {
        // ALLOW LOGIN - Update deviceId and status in database
        $updateStmt = $conn->prepare("UPDATE users SET deviceId = ?, status = '1', last_login = NOW() WHERE id = ?");
        $updateStmt->bind_param("si", $deviceId, $user['id']);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update login status', 500);
        }

        error_log("SUCCESS: Login approved for $name with device $deviceId");

        // Successful login response
        echo json_encode([
            'error' => false,
            'message' => 'Login successful',
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'gender' => $user['gender'],
                'deviceId' => $deviceId,
                'status' => '1'
            ]
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}

function getInputData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        return $input ?? [];
    }
    
    return $_POST;
}
?>
