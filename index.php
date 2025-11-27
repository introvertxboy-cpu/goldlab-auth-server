<?php
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $raw_data = file_get_contents("php://input");
    $post_data = array();
    parse_str($raw_data, $post_data);
    
    if (isset($post_data['deviceId'])) {
        $deviceId = $post_data['deviceId'];
        error_log("Logout successful for device: " . $deviceId);
        
        // Your logout logic here
        
        echo "Logout Successfull";
    } else {
        http_response_code(400);
        echo "Error: deviceId required";
    }
} else {
    http_response_code(405);
    echo "Method not allowed";
}
?>
