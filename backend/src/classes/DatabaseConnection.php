<?php

class DatabaseConnection
{
    private $config;
    private $connection;
    private static $instance = null;

    private function __construct(array $config = [])
    {
        $envConfig = [
            'host' => $this->getEnvVar('DB_HOST'),
            'port' => $this->getEnvVar('DB_PORT'),
            'dbname' => $this->getEnvVar('DB_NAME', $this->getEnvVar('DB_DATABASE')),
            'username' => $this->getEnvVar('DB_USERNAME', $this->getEnvVar('DB_USER')),
            'password' => $this->getEnvVar('DB_PASSWORD')
        ];

        foreach ($envConfig as $key => $value) {
            if ($value === null) {
                throw new DatabaseException("Required environment variable for '{$key}' not set. Please check your Docker configuration.");
            }
        }

        $this->config = array_merge($envConfig, $config);
        $this->logConfiguration();
    }

    private function getEnvVar($key, $fallback = null)
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return null;
    }

    private function logConfiguration()
    {
        $logConfig = $this->config;
        $logConfig['password'] = '***';
        error_log("DatabaseConnection config: " . json_encode($logConfig));
    }

    public static function getInstance(array $config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    private function connect()
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

            error_log("Database connection established successfully");

        } catch (PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new DatabaseException("Failed to connect to database: " . $exception->getMessage());
        }
    }

    public function testConnection()
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
                'postgres_version' => $version['version'],
                'connection_config' => [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'database' => $this->config['dbname']
                ]
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    public function execute(string $query, array $params = [])
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

    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit()
    {
        return $this->getConnection()->commit();
    }

    public function rollback()
    {
        return $this->getConnection()->rollBack();
    }

    public function getLastInsertId($sequence = null)
    {
        return $this->getConnection()->lastInsertId($sequence);
    }

    private function __clone() 
    {
    }

    public function __wakeup() 
    {
    }
}