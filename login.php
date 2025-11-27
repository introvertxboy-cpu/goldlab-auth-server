<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Get database credentials from Railway environment
$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$dbname = getenv('MYSQLDATABASE');

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$username = $data['name'] ?? '';
$password = $data['password'] ?? '';
$deviceId = $data['deviceId'] ?? '';

try {
    // Connect to Railway MySQL
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if users table exists, if not create it
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        gender VARCHAR(20) DEFAULT 'male',
        status INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Check if we have any users, if not insert default ones
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        // Insert default users
        $defaultUsers = [
            ['admin', 'admin123', 'admin@goldlab.com'],
            ['user', 'user123', 'user@goldlab.com'],
            ['test', 'test123', 'test@goldlab.com']
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        foreach ($defaultUsers as $userData) {
            $insertStmt->execute($userData);
        }
    }

    // Authenticate user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND status = 1");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Login successful
        $response = [
            'error' => false,
            'message' => 'Login successful',
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['username'],
                'email' => $user['email'],
                'gender' => $user['gender'],
                'deviceId' => $deviceId,
                'status' => '1'
            ]
        ];
    } else {
        // Login failed
        $response = [
            'error' => true,
            'message' => 'Invalid username or password'
        ];
    }

} catch (PDOException $e) {
    $response = [
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>
