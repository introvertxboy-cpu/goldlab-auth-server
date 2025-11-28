<?php
header("Content-Type: text/plain");

// DATABASE CONNECTION
$host = "containers-us-west-22.railway.app";
$user = "root";
$pass = "tTUXvohaqvsmNFrgjrulPrFXPOHQhwPC";
$dbname = "railway";
$port = 6370;

try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "=== FIXED DATABASE SETUP ===\n\n";
    echo "✅ Database connected!\n\n";
} catch (PDOException $e) {
    die("❌ DB Connection failed: " . $e->getMessage());
}

// 1. GET CURRENT TABLE STRUCTURE
echo "1. Checking table structure...\n";
try {
    $stmt = $db->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} | {$row['Type']} | {$row['Null']}\n";
    }
    echo "\n";
} catch (PDOException $e) {
    die("❌ ERROR: " . $e->getMessage());
}

// 2. FIX TABLE COLUMNS
echo "2. Fixing table columns...\n";

$columns = [
    "name"     => "ALTER TABLE users ADD COLUMN name VARCHAR(50) NOT NULL",
    "username" => "ALTER TABLE users ADD COLUMN username VARCHAR(50) NOT NULL",
    "password" => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL",
    "gender"   => "ALTER TABLE users ADD COLUMN gender ENUM('male','female','other') DEFAULT NULL",
    "deviceId" => "ALTER TABLE users ADD COLUMN deviceId VARCHAR(255) DEFAULT NULL",
    "status"   => "ALTER TABLE users ADD COLUMN status ENUM('0','1') DEFAULT '1'",
    "email"    => "ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL",
];

foreach ($columns as $column => $query) {
    try {
        $db->exec($query);
        echo "Added column '$column'... ✅\n";
    } catch (PDOException $e) {
        echo "Column '$column' already exists ✅\n";
    }
}

echo "\n";

// 3. INSERT TEST USERS (FULL FIX)
echo "3. Inserting test users...\n";

try {
    $db->exec("
        INSERT INTO users (name, username, password, gender, deviceId, status, email)
        VALUES 
        ('Admin User', 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'male', 'testDevice1', '1', 'admin@example.com'),
        ('Test User', 'user1', '" . password_hash('password1', PASSWORD_DEFAULT) . "', 'male', 'testDevice2', '1', 'user1@example.com')
    ");
    echo "Test users inserted! ✅\n";
} catch (PDOException $e) {
    echo "❌ ERROR inserting users: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
?>
