<?php
/**
 * Enhanced Router Class
 * 
 * Handles URL routing with improved error handling and middleware support.
 * 
 * @package    Backend\Core
 * @author     Your Team
 * @version    2.0.0
 * @since      PHP 7.4
 */

namespace Backend\Core;

use Backend\Utils\Response;
use Backend\Exceptions\BaseException;

class Router
{
    /**
     * Registered routes
     * 
     * @var array
     */
    private $routes = [];

    /**
     * Request method
     * 
     * @var string
     */
    private $method;

    /**
     * Request path
     * 
     * @var string
     */
    private $path;

    /**
     * Middleware stack
     * 
     * @var array
     */
    private $middleware = [];

    /**
     * Route groups
     * 
     * @var array
     */
    private $groups = [];

    /**
     * Current group prefix
     * 
     * @var string
     */
    private $currentGroupPrefix = '';

    /**
     * Router constructor
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->getCleanPath();
        
        $this->logRequest();
    }

    /**
     * Register GET route
     * 
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function get(string $pattern, $callback, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $callback, $middleware);
    }

    /**
     * Register POST route
     * 
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function post(string $pattern, $callback, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $callback, $middleware);
    }

    /**
     * Register PUT route
     * 
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function put(string $pattern, $callback, array $middleware = []): void
    {
        $this->addRoute('PUT', $pattern, $callback, $middleware);
    }

    /**
     * Register DELETE route
     * 
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function delete(string $pattern, $callback, array $middleware = []): void
    {
        $this->addRoute('DELETE', $pattern, $callback, $middleware);
    }

    /**
     * Register PATCH route
     * 
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function patch(string $pattern, $callback, array $middleware = []): void
    {
        $this->addRoute('PATCH', $pattern, $callback, $middleware);
    }

    /**
     * Register route for any method
     * 
     * @param array $methods HTTP methods
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function any(array $methods, string $pattern, $callback, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $pattern, $callback, $middleware);
        }
    }

    /**
     * Create a route group with common prefix and middleware
     * 
     * @param string $prefix URL prefix
     * @param callable $callback Group definition callback
     * @param array $middleware Group middleware
     * @return void
     */
    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix .= $prefix;
        
        // Store group middleware
        $this->groups[] = [
            'prefix' => $this->currentGroupPrefix,
            'middleware' => $middleware
        ];

        // Execute group definition
        $callback($this);

