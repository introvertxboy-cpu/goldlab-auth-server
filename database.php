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
        
        // Insert default users if not exists
        $this->pdo->exec("INSERT OR IGNORE INTO users (username, password, email) VALUES 
            ('admin', 'admin123', 'admin@goldlab.com'),
            ('user', 'user123', 'user@goldlab.com'),
            ('test', 'test123', 'test@goldlab.com')");
    }
    
    public function getUser($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createSession($user_id, $device_id) {
        $stmt = $this->pdo->prepare("INSERT INTO user_sessions (user_id, device_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $device_id]);
    }
    
    public function logoutSession($device_id) {
        $stmt = $this->pdo->prepare("UPDATE user_sessions SET is_active = 0, logout_time = CURRENT_TIMESTAMP WHERE device_id = ? AND is_active = 1");
        return $stmt->execute([$device_id]);
    }
    
    public function getActiveSession($device_id) {
        $stmt = $this->pdo->prepare("SELECT u.* FROM users u 
                                   JOIN user_sessions s ON u.id = s.user_id 
                                   WHERE s.device_id = ? AND s.is_active = 1");
        $stmt->execute([$device_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
