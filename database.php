<?php
class Database {
    private $pdo;
    
    public function __construct() {
        $host = 'maglev.proxy.rlwy.net';
        $port = '56342';
        $dbname = 'railway';
        $username = 'root';
        $password = 'hFkfxpOcUtVkXyYrfJMEGSNlPDvcJZIe';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    // ... include all the same database methods from above
    // (authenticate, createSession, logoutSession, getActiveDevice, etc.)
}
?>