        // Restore previous prefix
        $this->currentGroupPrefix = $previousPrefix;
        array_pop($this->groups);
    }

    /**
     * Add global middleware
     * 
     * @param callable $middleware Middleware function
     * @return void
     */
    public function middleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Run the router and dispatch the request
     * 
     * @return void
     */
    public function run(): void
    {
        try {
            // Execute global middleware
            $this->executeMiddleware($this->middleware);

            // Find and execute matching route
            $route = $this->findMatchingRoute();
            
            if ($route) {
                $this->executeRoute($route);
            } else {
                $this->handleNotFound();
            }
            
        } catch (BaseException $e) {
            Response::exception($e, $_ENV['APP_DEBUG'] ?? false);
        } catch (\Throwable $e) {
            $this->handleUnexpectedException($e);
        }
    }

    /**
     * Add a route to the collection
     * 
     * @param string $method HTTP method
     * @param string $pattern URL pattern
     * @param callable|array $callback Route callback
     * @param array $middleware Route middleware
     * @return void
     */
    private function addRoute(string $method, string $pattern, $callback, array $middleware = []): void
    {
        // Apply group prefix
        $fullPattern = $this->currentGroupPrefix . $pattern;
        
        // Collect group middleware
        $groupMiddleware = [];
        foreach ($this->groups as $group) {
            if (strpos($fullPattern, $group['prefix']) === 0) {
                $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
            }
        }

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPattern,
            'callback' => $callback,
            'middleware' => array_merge($groupMiddleware, $middleware),
            'regex' => $this->convertPatternToRegex($fullPattern)
        ];
    }

    /**
     * Find matching route for current request
     * 
     * @return array|null Matching route or null
     */
    private function findMatchingRoute(): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $this->method) {
                $matches = [];
                if (preg_match($route['regex'], $this->path, $matches)) {
                    array_shift($matches); // Remove full match
                    $route['params'] = $matches;
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Execute a matched route
     * 
     * @param array $route Route definition
     * @return void
     */
    private function executeRoute(array $route): void
    {
        // Execute route middleware
        $this->executeMiddleware($route['middleware']);

        // Execute route callback
        $callback = $route['callback'];
        $params = $route['params'] ?? [];

        if (is_array($callback)) {
            // Controller method callback [ControllerInstance, 'method']
            [$controller, $method] = $callback;
            call_user_func_array([$controller, $method], $params);
        } else {
            // Function callback
            call_user_func_array($callback, $params);
        }
    }

    /**
     * Execute middleware stack
     * 
     * @param array $middleware Middleware functions
     * @return void
     */
    private function executeMiddleware(array $middleware): void
    {
        foreach ($middleware as $middlewareFunc) {
            if (is_callable($middlewareFunc)) {
                $result = call_user_func($middlewareFunc);
                
                // If middleware returns false, stop execution
                if ($result === false) {
                    break;
                }
            }
        }
    }

    /**
     * Handle 404 not found
     * 
     * @return void
     */
    private function handleNotFound(): void
    {
        $availableMethods = $this->getAvailableMethodsForPath($this->path);
        
        if (!empty($availableMethods)) {
            // Path exists but method not allowed
            Response::methodNotAllowed($availableMethods);
        } else {
            // Path not found
            Response::notFound('Endpoint not found', [
                'path' => $this->path,
                'method' => $this->method,
                'available_endpoints' => $this->getAvailableEndpoints()
            ]);
        }
    }

    /**
     * Handle unexpected exceptions
     * 
     * @param \Throwable $e Exception
     * @return void
     */
    private function handleUnexpectedException(\Throwable $e): void
    {
        error_log("Unexpected exception in router: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        Response::error(
            'Internal server error',
            500,
            'server_error',
            $_ENV['APP_DEBUG'] ? [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : []
        );
    }

    /**
     * Get clean request path
     * 
     * @return string Cleaned path
     */
    private function getCleanPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }

    /**
     * Convert URL pattern to regex
     * 
     * @param string $pattern URL pattern
     * @return string Regex pattern
     */
    private function convertPatternToRegex(string $pattern): string
    {
        // Escape special regex characters except {}
        $pattern = preg_quote($pattern, '/');
        
        // Convert {param} to regex groups
        $pattern = preg_replace('/\\\{([^}]+)\\\}/', '([^\/]+)', $pattern);
        
        return '/^' . $pattern . '$/';
    }

    /**
     * Get available HTTP methods for a path
     * 
     * @param string $path Request path
     * @return array Available methods
     */
    private function getAvailableMethodsForPath(string $path): array
    {
        $methods = [];
        
        foreach ($this->routes as $route) {
            $matches = [];
            if (preg_match($route['regex'], $path, $matches)) {
                $methods[] = $route['method'];
            }
        }

        return array_unique($methods);
    }

    /**
     * Get list of available endpoints
     * 
     * @return array Available endpoints
     */
    private function getAvailableEndpoints(): array
    {
        $endpoints = [];
        
        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];
            $method = $route['method'];
            
            if (!isset($endpoints[$pattern])) {
                $endpoints[$pattern] = [];
            }
            
            $endpoints[$pattern][] = $method;
        }

        return $endpoints;
    }

    /**
     * Log request details
     * 
     * @return void
     */
    private function logRequest(): void
    {
        if ($_ENV['LOG_REQUESTS'] ?? false) {
            error_log(sprintf(
                "Router: %s %s from %s",
                $this->method,
                $this->path,
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ));
        }
    }

    /**
     * Get current request method
     * 
     * @return string HTTP method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get current request path
     * 
     * @return string Request path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get all registered routes
     * 
     * @return array Routes array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
