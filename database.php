<?php
class Database {
    private $pdo;
    
    public function __construct() {
        // Railway provides MySQL, not PostgreSQL
        $databaseUrl = getenv('DATABASE_URL');
        
        if (!$databaseUrl) {
            throw new Exception("DATABASE_URL environment variable not found.");
        }
        
        // Parse MySQL connection string
        $url = parse_url($databaseUrl);
        
        $host = $url['host'];
        $port = $url['port'] ?? 3306; // Default MySQL port
        $dbname = ltrim($url['path'], '/');
        $username = $url['user'];
        $password = $url['pass'];
        
        // Create DSN for MySQL PDO
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            error_log("✅ Successfully connected to MySQL database");
        } catch (PDOException $e) {
            throw new Exception("MySQL connection failed: " . $e->getMessage());
        }
        
        $this->createTables();
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
        
        // Sessions table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                device_id VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                logout_time TIMESTAMP NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_device_id (device_id),
                INDEX idx_is_active (is_active),
                INDEX idx_user_id (user_id)
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
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO users (username, password, email) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($defaultUsers as $user) {
            $stmt->execute($user);
        }
        
        error_log("✅ Default users created/verified");
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
        $stmt = $this->pdo->prepare("
            UPDATE user_sessions 
            SET is_active = 0, logout_time = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$user_id]);
        
        // Create new session
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (user_id, device_id) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$user_id, $device_id]);
    }
    
    public function logoutSession($device_id) {
        $stmt = $this->pdo->prepare("
            UPDATE user_sessions 
            SET is_active = 0, logout_time = CURRENT_TIMESTAMP 
            WHERE device_id = ? AND is_active = 1
        ");
        return $stmt->execute([$device_id]);
    }
    
    public function getActiveUserByDevice($device_id) {
        $stmt = $this->pdo->prepare("
            SELECT u.* 
            FROM users u 
            JOIN user_sessions s ON u.id = s.user_id 
            WHERE s.device_id = ? AND s.is_active = 1
        ");
        $stmt->execute([$device_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Test database connection
    public function testConnection() {
        $stmt = $this->pdo->query("SELECT 1 as test");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get database info for debugging
    public function getDatabaseInfo() {
        $stmt = $this->pdo->query("SELECT VERSION() as version, DATABASE() as dbname");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
