<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Basic response
$response = [
    'status' => 'success',
    'message' => 'POS System API is working!',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'vercel' => true
    ],
    'note' => 'This is a test endpoint. Traditional PHP/MySQL applications like POS systems require traditional hosting with database support.'
];

// Return the JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?> 