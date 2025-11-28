<?php
header('Content-Type: text/plain');

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

echo "Fixing database schema...\n";

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Drop table if exists
    echo "Dropping existing table...\n";
    $conn->query("DROP TABLE IF EXISTS users");
    
    // Create new table with correct structure
    echo "Creating new table...\n";
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        gender ENUM('male', 'female', 'other') DEFAULT 'male',
        deviceId VARCHAR(255) DEFAULT NULL,
        status ENUM('0', '1') DEFAULT '0',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Table created successfully!\n";
        
        // Insert test users
        echo "Inserting test users...\n";
        $conn->query("INSERT INTO users (name, email, password, gender) VALUES 
            ('testuser', 'test@example.com', 'testpass', 'male'),
            ('admin', 'admin@example.com', 'admin123', 'male')");
        
        echo "✅ Test users inserted!\n";
        echo "🎉 Database fixed! You can now test login.\n";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
