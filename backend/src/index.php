<?php
// backend/src/index.php

// Include the Database class
require_once 'Database.php';

// Set CORS headers to allow requests from React app
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Simple routing
switch ($path) {
    case '/':
    case '/api':
        handleRoot($method);
        break;
    case '/api/hello':
        handleHello($method);
        break;
    case '/api/status':
        handleStatus($method);
        break;
    case '/api/db-test':
        handleDatabaseTest($method);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'path' => $path]);
        break;
}

function handleRoot($method) {
    if ($method === 'GET') {
        $response = [
            'message' => 'PHP Backend API',
            'version' => '1.0.0',
            'available_endpoints' => [
                'GET /api/hello - Hello world endpoint',
                'GET /api/status - Service status',
                'GET /api/db-test - Database connection test'
            ]
        ];
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleHello($method) {
    if ($method === 'GET') {
        $response = [
            'message' => 'Hello from PHP Backend!',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => 'PHP ' . phpversion(),
            'container' => gethostname()
        ];
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleStatus($method) {
    if ($method === 'GET') {
        $response = [
            'status' => 'running',
            'version' => '1.0.0',
            'environment' => 'docker',
            'php_version' => phpversion(),
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's'
        ];
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleDatabaseTest($method) {
    if ($method === 'GET') {
        $database = new Database();
        $result = $database->testConnection();
        
        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(500);
        }
        
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}
