<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'online',
    'service' => 'GoldLab Auth Server',
    'endpoint' => 'POST /login.php'
]);
?>
