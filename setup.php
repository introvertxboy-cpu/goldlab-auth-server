<?php
header('Content-Type: text/plain');
echo "=== GoldLab Database Setup ===\n\n";

// Database connection details from Railway
$servername = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'goldlab_auth';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

echo "Connecting to database...\n";
echo "Host: $servername\n";
echo "Database: $db_name\n";
echo "User: $db_user\n\n";

try {
    // Connect to database
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "✅ Database connected successfully!\n\n";
    
    // Create users table
    echo "Creating 'users' table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS users (
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
        echo "✅ Table 'users' created successfully!\n";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // Insert test users
    echo "\nInserting test users...\n";
    
    $users = [
        ['testuser', 'test@example.com', 'testpass', 'male'],
        ['admin', 'admin@example.com', 'admin123', 'male']
    ];
    
    $inserted = 0;
    foreach ($users as $user) {
        $name = $user[0];
        $email = $user[1];
        $password = $user[2];
        $gender = $user[3];
        
        // Use INSERT IGNORE to avoid duplicates
        $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, gender) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $gender);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "✅ User '$name' inserted\n";
                $inserted++;
            } else {
                echo "⚠️  User '$name' already exists\n";
            }
        } else {
            echo "❌ Failed to insert user '$name': " . $stmt->error . "\n";
        }
        $stmt->close();
    }
    
    // Verify the setup
    echo "\n=== Verification ===\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "Total users in database: " . $row['count'] . "\n";
    
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ 'users' table exists\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "You can now test the login system with:\n";
    echo "Username: testuser, Password: testpass\n";
    echo "Username: admin, Password: admin123\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check if MySQL database is running\n";
    echo "2. Verify environment variables in Railway\n";
    echo "3. Check Railway logs for database connection issues\n";
}
?>
