<?php
header('Content-Type: text/plain');
echo "=== Quick Database Fix ===\n\n";

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    echo "✅ Connected to database\n\n";
    
    // Just clear and repopulate the users table
    echo "1. Clearing existing users...\n";
    $conn->query("DELETE FROM users");
    echo "✅ Users cleared\n\n";
    
    echo "2. Adding test users...\n";
    $users = [
        [1, 'testuser', 'test@example.com', 'testpass', 'male'],
        [2, 'admin', 'admin@example.com', 'admin123', 'male']
    ];
    
    foreach ($users as $user) {
        $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, gender) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user[0], $user[1], $user[2], $user[3], $user[4]);
        if ($stmt->execute()) {
            echo "✅ User '{$user[1]}' added\n";
        }
        $stmt->close();
    }
    
    echo "\n🎉 Database ready!\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
