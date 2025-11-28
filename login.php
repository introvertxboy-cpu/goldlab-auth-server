<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
$servername = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'goldlab_auth';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

// Simple debug
error_log("Login attempt started");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $deviceId = $_POST['deviceId'] ?? '';
    
    error_log("Data received: $name, $deviceId");
    
    // Input validation
    if (empty($name) || empty($password) || empty($deviceId)) {
        echo json_encode([
            'error' => true,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    try {
        // Connect to database
        $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
        
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name, email, password, gender, deviceId, status FROM users WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'error' => true,
                'message' => 'Invalid username or password'
            ]);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if ($password !== $user['password']) {
            echo json_encode([
                'error' => true,
                'message' => 'Invalid username or password'
            ]);
            exit;
        }
        
        // Device ID check
        $current_device = $user['deviceId'];
        $current_status = $user['status'];
        
        error_log("Current device: " . ($current_device ?: 'NULL') . ", Status: $current_status");
        
        // Check if another device is already logged in
        if (!empty($current_device) && $current_device !== $deviceId && $current_status === '1') {
            error_log("BLOCKED: Device $current_device is already logged in");
            
            echo json_encode([
                'error' => true,
                'message' => 'Another device is already logged in. Please logout first.'
            ]);
        } else {
            // Allow login - update deviceId and status
            $updateStmt = $conn->prepare("UPDATE users SET deviceId = ?, status = '1', last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("si", $deviceId, $user['id']);
            $updateStmt->execute();
            
            error_log("SUCCESS: Login approved for device $deviceId");
            
            // Successful login
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
        echo json_encode([
            'error' => true,
            'message' => 'Server error'
        ]);
    }
    
} else {
    echo json_encode([
        'error' => true,
        'message' => 'Only POST method allowed'
    ]);
}
?>
