<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $deviceId = $_POST['deviceId'] ?? '';
    
    if (empty($username) || empty($password) || empty($deviceId)) {
        echo json_encode(['error' => true, 'message' => 'Missing fields']);
        exit;
    }
    
    try {
        $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
        
        if ($conn->connect_error) {
            throw new Exception('DB connection failed');
        }
        
        // Fetch user via username
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => true, 'message' => 'Invalid credentials']);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        if ($password !== $user['password']) {
            echo json_encode(['error' => true, 'message' => 'Invalid credentials']);
            exit;
        }
        
        // Device check
        if (!empty($user['deviceId']) && $user['deviceId'] !== $deviceId && $user['status'] === '1') {
            echo json_encode(['error' => true, 'message' => 'Another device is logged in']);
        } else {
            // Update device
            $update = $conn->prepare("UPDATE users SET deviceId = ?, status = '1' WHERE id = ?");
            $update->bind_param("si", $deviceId, $user['id']);
            $update->execute();
            
            echo json_encode([
                'error' => false,
                'message' => 'Login successful',
                'user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'gender' => $user['gender'],
                    'deviceId' => $deviceId,
                    'status' => '1'
                ]
            ]);
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo json_encode(['error' => true, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => true, 'message' => 'POST only']);
}
?>
