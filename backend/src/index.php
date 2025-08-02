<?php
// backend/src/index.php

// Include required classes
require_once __DIR__ . '/classes/Router.php';
require_once __DIR__ . '/classes/ApiController.php';

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

// Run the router
$router->run();
