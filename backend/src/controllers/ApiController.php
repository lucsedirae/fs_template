<?php
/**
 * API Controller Class (Refactored)
 * 
 * Handles basic API endpoints including status, health checks, and database testing.
 * Table-related operations have been moved to TableController.
 * 
 * @package    Backend\Controllers
 * @author     Your Team
 * @version    2.0.0
 * @since      PHP 7.4
 */

namespace Backend\Controllers;

use Backend\Database\Connection;
use Backend\Utils\Response;

class ApiController extends BaseController
{
    /**
     * Database connection instance
     * 
     * @var Connection
     */
    private $db;

    /**
     * ApiController constructor
     * 
     * @param Connection|null $db Database connection
     */
    public function __construct(?Connection $db = null)
    {
        parent::__construct();
        $this->db = $db ?? Connection::getInstance();
    }

    /**
     * Handle the root API endpoint
     * 
     * Returns basic API information and available endpoints discovery.
     * 
     * @api GET /
     * @api GET /api
     * 
     * @return void
     */
    public function root(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            $data = [
                'name' => 'Backend API',
                'version' => '2.0.0',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
                'timestamp' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'status' => [
                        'method' => 'GET',
                        'path' => '/api/status',
                        'description' => 'Get API status and system information'
                    ],
                    'health' => [
                        'method' => 'GET',
                        'path' => '/api/health',
                        'description' => 'Health check endpoint'
                    ],
                    'database_test' => [
                        'method' => 'GET',
                        'path' => '/api/db-test',
                        'description' => 'Test database connectivity'
                    ],
                    'tables' => [
                        'method' => 'GET',
                        'path' => '/api/tables',
                        'description' => 'Get list of database tables'
                    ],
                    'table_operations' => [
                        'methods' => ['GET', 'POST', 'DELETE'],
                        'path' => '/api/tables/{tableName}',
                        'description' => 'Table management operations'
                    ]
                ]
            ];

