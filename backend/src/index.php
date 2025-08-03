<?php
/**
 * Application Entry Point (Refactored)
 * 
 * Bootstrap file that initializes the application with the new architecture.
 * Handles CORS, routing, error handling, and dependency injection.
 * 
 * @package    Backend
 * @author     Your Team
 * @version    2.0.0
 * @since      PHP 7.4
 */

// Set CORS headers immediately - before anything else
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control");
header("Access-Control-Allow-Credentials: false");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight OPTIONS requests immediately
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configure error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Load environment variables (if using a .env file)
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();

// Define application constants
define('APP_ROOT', __DIR__);
define('APP_VERSION', '2.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', (bool)($_ENV['APP_DEBUG'] ?? false));

/**
 * Simple autoloader for our classes
 * In production, consider using Composer's autoloader instead
 */
spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $className = str_replace('Backend\\', '', $className);
    $className = str_replace('\\', '/', $className);
    $file = __DIR__ . '/' . strtolower($className) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    // Fallback to classes directory (for backward compatibility)
    $classFile = __DIR__ . '/classes/' . basename($className) . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    
    return false;
});

// Include required files manually (temporary during migration)
require_once __DIR__ . '/exceptions/BaseException.php';
require_once __DIR__ . '/exceptions/ValidationException.php';
require_once __DIR__ . '/exceptions/DatabaseException.php';
require_once __DIR__ . '/exceptions/NotFoundException.php';
require_once __DIR__ . '/exceptions/ConflictException.php';
require_once __DIR__ . '/exceptions/BadRequestException.php';
require_once __DIR__ . '/exceptions/AuthorizationException.php';

require_once __DIR__ . '/utils/Response.php';

require_once __DIR__ . '/controllers/BaseController.php';
require_once __DIR__ . '/controllers/ApiController.php';
require_once __DIR__ . '/controllers/TableController.php';

require_once __DIR__ . '/services/BaseService.php';
require_once __DIR__ . '/services/TableService.php';

require_once __DIR__ . '/core/Router.php';

// Include existing database classes
require_once __DIR__ . '/classes/DatabaseConnection.php';
require_once __DIR__ . '/classes/DatabaseValidator.php';
require_once __DIR__ . '/classes/QueryBuilder.php';
require_once __DIR__ . '/Database.php';

// Use namespaced classes
use Backend\Core\Router;
use Backend\Controllers\ApiController;
use Backend\Controllers\TableController;
use Backend\Utils\Response;
use Backend\Exceptions\BaseException;

/**
 * Global exception handler
 */
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage() . "\n" . $exception->getTraceAsString());
    
    // Ensure CORS headers are set even for errors
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: http://localhost:3000");
        header("Content-Type: application/json; charset=utf-8");
    }
    
    if ($exception instanceof BaseException) {
        Response::exception($exception, APP_DEBUG);
    } else {
        Response::error(
            'Internal server error',
            500,
            'server_error',
            APP_DEBUG ? [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ] : []
        );
    }
});

/**
 * Initialize application
 */
try {
    // Create router instance
    $router = new Router();
    
    // Add global middleware
    $router->middleware(function() {
        // CORS middleware (already handled in headers, but can add logic here)
        return true;
    });
    
    // Create controller instances
    $apiController = new ApiController();
    $tableController = new TableController();
    
    // Define API routes
    $router->group('/api', function($router) use ($apiController, $tableController) {
        
        // Basic API endpoints
        $router->get('', [$apiController, 'root']);
        $router->get('/hello', [$apiController, 'hello']);
        $router->get('/status', [$apiController, 'status']);
        $router->get('/health', [$apiController, 'health']);
        $router->get('/db-test', [$apiController, 'databaseTest']);
        
        // Table management routes
        $router->group('/tables', function($router) use ($tableController) {
            // Table collection endpoints
            $router->get('', [$tableController, 'index']);
            $router->post('', [$tableController, 'create']);
            $router->get('/health', [$tableController, 'health']);
            
            // Individual table endpoints
            $router->delete('/{tableName}', [$tableController, 'delete']);
            $router->any(['HEAD', 'GET'], '/{tableName}/exists', [$tableController, 'exists']);
            
            // Table data endpoints
            $router->get('/{tableName}/data', [$tableController, 'getData']);
            $router->get('/{tableName}/schema', [$tableController, 'getSchema']);
            $router->get('/{tableName}/stats', [$tableController, 'getStats']);
            $router->post('/{tableName}/truncate', [$tableController, 'truncate']);
            
            // Column management endpoints
            $router->post('/{tableName}/columns', [$tableController, 'addColumn']);
            $router->delete('/{tableName}/columns/{columnName}', [$tableController, 'removeColumn']);
        });
        
    });
    
    // Root endpoint (outside API group)
    $router->get('', [$apiController, 'root']);
    $router->get('/', [$apiController, 'root']);
    
    // Run the router
    $router->run();
    
} catch (BaseException $e) {
    Response::exception($e, APP_DEBUG);
} catch (\Throwable $e) {
    error_log("Critical error in index.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Ensure CORS headers are set
    if (!headers_sent()) {
        header("Access-Control-Allow-Origin: http://localhost:3000");
        header("Content-Type: application/json; charset=utf-8");
    }
    
    Response::error(
        'Server initialization failed',
        500,
        'initialization_error',
        APP_DEBUG ? [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : []
    );
}
