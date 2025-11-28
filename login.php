<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get database config from Railway environment variables
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'goldlab_auth';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data (Android sends raw JSON)
    $input = json_decode(file_get_contents('php://input'), true);
    
    $name = $input['name'] ?? '';
    $password = $input['password'] ?? '';
    $deviceId = $input['deviceId'] ?? '';
    
    // Validate input
    if (empty($name) || empty($password) || empty($deviceId)) {
        http_response_code(400);
        echo json_encode([
            'error' => true,
            'message' => 'Missing required fields: name, password, deviceId'
        ]);
        exit;
    }
    
    try {
        // Database connection
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
        
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, name, email, password, gender, deviceId, status FROM users WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // User not found
            echo json_encode([
                'error' => true,
                'message' => 'Invalid username or password'
            ]);
        } else {
            $user = $result->fetch_assoc();
            
            // Verify password (plain text for now - upgrade to password_verify later)
            if ($password === $user['password']) {
                
                // Check if another device is already logged in
                if (!empty($user['deviceId']) && $user['deviceId'] !== $deviceId && $user['status'] === '1') {
                    echo json_encode([
                        'error' => true,
                        'message' => 'Another device is already logged in. Please logout first.'
                    ]);
                } else {
                    // Update deviceId and login status
                    $updateStmt = $conn->prepare("UPDATE users SET deviceId = ?, status = '1', last_login = NOW() WHERE id = ?");
                    $updateStmt->bind_param("si", $deviceId, $user['id']);
                    $updateStmt->execute();
                    
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
            } else {
                echo json_encode([
                    'error' => true,
                    'message' => 'Invalid username or password'
                ]);
            }
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'error' => true,
        'message' => 'Method not allowed. Use POST.'
    ]);
}
?>
