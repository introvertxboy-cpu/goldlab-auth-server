<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

error_log("=== LOGIN DEBUG START ===");

// Get all environment variables for debugging
$mysql_host = getenv('MYSQLHOST');
$mysql_port = getenv('MYSQLPORT');
$mysql_db = getenv('MYSQLDATABASE');
$mysql_user = getenv('MYSQLUSER');
$mysql_pass = getenv('MYSQLPASSWORD');

error_log("MYSQLHOST: " . ($mysql_host ?: 'NOT SET'));
error_log("MYSQLPORT: " . ($mysql_port ?: 'NOT SET'));
error_log("MYSQLDATABASE: " . ($mysql_db ?: 'NOT SET'));
error_log("MYSQLUSER: " . ($mysql_user ?: 'NOT SET'));
error_log("MYSQLPASSWORD: " . ($mysql_pass ? 'SET' : 'NOT SET'));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $deviceId = $_POST['deviceId'] ?? '';
    
    error_log("Login attempt: $name, $deviceId");
    
    // Input validation
    if (empty($name) || empty($password) || empty($deviceId)) {
        echo json_encode([
            'error' => true,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Try database connection
    try {
        $conn = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_db, $mysql_port);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            throw new Exception('Database connection failed');
        }
        
        error_log("Database connected successfully!");
        
        // Check if users table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'users'");
        if ($table_check->num_rows === 0) {
            error_log("ERROR: 'users' table does not exist!");
            echo json_encode([
                'error' => true,
                'message' => 'Database not properly setup'
            ]);
            exit;
        }
        
        error_log("Users table exists, checking user...");
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name, email, password, gender, deviceId, status FROM users WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("User not found: $name");
            echo json_encode([
                'error' => true,
                'message' => 'Invalid username or password'
            ]);
            exit;
        }
        
        $user = $result->fetch_assoc();
        error_log("User found: " . $user['name']);
        
        // Verify password
        if ($password !== $user['password']) {
            error_log("Wrong password for: $name");
            echo json_encode([
                'error' => true,
                'message' => 'Invalid username or password'
            ]);
            exit;
        }
        
        error_log("Password correct, checking device...");
        
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
        error_log("EXCEPTION: " . $e->getMessage());
        echo json_encode([
            'error' => true,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'error' => true,
        'message' => 'Only POST method allowed'
    ]);
}

error_log("=== LOGIN DEBUG END ===");
?>