            $this->success($data, 'API information retrieved successfully');
        });
    }

    /**
     * Handle the hello endpoint
     * 
     * Returns a friendly greeting with server information.
     * 
     * @api GET /api/hello
     * 
     * @return void
     */
    public function hello(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            $data = [
                'message' => 'Hello from the Backend API!',
                'timestamp' => date('Y-m-d H:i:s'),
                'server_info' => [
                    'php_version' => phpversion(),
                    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'container' => gethostname(),
                    'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                    'memory_peak' => $this->formatBytes(memory_get_peak_usage(true))
                ],
                'request_info' => [
                    'method' => $this->method,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'ip_address' => $this->getClientIp(),
                    'headers_count' => count($this->headers)
                ]
            ];

            $this->success($data, 'Hello endpoint accessed successfully');
        });
    }

    /**
     * Handle the status endpoint
     * 
     * Returns comprehensive status information about the backend service.
     * 
     * @api GET /api/status
     * 
     * @return void
     */
    public function status(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
            $uptime = round(microtime(true) - $startTime, 3);

            $data = [
                'status' => 'operational',
                'version' => '2.0.0',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
                'debug_mode' => (bool) ($_ENV['APP_DEBUG'] ?? false),
                'maintenance_mode' => (bool) ($_ENV['MAINTENANCE_MODE'] ?? false),
                'system_info' => [
                    'php_version' => phpversion(),
                    'server_time' => date('Y-m-d H:i:s'),
                    'timezone' => date_default_timezone_get(),
                    'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time') . 's',
                    'post_max_size' => ini_get('post_max_size'),
                    'upload_max_filesize' => ini_get('upload_max_filesize')
                ],
                'request_info' => [
                    'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                    'response_time' => $uptime . 's',
                    'method' => $this->method,
                    'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown'
                ]
            ];

            $this->success($data, 'Status information retrieved successfully');
        });
    }

    /**
     * Handle the database test endpoint
     * 
     * Tests database connectivity and returns connection information.
     * 
     * @api GET /api/db-test
     * 
     * @return void
     */
    public function databaseTest(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            try {
                $result = $this->db->testConnection();

                if ($result['status'] === 'success') {
                    // Add additional database information
                    $result['connection_info'] = [
                        'driver' => 'PostgreSQL PDO',
                        'host' => $_ENV['DB_HOST'] ?? 'localhost',
                        'port' => $_ENV['DB_PORT'] ?? 5432,
                        'database' => $_ENV['DB_NAME'] ?? 'unknown'
                    ];

                    $this->success($result, 'Database connection test successful');
                } else {
                    $this->error(
                        $result['message'] ?? 'Database connection failed',
                        500,
                        'database_error',
                        $result
                    );
                }
            } catch (\Throwable $e) {
                $this->error(
                    'Database connection test failed: ' . $e->getMessage(),
                    500,
                    'database_error'
                );
            }
        });
    }

    /**
     * Handle health check endpoint
     * 
     * Comprehensive health check including database connectivity and system resources.
     * 
     * @api GET /api/health
     * 
     * @return void
     */
    public function health(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            $checks = [];
            $overallHealthy = true;

            // Database health check
            try {
                $dbResult = $this->db->testConnection();
                $checks['database'] = [
                    'healthy' => $dbResult['status'] === 'success',
                    'message' => $dbResult['message'],
                    'response_time' => $this->measureExecutionTime(function () {
                        $this->db->testConnection();
                    })
                ];

                if (!$checks['database']['healthy']) {
                    $overallHealthy = false;
                }
            } catch (\Throwable $e) {
                $checks['database'] = [
                    'healthy' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ];
                $overallHealthy = false;
            }

            // Memory health check
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            $memoryPercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

            $checks['memory'] = [
                'healthy' => $memoryPercent < 90, // Alert if using >90% memory
                'usage' => $this->formatBytes($memoryUsage),
                'limit' => ini_get('memory_limit'),
                'percentage' => round($memoryPercent, 2) . '%',
                'message' => $memoryPercent < 90 ? 'Memory usage normal' : 'High memory usage detected'
            ];

            if (!$checks['memory']['healthy']) {
                $overallHealthy = false;
            }

            // Disk space check (if writable directory exists)
            $tempDir = sys_get_temp_dir();
            if (is_writable($tempDir)) {
                $freeBytes = disk_free_space($tempDir);
                $totalBytes = disk_total_space($tempDir);
                $usedPercent = $totalBytes > 0 ? (($totalBytes - $freeBytes) / $totalBytes) * 100 : 0;

                $checks['disk'] = [
                    'healthy' => $usedPercent < 95, // Alert if >95% full
                    'free_space' => $this->formatBytes($freeBytes),
                    'total_space' => $this->formatBytes($totalBytes),
                    'used_percentage' => round($usedPercent, 2) . '%',
                    'message' => $usedPercent < 95 ? 'Disk space sufficient' : 'Low disk space warning'
                ];

                if (!$checks['disk']['healthy']) {
                    $overallHealthy = false;
                }
            }

            // Response using the health check format
            Response::healthCheck($checks, '2.0.0');
        });
    }

    /**
     * Handle method not allowed responses
     * 
     * @param array $allowedMethods Allowed methods for the endpoint
     * @return void
     */
    public function methodNotAllowed(array $allowedMethods = []): void
    {
        $this->methodNotAllowed($allowedMethods);
    }

    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    /**
     * Parse memory limit string to bytes
     * 
     * @param string $limit Memory limit string (e.g., "128M")
     * @return int Memory limit in bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // Unlimited
        }

        $unit = strtoupper(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        switch ($unit) {
            case 'G':
                $value *= 1024;
            // fall through
            case 'M':
                $value *= 1024;
            // fall through
            case 'K':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }

        return 'Unknown';
    }

    /**
     * Measure execution time of a function
     * 
     * @param callable $func Function to measure
     * @return string Execution time with unit
     */
    private function measureExecutionTime(callable $func): string
    {
        $start = microtime(true);
        $func();
        $time = round((microtime(true) - $start) * 1000, 2);

        return $time . 'ms';
    }
}
