<?php
header('Content-Type: text/plain');
echo "=== GoldLab Database Setup ===\n\n";

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

echo "Database: $db_name\n";
echo "Host: $servername\n";
echo "User: $db_user\n\n";

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "✅ Database connected!\n\n";
    
    // First, check what tables exist
    echo "1. Checking existing tables...\n";
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
    echo "\n";
    
    // Drop foreign key constraints first
    echo "2. Removing foreign key constraints...\n";
    $conn->query("ALTER TABLE user_sessions DROP FOREIGN KEY user_sessions_ibfk_1");
    echo "✅ Foreign key dropped\n\n";
    
    // Drop user_sessions table
    echo "3. Dropping user_sessions table...\n";
    $conn->query("DROP TABLE IF EXISTS user_sessions");
    echo "✅ user_sessions table dropped\n\n";
    
    // Now drop users table
    echo "4. Dropping users table...\n";
    $conn->query("DROP TABLE IF EXISTS users");
    echo "✅ users table dropped\n\n";
    
    // Create new users table
    echo "5. Creating new users table...\n";
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
    
    if ($conn->query($sql)) {
        echo "✅ Table created!\n\n";
    } else {
        throw new Exception("Table creation failed: " . $conn->error);
    }
    
    // Insert users
    echo "6. Inserting test users...\n";
    $users = [
        ['testuser', 'test@example.com', 'testpass', 'male'],
        ['admin', 'admin@example.com', 'admin123', 'male']
    ];
    
    foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, gender) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user[0], $user[1], $user[2], $user[3]);
        if ($stmt->execute()) {
            echo "✅ User '$user[0]' added\n";
        }
        $stmt->close();
    }
    
    echo "\n7. Verifying setup...\n";
    $result = $conn->query("SELECT * FROM users");
    echo "Total users: " . $result->num_rows . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['name']} | {$row['email']} | {$row['password']}\n";
    }
    
    // Show table structure
    echo "\n8. Table structure:\n";
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} | {$row['Type']} | {$row['Null']}\n";
    }
    
    $conn->close();
    
    echo "\n🎉 SETUP COMPLETE!\n";
    echo "Test with: curl -X POST https://goldlab-auth-server-production.up.railway.app/login.php -d \"name=admin&password=admin123&deviceId=device1\"\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    // If foreign key drop fails, try alternative approach
    if (strpos($e->getMessage(), 'foreign key') !== false) {
        echo "\nTrying alternative approach...\n";
        
        try {
            $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
            
            // Just update the existing table structure
            echo "Updating existing table structure...\n";
            
            // Check current structure
            $result = $conn->query("DESCRIBE users");
            $has_name = false;
            while ($row = $result->fetch_assoc()) {
                if ($row['Field'] == 'name') $has_name = true;
            }
            
            if (!$has_name) {
                echo "Adding 'name' column...\n";
                $conn->query("ALTER TABLE users ADD COLUMN name VARCHAR(50) UNIQUE NOT NULL AFTER id");
                echo "✅ Name column added\n";
            }
            
            // Update test users
            echo "Updating test users...\n";
            $conn->query("UPDATE users SET name = 'testuser', email = 'test@example.com', password = 'testpass', gender = 'male' WHERE id = 1");
            $conn->query("UPDATE users SET name = 'admin', email = 'admin@example.com', password = 'admin123', gender = 'male' WHERE id = 2");
            
            echo "✅ Users updated!\n";
            
            $conn->close();
            
        } catch (Exception $e2) {
            echo "Alternative also failed: " . $e2->getMessage() . "\n";
        }
    }
}
?>
