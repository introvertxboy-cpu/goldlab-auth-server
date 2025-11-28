<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// MySQL Database Configuration
class Database {
    private $pdo;
    
    public function __construct() {
        // Use your Railway MySQL credentials
        $host = 'maglev.proxy.rlwy.net';
        $port = '56342';
        $dbname = 'railway';
        $username = 'root';
        $password = 'hFkfxpOcUtVkXyYrfJMEGSNlPDvcJZIe';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Continue without database - use simple auth as fallback
        }
    }
    
    private function createTables() {
        // Users table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Sessions table - for single device per user
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                device_id VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                logout_time TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        
        $this->createDefaultUsers();
    }
    
    private function createDefaultUsers() {
        $defaultUsers = [
            ['admin', 'admin123', 'admin@goldlab.com'],
            ['user', 'user123', 'user@goldlab.com'],
            ['test', 'test123', 'test@goldlab.com']
        ];
        
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO users (username, password, email) VALUES (?, ?, ?)");
        
        foreach ($defaultUsers as $user) {
            $stmt->execute($user);
        }
    }
    
    public function authenticate($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['password'] === $password) {
            return $user;
        }
        return false;
    }
    
    public function createSession($user_id, $device_id) {
        // Logout from all other devices for this user
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        
        // Create new session
        $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, device_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $device_id]);
    }
    
    public function logoutSession($device_id) {
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE device_id = ? AND is_active = 1");
        return $stmt->execute([$device_id]);
    }
    
    public function isUserLoggedInElsewhere($user_id, $current_device_id) {
        $stmt = $this->pdo->prepare("SELECT device_id FROM user_sessions WHERE user_id = ? AND is_active = 1 AND device_id != ?");
        $stmt->execute([$user_id, $current_device_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Initialize database
try {
    $db = new Database();
    $databaseEnabled = true;
} catch (Exception $e) {
    $databaseEnabled = false;
    error_log("Database disabled: " . $e->getMessage());
}

// Route requests
$path = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Handle login requests
if ($method === 'POST') {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        parse_str($input, $data);
    }
    
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? '';
    
    error_log("Login attempt - Username: $username, Device: $deviceId, DB: " . ($databaseEnabled ? "Enabled" : "Disabled"));
    
    // Simple authentication as fallback
    $valid_users = [
        'admin' => 'admin123',
        'user' => 'user123', 
        'test' => 'test123'
    ];
    
    $authenticated = isset($valid_users[$username]) && $valid_users[$username] === $password;
    
    if ($authenticated) {
        // Login successful
        if ($databaseEnabled) {
            try {
                // Use database for session management
                $user = $db->authenticate($username, $password);
                if ($user) {
                    // Check if user is already logged in on another device
                    $existingSession = $db->isUserLoggedInElsewhere($user['id'], $deviceId);
                    
                    if ($existingSession) {
                        $response = [
                            'error' => true,
                            'message' => 'User already logged in on another device',
                            'database' => 'active'
                        ];
                    } else {
                        // Create new session
                        $db->createSession($user['id'], $deviceId);
                        
                        $response = [
                            'error' => false,
                            'message' => 'Login successful (Database)',
                            'user' => [
                                'id' => $user['id'],
                                'name' => $user['username'],
                                'email' => $user['email'],
                                'gender' => 'male',
                                'deviceId' => $deviceId,
                                'status' => '1'
                            ],
                            'database' => 'active'
                        ];
                    }
                } else {
                    throw new Exception("Database authentication failed");
                }
            } catch (Exception $e) {
                error_log("Database error, using simple auth: " . $e->getMessage());
                // Fallback to simple authentication
                $response = [
                    'error' => false,
                    'message' => 'Login successful (Simple Auth)',
                    'user' => [
                        'id' => 1,
                        'name' => $username,
                        'email' => $username . '@goldlab.com',
                        'gender' => 'male',
                        'deviceId' => $deviceId,
                        'status' => '1'
                    ],
                    'database' => 'fallback'
                ];
            }
        } else {
            // No database - use simple authentication
            $response = [
                'error' => false,
                'message' => 'Login successful (Simple Auth)',
                'user' => [
                    'id' => 1,
                    'name' => $username,
                    'email' => $username . '@goldlab.com',
                    'gender' => 'male',
                    'deviceId' => $deviceId,
                    'status' => '1'
                ],
                'database' => 'disabled'
            ];
        }
    } else {
        // Login failed
        $response = [
            'error' => true,
            'message' => 'Invalid username or password',
            'database' => $databaseEnabled ? 'active' : 'disabled'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Handle logout requests
if ($method === 'POST' && isset($data['deviceId']) && !isset($data['name'])) {
    $deviceId = $data['deviceId'] ?? '';
    
    if ($databaseEnabled) {
        $db->logoutSession($deviceId);
    }
    
    $response = [
        'error' => false,
        'message' => 'Logout Successfull',
        'database' => $databaseEnabled ? 'active' : 'disabled'
    ];
    
    echo json_encode($response);
    exit;
}

// Default response for other requests
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => $databaseEnabled ? 'connected' : 'disabled',
    'endpoint' => 'POST to this URL with name, password, deviceId'
]);
?>
