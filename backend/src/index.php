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

// Remove trailing slash if present
$path = rtrim($path, '/');

// Simple routing
switch (true) {
    case $path === '' || $path === '/api':
        handleRoot($method);
        break;
    case $path === '/api/hello':
        handleHello($method);
        break;
    case $path === '/api/status':
        handleStatus($method);
        break;
    case $path === '/api/db-test':
        handleDatabaseTest($method);
        break;
    case $path === '/api/tables':
        handleTables($method);
        break;
    case preg_match('/^\/api\/tables\/(.+)$/', $path, $matches):
        handleSpecificTable($method, $matches[1]);
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
                'GET /api/db-test - Database connection test',
                'GET /api/tables - List all tables',
                'POST /api/tables - Create a new table',
                'DELETE /api/tables/{name} - Delete a table'
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

function handleTables($method) {
    $database = new Database();
    
    switch ($method) {
        case 'GET':
            // List all tables
            $result = $database->getTables();
            
            if ($result['status'] === 'success') {
                http_response_code(200);
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(500);
                echo json_encode($result, JSON_PRETTY_PRINT);
            }
            break;
            
        case 'POST':
            // Create a new table
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['tableName']) || !isset($input['columns'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields: tableName and columns'
                ]);
                return;
            }
            
            $result = $database->createTable($input['tableName'], $input['columns']);
            
            if ($result['status'] === 'success') {
                http_response_code(201);
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode($result, JSON_PRETTY_PRINT);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleSpecificTable($method, $tableName) {
    $database = new Database();
    
    switch ($method) {
        case 'DELETE':
            // Delete a table
            $result = $database->dropTable($tableName);
            
            if ($result['status'] === 'success') {
                http_response_code(200);
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode($result, JSON_PRETTY_PRINT);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}