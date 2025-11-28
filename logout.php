<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get the raw POST data
    $raw_data = file_get_contents("php://input");
    $post_data = array();
    parse_str($raw_data, $post_data);
    
    // Check if deviceId parameter exists
    if (isset($post_data['deviceId'])) {
        $deviceId = $post_data['deviceId'];
        
        // Log the logout attempt (optional)
        error_log("Logout request for device: " . $deviceId);
        
        // Here you would typically:
        // 1. Remove the device from active sessions in database
        // 2. Clear any session tokens
        // 3. Update user status
        
        // Example database operations (uncomment and modify as needed):
        /*
        $servername = "localhost";
        $username = "your_username";
        $password = "your_password";
        $dbname = "your_database";
        
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update user session status
            $stmt = $conn->prepare("UPDATE user_sessions SET is_active = 0, logout_time = NOW() WHERE device_id = :device_id AND is_active = 1");
            $stmt->bindParam(':device_id', $deviceId);
            $stmt->execute();
            
            // Or delete the session
            // $stmt = $conn->prepare("DELETE FROM user_sessions WHERE device_id = :device_id");
            // $stmt->bindParam(':device_id', $deviceId);
            // $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
        }
        */
        
        // Return success response
        http_response_code(200);
        echo "Logout Successfull";
        
    } else {
        // Missing deviceId parameter
        http_response_code(400);
        echo "Error: deviceId parameter missing";
    }
    
} else {
    // Invalid request method
    http_response_code(405);
    echo "Error: Only POST method allowed";
}
?>
