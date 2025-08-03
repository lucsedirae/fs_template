<?php
// Simple CORS test file - Place this in backend/src/cors-test.php

// Set CORS headers immediately
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Content-Type: application/json");

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log to error log
error_log("CORS test accessed - Method: " . $_SERVER['REQUEST_METHOD']);

// Return test data
echo json_encode([
    'status' => 'success',
    'message' => 'CORS test working!',
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers_sent' => headers_sent(),
    'timestamp' => date('Y-m-d H:i:s')
]);
