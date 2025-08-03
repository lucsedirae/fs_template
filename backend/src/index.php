<?php
// backend/src/index.php

// SET CORS HEADERS IMMEDIATELY - BEFORE ANYTHING ELSE
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control");
header("Access-Control-Allow-Credentials: false");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight OPTIONS requests immediately
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    error_log("Handling OPTIONS preflight in index.php");
    http_response_code(200);
    exit();
}

// Log the request for debugging
error_log("Request received: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . " " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include required classes
require_once __DIR__ . '/classes/Router.php';
require_once __DIR__ . '/classes/BaseController.php';
require_once __DIR__ . '/classes/ApiController.php';

// Set up error handler for uncaught exceptions
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    
    // Ensure CORS headers are set even for errors
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: http://localhost:3000");
        header("Content-Type: application/json");
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
});

try {
    // Create router instance
    $router = new Router();

    // Create controller instance
    $controller = new ApiController();

    // Define API routes
    $router->get('', [$controller, 'handleRoot']);
    $router->get('/api', [$controller, 'handleRoot']);
    $router->get('/api/hello', [$controller, 'handleHello']);
    $router->get('/api/status', [$controller, 'handleStatus']);
    $router->get('/api/db-test', [$controller, 'handleDatabaseTest']);

    // Table management routes
    $router->get('/api/tables', [$controller, 'handleGetTables']);
    $router->post('/api/tables', [$controller, 'handleCreateTable']);
    $router->delete('/api/tables/{tableName}', [$controller, 'handleDeleteTable']);

    // Table data routes
    $router->get('/api/tables/{tableName}/data', [$controller, 'handleGetTableData']);

    // Table schema routes
    $router->get('/api/tables/{tableName}/schema', [$controller, 'handleGetTableSchema']);
    $router->post('/api/tables/{tableName}/columns', [$controller, 'handleAddColumn']);
    $router->delete('/api/tables/{tableName}/columns/{columnName}', [$controller, 'handleDropColumn']);

    // Run the router
    $router->run();

} catch (Exception $e) {
    error_log("Critical error in index.php: " . $e->getMessage());
    
    // Ensure CORS headers are set
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: http://localhost:3000");
        header("Content-Type: application/json");
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server initialization failed',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}