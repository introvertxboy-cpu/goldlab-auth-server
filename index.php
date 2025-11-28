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
            throw new Exception("Database unavailable");
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
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_active (user_id, is_active),
                INDEX idx_device (device_id)
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
    
    // Check if user is already logged in on any device
    public function isUserAlreadyLoggedIn($user_id) {
        $stmt = $this->pdo->prepare("SELECT device_id FROM user_sessions WHERE user_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create new session and logout all other devices
    public function createSession($user_id, $device_id) {
        // Logout from ALL other devices for this user
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        
        // Create new session for current device
        $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, device_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $device_id]);
    }
    
    // Force login - kick out other devices and login on current device
    public function forceLogin($user_id, $device_id) {
        return $this->createSession($user_id, $device_id);
    }
    
    public function logoutSession($device_id) {
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE device_id = ? AND is_active = 1");
        return $stmt->execute([$device_id]);
    }
    
    // Get active device for a user
    public function getActiveDevice($user_id) {
        $stmt = $this->pdo->prepare("SELECT device_id FROM user_sessions WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['device_id'] : null;
    }
}

// Initialize database
try {
    $db = new Database();
    $databaseEnabled = true;
    error_log("✅ Database connected - Single device enforcement ACTIVE");
} catch (Exception $e) {
    $databaseEnabled = false;
    error_log("❌ Database disabled: " . $e->getMessage());
}

// Route requests
$path = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    parse_str($input, $data);
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

// Handle login requests
if ($method === 'POST' && isset($data['name'])) {
    $username = $data['name'] ?? '';
    $password = $data['password'] ?? '';
    $deviceId = $data['deviceId'] ?? '';
    $forceLogin = isset($data['forceLogin']) && $data['forceLogin'] === 'true';
    
    error_log("Login attempt - Username: $username, Device: $deviceId, Force: " . ($forceLogin ? "Yes" : "No"));
    
    if (!$databaseEnabled) {
        // Fallback to simple authentication without device限制
        $valid_users = [
            'admin' => 'admin123',
            'user' => 'user123', 
            'test' => 'test123'
        ];
        
        if (isset($valid_users[$username]) && $valid_users[$username] === $password) {
            $response = [
                'error' => false,
                'message' => 'Login successful (No database - multiple devices allowed)',
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
        } else {
            $response = [
                'error' => true,
                'message' => 'Invalid username or password'
            ];
        }
        
        echo json_encode($response);
        exit;
    }
    
    // DATABASE ENABLED - SINGLE DEVICE ENFORCEMENT
    try {
        $user = $db->authenticate($username, $password);
        
        if ($user) {
            // Check if user is already logged in on another device
            $activeDevice = $db->getActiveDevice($user['id']);
            
            if ($activeDevice) {
                // User is already logged in elsewhere
                if ($activeDevice === $deviceId) {
                    // Same device - allow login (renew session)
                    $db->createSession($user['id'], $deviceId);
                    
                    $response = [
                        'error' => false,
                        'message' => 'Login successful (Session renewed)',
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
                } else {
                    // Different device - check if force login is requested
                    if ($forceLogin) {
                        // Force login - kick out the other device
                        $db->forceLogin($user['id'], $deviceId);
                        
                        $response = [
                            'error' => false,
                            'message' => 'Login successful (Other device was logged out)',
                            'user' => [
                                'id' => $user['id'],
                                'name' => $user['username'],
                                'email' => $user['email'],
                                'gender' => 'male',
                                'deviceId' => $deviceId,
                                'status' => '1'
                            ],
                            'database' => 'active',
                            'force_login' => true
                        ];
                    } else {
                        // Block login - user is active on another device
                        $response = [
                            'error' => true,
                            'message' => 'Account is already active on another device. Use forceLogin=true to logout other device.',
                            'current_device' => $activeDevice,
                            'blocked_reason' => 'multiple_devices'
                        ];
                    }
                }
            } else {
                // No active session - allow login
                $db->createSession($user['id'], $deviceId);
                
                $response = [
                    'error' => false,
                    'message' => 'Login successful',
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
            $response = [
                'error' => true,
                'message' => 'Invalid username or password'
            ];
        }
    } catch (Exception $e) {
        error_log("Database error during login: " . $e->getMessage());
        $response = [
            'error' => true,
            'message' => 'Server error - please try again'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Default response for other requests
echo json_encode([
    'status' => 'GoldLab Auth Server is running',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => $databaseEnabled ? 'connected' : 'disabled',
    'single_device_enforcement' => $databaseEnabled ? 'ACTIVE' : 'INACTIVE',
    'endpoints' => [
        'login' => 'POST with: name, password, deviceId',
        'force_login' => 'POST with: name, password, deviceId, forceLogin=true',
        'logout' => 'POST with: deviceId'
    ]
]);
?>
