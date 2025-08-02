<?php
/**
 * Router Class
 * 
 * A lightweight HTTP router for handling API requests with support for 
 * parameterized routes, CORS management, and method-based routing.
 * 
 * This router supports GET, POST, PUT, and DELETE HTTP methods and can
 * extract parameters from URL patterns using placeholder syntax like {param}.
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 * 
 * Example usage:
 * ```php
 * $router = new Router();
 * $router->get('/api/users/{id}', [$controller, 'getUser']);
 * $router->post('/api/users', [$controller, 'createUser']);
 * $router->run();
 * ```
 */
class Router {
    
    /**
     * Array of registered routes
     * 
     * Each route contains method, pattern, and callback information
     * 
     * @var array<int, array{method: string, pattern: string, callback: callable}>
     */
    private $routes = [];
    
    /**
     * Current HTTP request method
     * 
     * @var string The HTTP method (GET, POST, PUT, DELETE, OPTIONS, etc.)
     */
    private $method;
    
    /**
     * Current request path without query parameters
     * 
     * @var string The cleaned request path (e.g., "/api/users/123")
     */
    private $path;

    /**
     * Router constructor
     * 
     * Initializes the router by extracting the current HTTP method and path,
     * and sets up CORS headers for cross-origin requests.
     * 
     * @throws RuntimeException If required server variables are not available
     */
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->getCleanPath();
        $this->setupCORS();
    }

    /**
     * Extract and clean the request path from the current URI
     * 
     * Removes query parameters and trailing slashes to normalize the path
     * for consistent route matching.
     * 
     * @return string The cleaned request path
     * 
     * @example
     * - "/api/users/" becomes "/api/users"
     * - "/api/users?page=1" becomes "/api/users"
     * - "/" becomes ""
     */
    private function getCleanPath(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/');
    }

    /**
     * Configure CORS headers for cross-origin requests
     * 
     * Sets the necessary headers to allow requests from the React frontend
     * and handles preflight OPTIONS requests automatically.
     * 
     * @return void
     * 
     * @note This method will exit the script if handling an OPTIONS request
     */
    private function setupCORS(): void {
        header('Access-Control-Allow-Origin: http://localhost:3000');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');

        // Handle preflight OPTIONS requests
        if ($this->method === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Register a GET route
     * 
     * @param string   $pattern  The route pattern (e.g., "/api/users/{id}")
     * @param callable $callback The function or method to call when route matches
     * 
     * @return void
     * 
     * @example
     * ```php
     * $router->get('/api/users', [$controller, 'getAllUsers']);
     * $router->get('/api/users/{id}', [$controller, 'getUser']);
     * ```
     */
    public function get(string $pattern, callable $callback): void {
        $this->addRoute('GET', $pattern, $callback);
    }

    /**
     * Register a POST route
     * 
     * @param string   $pattern  The route pattern (e.g., "/api/users")
     * @param callable $callback The function or method to call when route matches
     * 
     * @return void
     * 
     * @example
     * ```php
     * $router->post('/api/users', [$controller, 'createUser']);
     * ```
     */
    public function post(string $pattern, callable $callback): void {
        $this->addRoute('POST', $pattern, $callback);
    }

    /**
     * Register a PUT route
     * 
     * @param string   $pattern  The route pattern (e.g., "/api/users/{id}")
     * @param callable $callback The function or method to call when route matches
     * 
     * @return void
     * 
     * @example
     * ```php
     * $router->put('/api/users/{id}', [$controller, 'updateUser']);
     * ```
     */
    public function put(string $pattern, callable $callback): void {
        $this->addRoute('PUT', $pattern, $callback);
    }

    /**
     * Register a DELETE route
     * 
     * @param string   $pattern  The route pattern (e.g., "/api/users/{id}")
     * @param callable $callback The function or method to call when route matches
     * 
     * @return void
     * 
     * @example
     * ```php
     * $router->delete('/api/users/{id}', [$controller, 'deleteUser']);
     * ```
     */
    public function delete(string $pattern, callable $callback): void {
        $this->addRoute('DELETE', $pattern, $callback);
    }

    /**
     * Add a route to the internal routes array
     * 
     * This is the internal method that stores route information for later matching.
     * All public route methods (get, post, put, delete) use this method.
     * 
     * @param string   $method   The HTTP method for this route
     * @param string   $pattern  The URL pattern with optional parameters
     * @param callable $callback The callback to execute when route matches
     * 
     * @return void
     */
    private function addRoute(string $method, string $pattern, callable $callback): void {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }

    /**
     * Execute the router and handle the current request
     * 
     * Iterates through all registered routes to find a match for the current
     * request method and path. If a match is found, executes the callback
     * with any extracted parameters. If no match is found, returns a 404 response.
     * 
     * @return void This method outputs the response and does not return a value
     * 
     * @throws Exception If the callback execution fails
     * 
     * @example
     * For a route pattern "/api/users/{id}" matching "/api/users/123",
     * the callback will be called with "123" as the first parameter.
     */
    public function run(): void {
        foreach ($this->routes as $route) {
            if ($route['method'] === $this->method) {
                $matches = [];
                $pattern = $this->convertPatternToRegex($route['pattern']);
                
                if (preg_match($pattern, $this->path, $matches)) {
                    // Remove the full match, keep only captured groups (parameters)
                    array_shift($matches);
                    
                    // Call the callback with any captured parameters
                    call_user_func_array($route['callback'], $matches);
                    return;
                }
            }
        }

        // No matching route found
        $this->notFound();
    }

    /**
     * Convert a route pattern to a regular expression
     * 
     * Transforms route patterns with placeholder syntax into regex patterns
     * for matching against request paths.
     * 
     * @param string $pattern The route pattern (e.g., "/api/users/{id}")
     * 
     * @return string The converted regex pattern
     * 
     * @example
     * - "/api/users/{id}" becomes "/^\/api\/users\/([^\/]+)$/"
     * - "/api/posts/{postId}/comments/{commentId}" becomes "/^\/api\/posts\/([^\/]+)\/comments\/([^\/]+)$/"
     */
    private function convertPatternToRegex(string $pattern): string {
        // Escape special regex characters except for our placeholders
        $pattern = preg_quote($pattern, '/');
        
        // Convert {param} placeholders to capturing groups
        // This matches any characters except forward slashes
        $pattern = preg_replace('/\\\\{([^}]+)\\\\}/', '([^/]+)', $pattern);
        
        // Ensure exact match from start to end
        return '/^' . $pattern . '$/';
    }

    /**
     * Handle 404 Not Found responses
     * 
     * Sends a JSON response indicating that the requested endpoint was not found.
     * Includes debugging information about the requested path and method.
     * 
     * @return void This method outputs the response and does not return a value
     */
    private function notFound(): void {
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint not found',
            'path' => $this->path,
            'method' => $this->method,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Handle 405 Method Not Allowed responses
     * 
     * Public method that can be called by controllers to indicate that
     * a route exists but doesn't support the current HTTP method.
     * 
     * @return void This method outputs the response and does not return a value
     */
    public function methodNotAllowed(): void {
        http_response_code(405);
        echo json_encode([
            'error' => 'Method not allowed',
            'method' => $this->method,
            'path' => $this->path,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Send a JSON response with the specified data and status code
     * 
     * Utility method for sending consistent JSON responses from route handlers.
     * 
     * @param mixed $data       The data to encode as JSON
     * @param int   $statusCode The HTTP status code (default: 200)
     * 
     * @return void This method outputs the response and does not return a value
     * 
     * @example
     * ```php
     * $router->json(['message' => 'Success'], 200);
     * $router->json(['error' => 'Not found'], 404);
     * ```
     */
    public function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Get the request body parsed as JSON
     * 
     * Utility method for reading and parsing JSON input from the request body.
     * Commonly used in POST and PUT requests.
     * 
     * @return array|null The parsed JSON data or null if parsing fails
     * 
     * @example
     * ```php
     * $input = $router->getJsonInput();
     * if ($input && isset($input['name'])) {
     *     // Process the input data
     * }
     * ```
     */
    public function getJsonInput(): ?array {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * Get the current request method
     * 
     * @return string The HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * Get the current request path
     * 
     * @return string The cleaned request path
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Get all registered routes
     * 
     * Useful for debugging and route inspection.
     * 
     * @return array<int, array{method: string, pattern: string, callback: callable}>
     */
    public function getRoutes(): array {
        return $this->routes;
    }
}