<?php
/**
 * Router Class - Simplified (CORS handled in index.php)
 */
class Router {
    
    private $routes = [];
    private $method;
    private $path;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->getCleanPath();
        
        error_log("Router initialized - Method: {$this->method}, Path: {$this->path}");
    }

    private function getCleanPath(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/');
    }

    public function get(string $pattern, callable $callback): void {
        $this->addRoute('GET', $pattern, $callback);
    }

    public function post(string $pattern, callable $callback): void {
        $this->addRoute('POST', $pattern, $callback);
    }

    public function put(string $pattern, callable $callback): void {
        $this->addRoute('PUT', $pattern, $callback);
    }

    public function delete(string $pattern, callable $callback): void {
        $this->addRoute('DELETE', $pattern, $callback);
    }

    private function addRoute(string $method, string $pattern, callable $callback): void {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }

    public function run(): void {
        error_log("Processing request: {$this->method} {$this->path}");
        
        try {
            foreach ($this->routes as $route) {
                if ($route['method'] === $this->method) {
                    $matches = [];
                    $pattern = $this->convertPatternToRegex($route['pattern']);
                    
                    if (preg_match($pattern, $this->path, $matches)) {
                        error_log("Route matched: {$route['pattern']}");
                        array_shift($matches);
                        call_user_func_array($route['callback'], $matches);
                        return;
                    }
                }
            }

            error_log("No route matched for: {$this->method} {$this->path}");
            $this->notFound();
            
        } catch (Exception $e) {
            error_log("Router exception: " . $e->getMessage());
            $this->handleException($e);
        }
    }

    private function convertPatternToRegex(string $pattern): string {
        $pattern = preg_quote($pattern, '/');
        $pattern = preg_replace('/\\\{[^}]+\\\}/', '([^\/]+)', $pattern);
        return '/^' . $pattern . '$/';
    }

    private function notFound(): void {
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint not found',
            'path' => $this->path,
            'method' => $this->method,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }

    private function handleException(Exception $e): void {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }

    public function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    public function getJsonInput(): ?array {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getPath(): string {
        return $this->path;
    }
}
