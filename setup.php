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
    
    // Drop table if exists
    echo "1. Dropping old table...\n";
    $conn->query("DROP TABLE IF EXISTS users");
    echo "✅ Table dropped\n\n";
    
    // Create new table
    echo "2. Creating new table...\n";
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
    echo "3. Inserting test users...\n";
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
    
    echo "\n4. Verifying setup...\n";
    $result = $conn->query("SELECT * FROM users");
    echo "Total users: " . $result->num_rows . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['name']} | {$row['email']} | {$row['password']}\n";
    }
    
    $conn->close();
    
    echo "\n🎉 SETUP COMPLETE!\n";
    echo "Test with: curl -X POST https://goldlab-auth-server-production.up.railway.app/login.php -d \"name=admin&password=admin123&deviceId=device1\"\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>