<?php
/**
 * Enhanced Base Controller Class
 * 
 * Provides common functionality for all controllers including request handling,
 * validation, response formatting, and error management.
 * 
 * @package    Backend\Controllers
 * @author     Your Team
 * @version    2.0.0
 * @since      PHP 7.4
 */

namespace Backend\Controllers;

use Backend\Utils\Response;
use Backend\Exceptions\BaseException;
use Backend\Exceptions\ValidationException;
use Backend\Exceptions\BadRequestException;

abstract class BaseController
{
    /**
     * Request method
     * 
     * @var string
     */
    protected $method;

    /**
     * Request headers
     * 
     * @var array
     */
    protected $headers;

    /**
     * Query parameters
     * 
     * @var array
     */
    protected $query;

    /**
     * Request body data
     * 
     * @var array|null
     */
    protected $requestData;

    /**
     * BaseController constructor
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->headers = $this->getAllHeaders();
        $this->query = $_GET ?? [];
        $this->requestData = $this->getJsonInput();
    }

    /**
     * Send successful response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @param array $meta Additional metadata
     * @return void
     */
    protected function success(
        $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200,
        array $meta = []
    ): void {
        Response::success($data, $message, $statusCode, $meta);
    }

    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param string $errorType Error type
     * @param array $details Additional details
     * @return void
     */
    protected function error(
        string $message,
        int $statusCode = 400,
        string $errorType = 'error',
        array $details = []
    ): void {
        Response::error($message, $statusCode, $errorType, $details);
    }

    /**
     * Send exception-based error response
     * 
     * @param BaseException $exception The exception to handle
     * @param bool $includeTrace Whether to include stack trace
     * @return void
     */
    protected function handleException(BaseException $exception, bool $includeTrace = false): void
    {
        Response::exception($exception, $includeTrace);
    }

    /**
     * Send created response
     * 
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @param string $location Optional location header
     * @return void
     */
    protected function created(
        $data = null,
        string $message = 'Resource created successfully',
        string $location = null
    ): void {
        Response::created($data, $message, $location);
    }

    /**
     * Send paginated response
     * 
     * @param array $items Data items
     * @param int $total Total count
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param string $message Success message
     * @return void
     */
    protected function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage,
        string $message = 'Data retrieved successfully'
    ): void {
        Response::paginated($items, $total, $page, $perPage, $message);
    }

    /**
     * Send not found response
     * 
     * @param string $message Error message
     * @param array $details Additional details
     * @return void
     */
    protected function notFound(
        string $message = 'Resource not found',
        array $details = []
    ): void {
        Response::notFound($message, $details);
    }

    /**
     * Send method not allowed response
     * 
     * @param array $allowedMethods Allowed HTTP methods
     * @return void
     */
    protected function methodNotAllowed(array $allowedMethods = []): void
    {
        Response::methodNotAllowed($allowedMethods);
    }

    /**
     * Get request body as JSON
     * 
     * @return array|null Decoded JSON data or null if invalid
     */
    protected function getJsonInput(): ?array
    {
        $input = file_get_contents('php://input');

        if (empty($input)) {
            return null;
        }

        $decoded = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException('Invalid JSON in request body');
        }

        return $decoded;
    }

    /**
     * Validate required fields in request data
     * 
     * @param array|null $data Data to validate (defaults to request data)
     * @param array $requiredFields Required field names
     * @param string $context Context for error messages
     * @throws ValidationException
     * @return void
     */
    protected function validateRequired(
        ?array $data = null,
        array $requiredFields = [],
        string $context = 'request'
    ): void {
        $data = $data ?? $this->requestData ?? [];
        $missing = [];

        foreach ($requiredFields as $field) {
            if (
                !isset($data[$field]) ||
                (is_string($data[$field]) && trim($data[$field]) === '') ||
                (is_null($data[$field]))
            ) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new ValidationException(
                "Missing required fields in {$context}",
                ['missing_fields' => $missing]
            );
        }
    }

    /**
     * Validate method allowed for this endpoint
     * 
     * @param array $allowedMethods Array of allowed HTTP methods
     * @throws BadRequestException
     * @return void
     */
    protected function validateMethod(array $allowedMethods): void
    {
        if (!in_array($this->method, $allowedMethods, true)) {
            $this->methodNotAllowed($allowedMethods);
        }
    }

    /**
     * Get query parameter with default value
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed Parameter value or default
     */
    protected function getQueryParam(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get query parameter as integer
     * 
     * @param string $key Parameter key
     * @param int $default Default value
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int Parameter value as integer
     */
    protected function getQueryInt(string $key, int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $value = (int) ($this->query[$key] ?? $default);
        return max($min, min($max, $value));
    }

    /**
     * Get pagination parameters from query
     * 
     * @param int $defaultLimit Default items per page
     * @param int $maxLimit Maximum items per page
     * @return array ['limit' => int, 'offset' => int, 'page' => int]
     */
    protected function getPaginationParams(int $defaultLimit = 50, int $maxLimit = 1000): array
    {
        $limit = $this->getQueryInt('limit', $defaultLimit, 1, $maxLimit);
        $page = max(1, $this->getQueryInt('page', 1, 1));
        $offset = ($page - 1) * $limit;

        return [
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page
        ];
    }

    /**
     * Get request header value
     * 
     * @param string $name Header name
     * @param string|null $default Default value
     * @return string|null Header value
     */
    protected function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    /**
     * Check if request is JSON
     * 
     * @return bool True if content type is JSON
     */
    protected function isJsonRequest(): bool
    {
        $contentType = $this->getHeader('content-type', '');
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Get all request headers
     * 
     * @return array Associative array of headers
     */
    private function getAllHeaders(): array
    {
        $headers = [];

        // Use getallheaders() if available (Apache/Nginx)
        if (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            if ($allHeaders) {
                foreach ($allHeaders as $name => $value) {
                    $headers[strtolower($name)] = $value;
                }
                return $headers;
            }
        }

        // Fallback for other servers
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $header = strtolower(str_replace(['HTTP_', '_'], ['', '-'], $name));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Log activity for debugging/auditing
     * 
     * @param string $action Action description
     * @param array $context Additional context
     * @param string $level Log level
     * @return void
     */
    protected function logActivity(string $action, array $context = [], string $level = 'info'): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $this->method,
            'action' => $action,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        ];

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        error_log("[{$level}] " . json_encode($logData));
    }

    /**
     * Execute a controller action with automatic exception handling
     * 
     * @param callable $action The action to execute
     * @return void
     */
    protected function executeAction(callable $action): void
    {
        try {
            $action();
        } catch (BaseException $e) {
            $this->handleException($e, $_ENV['APP_DEBUG'] ?? false);
        } catch (\Throwable $e) {
            // Convert unexpected exceptions to our format
            error_log("Unexpected exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            $this->error(
                'An unexpected error occurred',
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
    }
}
