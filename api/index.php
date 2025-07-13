<?php
header('Content-Type: application/json');

echo json_encode([
    'message' => 'POS System API is working!',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 