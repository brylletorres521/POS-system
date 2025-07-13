<?php
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'endpoint' => 'test.php',
    'message' => 'This is the test endpoint',
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 