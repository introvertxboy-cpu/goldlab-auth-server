<?php
header('Content-Type: text/plain');

$servername = getenv('MYSQLHOST');
$db_port = getenv('MYSQLPORT');
$db_name = getenv('MYSQLDATABASE');
$db_user = getenv('MYSQLUSER');
$db_pass = getenv('MYSQLPASSWORD');

try {
    $conn = new mysqli($servername, $db_user, $db_pass, $db_name, $db_port);
    
    echo "Current table structure:\n";
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
