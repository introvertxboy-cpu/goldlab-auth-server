<?php
class Database {
    private $pdo;
    
    public function __construct() {
        $db_path = __DIR__ . '/goldlab.db';
        $this->pdo = new PDO("sqlite:$db_path");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createTables();
    }
    
    private function createTables() {
        // Users table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Sessions table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            device_id TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            logout_time DATETIME,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )");
        
        // Create index for better performance
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_active ON user_sessions(user_id, is_active)");
        
        $this->createDefaultUsers();
    }
    
    private function createDefaultUsers() {
        $defaultUsers = [
            ['admin', 'admin123', 'admin@goldlab.com'],
            ['user', 'user123', 'user@goldlab.com'],
            ['test', 'test123', 'test@goldlab.com']
        ];
        
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO users (username, password, email) VALUES (?, ?, ?)");
        
        foreach ($defaultUsers as $user) {
            $stmt->execute($user);
        }
    }
    
    // Authenticate user by username and password
    public function authenticate($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['password'] === $password) {
            return $user;
        }
        return false;
    }
    
    // Create a new login session - ONLY ONE DEVICE PER USER
    public function createSession($user_id, $device_id) {
        // First, logout ALL active sessions for this user
        $this->logoutAllUserSessions($user_id);
        
        // Then create new session for the current device
        $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, device_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $device_id]);
    }
    
    // Logout ALL sessions for a specific user
    public function logoutAllUserSessions($user_id) {
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE user_id = ? AND is_active = 1");
        return $stmt->execute([$user_id]);
    }
    
    // Logout session for a specific device
    public function logoutSession($device_id) {
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE device_id = ? AND is_active = 1");
        return $stmt->execute([$device_id]);
    }
    
    // Get active user by device ID
    public function getActiveUserByDevice($device_id) {
        $stmt = $this->pdo->prepare("SELECT u.* FROM users u 
                                   JOIN user_sessions s ON u.id = s.user_id 
                                   WHERE s.device_id = ? AND s.is_active = 1");
        $stmt->execute([$device_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Check if user already has an active session on any device
    public function getUserActiveSession($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM user_sessions WHERE user_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all active sessions (for admin purposes)
    public function getActiveSessions() {
        $stmt = $this->pdo->prepare("SELECT u.username, s.device_id, s.login_time 
                                   FROM user_sessions s 
                                   JOIN users u ON s.user_id = u.id 
                                   WHERE s.is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user's active device
    public function getUserActiveDevice($user_id) {
        $stmt = $this->pdo->prepare("SELECT device_id FROM user_sessions WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['device_id'] : null;
    }
}
?>
