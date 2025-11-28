<?php
header('Content-Type: text/plain');
echo "=== FINAL DATABASE FIX ===\n\n";

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    echo "✅ Database connected!\n\n";
    
    // 1. First, let's see the current table structure
    echo "1. Current table structure:\n";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} | {$row['Type']} | {$row['Null']}\n";
        }
    } else {
        echo "❌ Table 'users' doesn't exist\n";
    }
    echo "\n";
    
    // 2. Check if we have the right columns
    echo "2. Fixing table structure...\n";
    
    // Add missing columns one by one
    $columns_to_add = [
        "name" => "ALTER TABLE users ADD COLUMN name VARCHAR(50) UNIQUE NOT NULL AFTER id",
        "email" => "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NOT NULL AFTER name", 
        "password" => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email",
        "gender" => "ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other') DEFAULT 'male' AFTER password",
        "deviceId" => "ALTER TABLE users ADD COLUMN deviceId VARCHAR(255) DEFAULT NULL AFTER gender",
        "status" => "ALTER TABLE users ADD COLUMN status ENUM('0', '1') DEFAULT '0' AFTER deviceId"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        // Check if column exists
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($check->num_rows === 0) {
            echo "Adding column '$column'... ";
            if ($conn->query($sql)) {
                echo "✅\n";
            } else {
                echo "❌ " . $conn->error . "\n";
            }
        } else {
            echo "Column '$column' already exists ✅\n";
        }
    }
    
    echo "\n3. Inserting test users...\n";
    
    // Clear existing data
    $conn->query("DELETE FROM users");
    
    // Insert test users
    $users = [
        ['testuser', 'test@example.com', 'testpass', 'male'],
        ['admin', 'admin@example.com', 'admin123', 'male']
    ];
    
    foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, gender) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user[0], $user[1], $user[2], $user[3]);
        if ($stmt->execute()) {
            echo "✅ User '{$user[0]}' added\n";
        } else {
            echo "❌ Failed to add '{$user[0]}': " . $stmt->error . "\n";
        }
        $stmt->close();
    }
    
    echo "\n4. Final verification...\n";
    $result = $conn->query("SELECT id, name, email, password, gender, deviceId, status FROM users");
    echo "Total users: " . $result->num_rows . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['id']} | {$row['name']} | {$row['email']} | {$row['password']} | Device: " . ($row['deviceId'] ?: 'NULL') . " | Status: {$row['status']}\n";
    }
    
    $conn->close();
    
    echo "\n🎉 DATABASE FIXED!\n";
    echo "You can now test the login system.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
