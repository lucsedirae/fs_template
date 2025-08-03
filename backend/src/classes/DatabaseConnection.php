<?php
/**
 * DatabaseConnection Class
 * 
 * Handles database connection management and basic connection operations.
 * Follows the singleton pattern to ensure only one connection instance.
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

class DatabaseConnection
{
    /**
     * Database connection configuration
     * 
     * @var array
     */
    private $config;

    /**
     * PDO connection instance
     * 
     * @var PDO|null
     */
    private $connection;

    /**
     * Singleton instance
     * 
     * @var DatabaseConnection|null
     */
    private static $instance = null;

    /**
     * DatabaseConnection constructor
     * 
     * @param array $config Database configuration array
     */
    private function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => 'database',
            'port' => '5432',
            'dbname' => 'appdb',
            'username' => 'appuser',
            'password' => 'apppassword'
        ], $config);
    }

    /**
     * Get singleton instance
     * 
     * @param array $config Optional configuration override
     * @return DatabaseConnection
     */
    public static function getInstance(array $config = []): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Get database connection
     * 
     * @return PDO|null
     * @throws DatabaseException If connection fails
     */
    public function getConnection(): ?PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Establish database connection
     * 
     * @return void
     * @throws DatabaseException If connection fails
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

        } catch (PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new DatabaseException("Failed to connect to database: " . $exception->getMessage());
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
            
            if ($connection === null) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to establish database connection'
                ];
            }

            $stmt = $connection->query('SELECT version()');
            $version = $stmt->fetch();

            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'postgres_version' => $version['version']
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute a prepared statement
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return PDOStatement
     * @throws DatabaseException If query execution fails
     */
    public function execute(string $query, array $params = []): PDOStatement
    {
        try {
            $connection = $this->getConnection();
            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution error: " . $e->getMessage());
            throw new DatabaseException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Begin database transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit database transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback database transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Get last insert ID
     * 
     * @param string|null $sequence Sequence name for PostgreSQL
     * @return string
     */
    public function getLastInsertId(?string $sequence = null): string
    {
        return $this->getConnection()->lastInsertId($sequence);
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {}
}
