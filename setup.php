<?php
header('Content-Type: text/plain');
echo "=== FIXED DATABASE SETUP ===\n\n";

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    echo "✅ Database connected!\n\n";
    
    echo "1. Checking table structure...\n";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} | {$row['Type']} | {$row['Null']}\n";
        }
    }
    echo "\n";

    echo "2. Fixing table columns...\n";

    // REQUIRED columns
    $columns = [
        "username" => "ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE NOT NULL AFTER id",
        "email" => "ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER username",
        "password" => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email",
        "gender" => "ALTER TABLE users ADD COLUMN gender ENUM('male','female','other') DEFAULT 'male' AFTER password",
        "deviceId" => "ALTER TABLE users ADD COLUMN deviceId VARCHAR(255) DEFAULT NULL AFTER gender",
        "status" => "ALTER TABLE users ADD COLUMN status ENUM('0','1') DEFAULT '0' AFTER deviceId"
    ];

    foreach ($columns as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
        echo ($check->num_rows === 0)
            ? ( $conn->query($sql) ? "Added '$column' ✅\n" : "❌ Failed to add '$column': ".$conn->error."\n" )
            : "Column '$column' already exists ✅\n";
    }

    echo "\n3. Inserting test users...\n";

    $conn->query("DELETE FROM users");

    $users = [
        ['testuser', 'test@example.com', 'testpass', 'male'],
        ['admin', 'admin@example.com', 'admin123', 'male']
    ];

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, gender) VALUES (?, ?, ?, ?)");

    foreach ($users as $u) {
        $stmt->bind_param("ssss", $u[0], $u[1], $u[2], $u[3]);
        echo ($stmt->execute())
            ? "✅ Added '{$u[0]}'\n"
            : "❌ Failed to add '{$u[0]}': ".$stmt->error."\n";
    }

    echo "\n4. Final check...\n";
    $result = $conn->query("SELECT id, username, email, password, gender, deviceId, status FROM users");
    echo "Total users: ".$result->num_rows."\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['id']} | {$row['username']} | {$row['email']} | {$row['password']} | Device: ".($row['deviceId'] ?: 'NULL')." | Status: {$row['status']}\n";
    }

    echo "\n🎉 DATABASE SETUP COMPLETE!\n";

} catch (Exception $e) {
    echo "❌ ERROR: ".$e->getMessage()."\n";
}
?>
