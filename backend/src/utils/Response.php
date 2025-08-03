<?php
/**
 * Response Utility Class
 * 
 * Standardizes API response formatting across the application.
 * Provides consistent JSON response structure and HTTP status codes.
 * 
 * @package    Backend\Utils
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Utils;

use Backend\Exceptions\BaseException;

class Response
{
    /**
     * Send a successful JSON response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @param array $meta Additional metadata
     * @return void
     */
    public static function success(
        $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200,
        array $meta = []
    ): void {
        $response = [
            'status' => 'success',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        self::send($response, $statusCode);
    }

    /**
     * Send an error JSON response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param string $errorType Error type/category
     * @param array $details Additional error details
     * @return void
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        string $errorType = 'error',
        array $details = []
    ): void {
        $response = [
            'status' => 'error',
            'error_type' => $errorType,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        self::send($response, $statusCode);
    }

    /**
     * Send an exception-based error response
     * 
     * @param BaseException $exception The exception to format
     * @param bool $includeTrace Whether to include stack trace
     * @return void
     */
    public static function exception(BaseException $exception, bool $includeTrace = false): void
    {
        $exception->logError();
        $response = $exception->toArray($includeTrace);
        self::send($response, $exception->getHttpStatusCode());
    }

    /**
     * Send a validation error response
     * 
     * @param array $errors Array of validation errors
     * @param string $message Main error message
     * @return void
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): void {
        self::error($message, 422, 'validation_error', ['validation_errors' => $errors]);
    }

    /**
     * Send a paginated response
     * 
     * @param array $items The data items
     * @param int $total Total number of items
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param string $message Success message
     * @return void
     */
    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage,
        string $message = 'Data retrieved successfully'
    ): void {
        $totalPages = ceil($total / $perPage);
        $hasMore = $page < $totalPages;

        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $hasMore,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ]
        ];

        self::success($items, $message, 200, $meta);
    }

    /**
     * Send a created resource response
     * 
     * @param mixed $data The created resource data
     * @param string $message Success message
     * @param string $location Optional location header
     * @return void
     */
    public static function created(
        $data = null,
        string $message = 'Resource created successfully',
        string $location = null
    ): void {
        if ($location) {
            header("Location: $location");
        }

        self::success($data, $message, 201);
    }

    /**
     * Send a no content response
     * 
     * @param string $message Optional message
     * @return void
     */
    public static function noContent(string $message = 'Operation completed'): void
    {
        http_response_code(204);
        // No content for 204 responses
        exit();
    }

    /**
     * Send a not found response
     * 
     * @param string $message Error message
     * @param array $details Additional details
     * @return void
     */
    public static function notFound(
        string $message = 'Resource not found',
        array $details = []
    ): void {
        self::error($message, 404, 'not_found_error', $details);
    }

    /**
     * Send a method not allowed response
     * 
     * @param array $allowedMethods Array of allowed HTTP methods
     * @return void
     */
    public static function methodNotAllowed(array $allowedMethods = []): void
    {
        if (!empty($allowedMethods)) {
            header('Allow: ' . implode(', ', $allowedMethods));
        }

        self::error('Method not allowed', 405, 'method_not_allowed_error');
    }

    /**
     * Send raw JSON response
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function send(array $data, int $statusCode = 200): void
    {
        // Ensure CORS headers are set if not already set
        if (!headers_sent()) {
            if (!self::headerExists('Access-Control-Allow-Origin')) {
                header('Access-Control-Allow-Origin: http://localhost:3000');
            }
            if (!self::headerExists('Content-Type')) {
                header('Content-Type: application/json; charset=utf-8');
            }
        }

        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Check if a header has already been set
     * 
     * @param string $header Header name to check
     * @return bool
     */
    private static function headerExists(string $header): bool
    {
        $headers = headers_list();
        foreach ($headers as $existingHeader) {
            if (stripos($existingHeader, $header . ':') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send a health check response
     * 
     * @param array $checks Array of health check results
     * @param string $version Application version
     * @return void
     */
    public static function healthCheck(array $checks = [], string $version = '1.0.0'): void
    {
        $allHealthy = empty($checks) || !in_array(false, array_column($checks, 'healthy'), true);
        $statusCode = $allHealthy ? 200 : 503;

        $data = [
            'version' => $version,
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's',
        ];

        if (!empty($checks)) {
            $data['checks'] = $checks;
        }

        self::send([
            'status' => 'success',
            'message' => 'Health check completed',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
        ], $statusCode);
    }
}
