<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

file_put_contents('php://stderr', "=== LOGIN ATTEMPT ===\n");
file_put_contents('php://stderr', "Time: " . date('Y-m-d H:i:s') . "\n");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $deviceId = $_POST['deviceId'] ?? '';
    
    file_put_contents('php://stderr', "Name: $name, Device: $deviceId\n");
    
    if (empty($name) || empty($password) || empty($deviceId)) {
        file_put_contents('php://stderr', "ERROR: Missing fields\n");
        echo json_encode([
            'error' => true,
            'message' => 'Missing required fields: name, password, deviceId'
        ]);
        exit;
    }
    
    $valid_users = [
        'testuser' => [
            'password' => 'testpass', 
            'email' => 'test@example.com', 
            'gender' => 'male', 
            'id' => 1,
            'deviceId' => '',
            'status' => '0'
        ],
        'admin' => [
            'password' => 'admin123', 
            'email' => 'admin@example.com', 
            'gender' => 'male', 
            'id' => 2,
            'deviceId' => '',
            'status' => '0'
        ],
    ];
    
    if (isset($valid_users[$name]) && $password === $valid_users[$name]['password']) {
        $user = $valid_users[$name];
        
        $current_device = $user['deviceId'];
        $current_status = $user['status'];
        
        file_put_contents('php://stderr', "Current device: $current_device, Status: $current_status\n");
        
        if (!empty($current_device) && $current_device !== $deviceId && $current_status === '1') {
            file_put_contents('php://stderr', "BLOCKED: Another device ($current_device) is logged in\n");
            
            echo json_encode([
                'error' => true,
                'message' => 'Another device is already logged in. Please logout first.'
            ]);
        } else {
            $valid_users[$name]['deviceId'] = $deviceId;
            $valid_users[$name]['status'] = '1';
            
            file_put_contents('php://stderr', "SUCCESS: Login approved for device $deviceId\n");
            
            echo json_encode([
                'error' => false,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'name' => $name,
                    'email' => $user['email'],
                    'gender' => $user['gender'],
                    'deviceId' => $deviceId,
                    'status' => '1'
                ]
            ]);
        }
        
    } else {
        file_put_contents('php://stderr', "FAILED: Invalid credentials for $name\n");
        
        echo json_encode([
            'error' => true,
            'message' => 'Invalid username or password'
        ]);
    }
    
} else {
    echo json_encode([
        'error' => true,
        'message' => 'Only POST method allowed'
    ]);
}
?>
