<?php
// backend/src/Database.php

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct()
    {
        // Database configuration from Docker environment
        $this->host = 'database'; // Docker service name
        $this->db_name = 'appdb';
        $this->username = 'appuser';
        $this->password = 'apppassword';
        $this->port = '5432';
    }

    // Get database connection
    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->db_name;

            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }

        return $this->conn;
    }

    // Test database connection
    public function testConnection()
    {
        $connection = $this->getConnection();

        if ($connection === null) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to database'
            ];
        }

        try {
            // Simple query to test connection
            $stmt = $connection->query('SELECT version()');
            $version = $stmt->fetch();

            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'postgres_version' => $version['version']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Database query failed: ' . $e->getMessage()
            ];
        }
    }

    // Get list of all tables
    public function getTables()
    {
        try {
            $connection = $this->getConnection();
            if (!$connection) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to database'
                ];
            }

            $query = "
                SELECT 
                    table_name,
                    table_type
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_type = 'BASE TABLE'
                ORDER BY table_name
            ";

            $stmt = $connection->prepare($query);
            $stmt->execute();
            $tables = $stmt->fetchAll();

            return [
                'status' => 'success',
                'tables' => $tables,
                'count' => count($tables)
            ];

        } catch (PDOException $e) {
            error_log("Get tables error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve tables: ' . $e->getMessage()
            ];
        }
    }

    // Create a new table
    public function createTable($tableName, $columns)
    {
        try {
            $connection = $this->getConnection();
            if (!$connection) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to database'
                ];
            }

            // Validate table name
            if (!$this->isValidTableName($tableName)) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid table name. Use only letters, numbers, and underscores.'
                ];
            }

            // Check if table already exists
            if ($this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '$tableName' already exists."
                ];
            }

            // Build CREATE TABLE SQL
            $sql = "CREATE TABLE " . $this->sanitizeTableName($tableName) . " (\n";
            $columnDefinitions = [];
            $primaryKeys = [];

            foreach ($columns as $column) {
                if (empty($column['name'])) {
                    return [
                        'status' => 'error',
                        'message' => 'Column name cannot be empty.'
                    ];
                }

                if (!$this->isValidColumnName($column['name'])) {
                    return [
                        'status' => 'error',
                        'message' => "Invalid column name: '{$column['name']}'. Use only letters, numbers, and underscores."
                    ];
                }

                $columnDef = $this->sanitizeColumnName($column['name']) . ' ' . $column['type'];

                if (isset($column['nullable']) && !$column['nullable']) {
                    $columnDef .= ' NOT NULL';
                }

                if (isset($column['isPrimary']) && $column['isPrimary']) {
                    $primaryKeys[] = $this->sanitizeColumnName($column['name']);
                }

                $columnDefinitions[] = $columnDef;
            }

            $sql .= implode(",\n", $columnDefinitions);

            // Add primary key constraint if any
            if (!empty($primaryKeys)) {
                $sql .= ",\nPRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
            }

            $sql .= "\n)";

            // Execute the CREATE TABLE statement
            $stmt = $connection->prepare($sql);
            $stmt->execute();

            return [
                'status' => 'success',
                'message' => "Table '$tableName' created successfully.",
                'sql' => $sql
            ];

        } catch (PDOException $e) {
            error_log("Create table error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to create table: ' . $e->getMessage()
            ];
        }
    }

    // Drop a table
    public function dropTable($tableName)
    {
        try {
            $connection = $this->getConnection();
            if (!$connection) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to database'
                ];
            }

            // Validate table name
            if (!$this->isValidTableName($tableName)) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid table name.'
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '$tableName' does not exist."
                ];
            }

            $sql = "DROP TABLE " . $this->sanitizeTableName($tableName);
            $stmt = $connection->prepare($sql);
            $stmt->execute();

            return [
                'status' => 'success',
                'message' => "Table '$tableName' deleted successfully."
            ];

        } catch (PDOException $e) {
            error_log("Drop table error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to delete table: ' . $e->getMessage()
            ];
        }
    }

    // Check if table exists
    private function tableExists($tableName)
    {
        try {
            $connection = $this->getConnection();
            if (!$connection)
                return false;

            $query = "
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = :table_name
            ";

            $stmt = $connection->prepare($query);
            $stmt->bindParam(':table_name', $tableName);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Validate table name
    private function isValidTableName($name)
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) && strlen($name) <= 63;
    }

    // Validate column name
    private function isValidColumnName($name)
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) && strlen($name) <= 63;
    }

    // Sanitize table name for SQL queries
    private function sanitizeTableName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    // Sanitize column name for SQL queries
    private function sanitizeColumnName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    // Generic CRUD operations base methods

    /**
     * Execute a SELECT query
     */
    public function select($query, $params = [])
    {
        try {
            $connection = $this->getConnection();
            if (!$connection)
                return null;

            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Select error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute an INSERT query
     */
    public function insert($query, $params = [])
    {
        try {
            $connection = $this->getConnection();
            if (!$connection)
                return false;

            $stmt = $connection->prepare($query);
            $result = $stmt->execute($params);

            // Return the last inserted ID for PostgreSQL
            return $result ? $connection->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute an UPDATE query
     */
    public function update($query, $params = [])
    {
        try {
            $connection = $this->getConnection();
            if (!$connection)
                return false;

            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a DELETE query
     */
    public function delete($query, $params = [])
    {
        try {
            $connection = $this->getConnection();
            if (!$connection)
                return false;

            $stmt = $connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get data from a specific table with pagination
     */
    public function getTableData($tableName, $limit = 100, $offset = 0)
    {
        try {
            $connection = $this->getConnection();
            if (!$connection) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to database'
                ];
            }

            // Validate table name
            if (!$this->isValidTableName($tableName)) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid table name.'
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '$tableName' does not exist."
                ];
            }

            // Get table structure first
            $structureQuery = "
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = :table_name
            ORDER BY ordinal_position
        ";

            $stmt = $connection->prepare($structureQuery);
            $stmt->bindParam(':table_name', $tableName);
            $stmt->execute();
            $columns = $stmt->fetchAll();

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM " . $this->sanitizeTableName($tableName);
            $stmt = $connection->prepare($countQuery);
            $stmt->execute();
            $totalRows = $stmt->fetch()['total'];

            // Get table data with pagination
            $dataQuery = "
            SELECT * FROM " . $this->sanitizeTableName($tableName) . " 
            ORDER BY 1
            LIMIT :limit OFFSET :offset
        ";

            $stmt = $connection->prepare($dataQuery);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            return [
                'status' => 'success',
                'table_name' => $tableName,
                'columns' => $columns,
                'rows' => $rows,
                'total_rows' => $totalRows,
                'current_page' => floor($offset / $limit) + 1,
                'per_page' => $limit,
                'has_more' => ($offset + $limit) < $totalRows
            ];

        } catch (PDOException $e) {
            error_log("Get table data error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve table data: ' . $e->getMessage()
            ];
        }
    }
}
