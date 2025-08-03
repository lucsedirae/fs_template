<?php
/**
 * Enhanced Database Connection Class
 * 
 * Manages PostgreSQL database connections with improved error handling,
 * configuration management, and connection pooling capabilities.
 * 
 * @package    Backend\Database
 * @author     Your Team
 * @version    2.0.0
 * @since      PHP 7.4
 */

namespace Backend\Database;

use Backend\Config\Config;
use Backend\Exceptions\DatabaseException;
use Backend\Exceptions\ConfigurationException;
use PDO;
use PDOStatement;
use PDOException;

class Connection
{
    /**
     * PDO connection instance
     * 
     * @var PDO|null
     */
    private $connection = null;

    /**
     * Database configuration
     * 
     * @var array
     */
    private $config;

    /**
     * Connection singleton instance
     * 
     * @var self|null
     */
    private static $instance = null;

    /**
     * Connection state
     * 
     * @var bool
     */
    private $connected = false;

    /**
     * Last query execution time
     * 
     * @var float
     */
    private $lastQueryTime = 0;

    /**
     * Query count for current connection
     * 
     * @var int
     */
    private $queryCount = 0;

    /**
     * Connection constructor
     * 
     * @param array|null $config Database configuration (optional)
     * @throws ConfigurationException|DatabaseException
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? Config::database();
        $this->validateConfig();
    }

    /**
     * Get singleton instance
     * 
     * @param array|null $config Optional configuration override
     * @return self Connection instance
     * @throws ConfigurationException|DatabaseException
     */
    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null || $config !== null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Get PDO connection instance
     * 
     * @return PDO Active PDO connection
     * @throws DatabaseException
     */
    public function getConnection(): PDO
    {
        if (!$this->connected || $this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Execute SQL query with parameters
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return PDOStatement Executed statement
     * @throws DatabaseException
     */
    public function execute(string $query, array $params = []): PDOStatement
    {
        try {
            $connection = $this->getConnection();
            
            $startTime = microtime(true);
            $stmt = $connection->prepare($query);
            
            if (!$stmt) {
                throw new DatabaseException('Failed to prepare query: ' . implode(', ', $connection->errorInfo()));
            }

            $success = $stmt->execute($params);
            
            if (!$success) {
                throw new DatabaseException('Query execution failed: ' . implode(', ', $stmt->errorInfo()));
            }

            $this->lastQueryTime = microtime(true) - $startTime;
            $this->queryCount++;

            // Log slow queries
            if ($this->lastQueryTime > 1.0) { // Log queries taking more than 1 second
                error_log(sprintf(
                    "Slow query detected: %.3fs - %s",
                    $this->lastQueryTime,
                    $this->sanitizeQueryForLogging($query)
                ));
            }

            return $stmt;

        } catch (PDOException $e) {
            throw new DatabaseException('Database query failed: ' . $e->getMessage(), [
                'query' => $this->sanitizeQueryForLogging($query),
                'params' => $this->sanitizeParamsForLogging($params),
                'error_code' => $e->getCode()
            ]);
        }
    }

    /**
     * Execute multiple queries in a transaction
     * 
     * @param callable $callback Callback containing queries to execute
     * @return mixed Callback return value
     * @throws DatabaseException
     */
    public function transaction(callable $callback)
    {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            
            if ($e instanceof DatabaseException) {
                throw $e;
            }

            throw new DatabaseException('Transaction failed: ' . $e->getMessage(), [
                'original_error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);
        }
    }

    /**
     * Begin database transaction
     * 
     * @return bool True on success
     * @throws DatabaseException
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->getConnection()->beginTransaction();
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to begin transaction: ' . $e->getMessage());
        }
    }

    /**
     * Commit database transaction
     * 
     * @return bool True on success
     * @throws DatabaseException
     */
    public function commit(): bool
    {
        try {
            return $this->getConnection()->commit();
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to commit transaction: ' . $e->getMessage());
        }
    }

    /**
     * Rollback database transaction
     * 
     * @return bool True on success
     * @throws DatabaseException
     */
    public function rollback(): bool
    {
        try {
            return $this->getConnection()->rollBack();
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to rollback transaction: ' . $e->getMessage());
        }
    }

    /**
     * Get last insert ID
     * 
     * @param string|null $sequence Sequence name for PostgreSQL
     * @return string Last insert ID
     * @throws DatabaseException
     */
    public function getLastInsertId(?string $sequence = null): string
    {
        try {
            return $this->getConnection()->lastInsertId($sequence);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to get last insert ID: ' . $e->getMessage());
        }
    }

    /**
     * Test database connection
     * 
     * @return array Connection test result
     */
    public function testConnection(): array
    {
        try {
            $connection = $this->getConnection();
            
            $startTime = microtime(true);
            $stmt = $connection->query('SELECT version(), current_database(), current_user');
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'response_time' => $responseTime . 'ms',
                'database_info' => [
                    'version' => $info['version'] ?? 'Unknown',
                    'database' => $info['current_database'] ?? 'Unknown',
                    'user' => $info['current_user'] ?? 'Unknown'
                ],
                'connection_info' => [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'database' => $this->config['name'],
                    'query_count' => $this->queryCount,
                    'last_query_time' => $this->lastQueryTime . 's'
                ]
            ];

        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'config' => [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'database' => $this->config['name']
                ]
            ];
        }
    }

    /**
     * Get connection statistics
     * 
     * @return array Connection statistics
     */
    public function getStats(): array
    {
        return [
            'connected' => $this->connected,
            'query_count' => $this->queryCount,
            'last_query_time' => $this->lastQueryTime,
            'config' => [
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'database' => $this->config['name'],
                'timeout' => $this->config['timeout']
            ]
        ];
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public function close(): void
    {
        $this->connection = null;
        $this->connected = false;
    }

    /**
     * Check if connection is active
     * 
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        return $this->connected && $this->connection !== null;
    }

    /**
     * Quote string for SQL
     * 
     * @param string $string String to quote
     * @return string Quoted string
     * @throws DatabaseException
     */
    public function quote(string $string): string
    {
        try {
            return $this->getConnection()->quote($string);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to quote string: ' . $e->getMessage());
        }
    }

    /**
     * Create database connection
     * 
     * @throws DatabaseException
     * @return void
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['name']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => $this->config['timeout'],
                PDO::ATTR_PERSISTENT => false // Disable persistent connections for now
            ];

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );

            // Set connection charset if specified
            if (!empty($this->config['charset'])) {
                $this->connection->exec("SET NAMES '{$this->config['charset']}'");
            }

            $this->connected = true;
            $this->queryCount = 0;

            error_log(sprintf(
                'Database connection established: %s@%s:%d/%s',
                $this->config['username'],
                $this->config['host'],
                $this->config['port'],
                $this->config['name']
            ));

        } catch (PDOException $e) {
            $this->connected = false;
            
            error_log(sprintf(
                'Database connection failed: %s@%s:%d/%s - %s',
                $this->config['username'],
                $this->config['host'],
                $this->config['port'],
                $this->config['name'],
                $e->getMessage()
            ));

            throw new DatabaseException(
                'Failed to connect to database: ' . $e->getMessage(),
                [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'database' => $this->config['name'],
                    'error_code' => $e->getCode()
                ]
            );
        }
    }

    /**
     * Validate database configuration
     * 
     * @throws ConfigurationException
     * @return void
     */
    private function validateConfig(): void
    {
        $required = ['host', 'port', 'name', 'username'];
        $missing = [];

        foreach ($required as $key) {
            if (!isset($this->config[$key]) || $this->config[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new ConfigurationException(
                'Missing required database configuration: ' . implode(', ', $missing)
            );
        }

        // Validate port number
        if (!is_numeric($this->config['port']) || $this->config['port'] <= 0) {
            throw new ConfigurationException('Invalid database port: ' . $this->config['port']);
        }

        // Set defaults for optional config
        $this->config = array_merge([
            'password' => '',
            'charset' => 'utf8',
            'timeout' => 30
        ], $this->config);
    }

    /**
     * Sanitize query for logging (remove sensitive data)
     * 
     * @param string $query SQL query
     * @return string Sanitized query
     */
    private function sanitizeQueryForLogging(string $query): string
    {
        // Remove potential sensitive data patterns
        $patterns = [
            "/password\s*=\s*'[^']*'/i" => "password='***'",
            "/password\s*=\s*\"[^\"]*\"/i" => 'password="***"',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $query);
    }

    /**
     * Sanitize parameters for logging
     * 
     * @param array $params Query parameters
     * @return array Sanitized parameters
     */
    private function sanitizeParamsForLogging(array $params): array
    {
        $sanitized = [];
        
        foreach ($params as $key => $value) {
            if (is_string($key) && stripos($key, 'password') !== false) {
                $sanitized[$key] = '***';
            } else {
                $sanitized[$key] = is_string($value) && strlen($value) > 100 
                    ? substr($value, 0, 100) . '...' 
                    : $value;
            }
        }

        return $sanitized;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {}

    /**
     * Cleanup on destruction
     */
    public function __destruct()
    {
        $this->close();
    }
}