<?php
/**
 * Database Class (Refactored)
 * 
 * Main database operations class that coordinates between connection management,
 * validation, and query building components. Focuses on business logic and
 * provides a clean API for database operations.
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    2.0.0
 * @since      PHP 7.4
 */

// Include required classes
require_once __DIR__ . '/classes/DatabaseConnection.php';
require_once __DIR__ . '/classes/DatabaseException.php';
require_once __DIR__ . '/classes/DatabaseValidator.php';
require_once __DIR__ . '/classes/QueryBuilder.php';

class Database
{
    /**
     * Database connection instance
     * 
     * @var DatabaseConnection
     */
    private $connection;

    /**
     * Database constructor
     * 
     * @param array $config Optional database configuration
     */
    public function __construct(array $config = [])
    {
        $this->connection = DatabaseConnection::getInstance($config);
    }

    /**
     * Test database connection
     * 
     * @return array Connection test result
     */
    public function testConnection(): array
    {
        return $this->connection->testConnection();
    }

    /**
     * Get list of all tables
     * 
     * @return array Operation result
     */
    public function getTables(): array
    {
        try {
            $query = QueryBuilder::buildGetTablesQuery();
            $stmt = $this->connection->execute($query);
            $tables = $stmt->fetchAll();

            return [
                'status' => 'success',
                'tables' => $tables,
                'count' => count($tables)
            ];

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Get tables error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve tables: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a new table
     * 
     * @param string $tableName Table name
     * @param array $columns Array of column definitions
     * @return array Operation result
     */
    public function createTable(string $tableName, array $columns): array
    {
        try {
            // Validate table name
            $tableValidation = DatabaseValidator::validateTableName($tableName);
            if (!$tableValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $tableValidation['errors'])
                ];
            }

            // Validate columns
            $columnsValidation = DatabaseValidator::validateTableColumns($columns);
            if (!$columnsValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $columnsValidation['errors'])
                ];
            }

            // Check if table already exists
            if ($this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '{$tableName}' already exists"
                ];
            }

            // Build and execute CREATE TABLE query
            $sql = QueryBuilder::buildCreateTableQuery($tableName, $columns);
            $this->connection->execute($sql);

            return [
                'status' => 'success',
                'message' => "Table '{$tableName}' created successfully",
                'sql' => $sql
            ];

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Create table error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to create table: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Drop a table
     * 
     * @param string $tableName Table name
     * @param bool $cascade Whether to cascade the drop
     * @return array Operation result
     */
    public function dropTable(string $tableName, bool $cascade = false): array
    {
        try {
            // Validate table name
            $validation = DatabaseValidator::validateTableName($tableName);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $validation['errors'])
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '{$tableName}' does not exist"
                ];
            }

            // Build and execute DROP TABLE query
            $sql = QueryBuilder::buildDropTableQuery($tableName, $cascade);
            $this->connection->execute($sql);

            return [
                'status' => 'success',
                'message' => "Table '{$tableName}' deleted successfully"
            ];

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Drop table error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to delete table: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get data from a specific table with pagination
     * 
     * @param string $tableName Table name
     * @param int $limit Number of rows to return
     * @param int $offset Number of rows to skip
     * @return array Operation result
     */
    public function getTableData(string $tableName, int $limit = 100, int $offset = 0): array
    {
        try {
            // Validate table name
            $validation = DatabaseValidator::validateTableName($tableName);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $validation['errors'])
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '{$tableName}' does not exist"
                ];
            }

            // Get table structure
            $columnsQuery = QueryBuilder::buildGetTableColumnsQuery();
            $stmt = $this->connection->execute($columnsQuery, ['table_name' => $tableName]);
            $columns = $stmt->fetchAll();

            // Get total count
            $countQuery = QueryBuilder::buildGetTableCountQuery($tableName);
            $stmt = $this->connection->execute($countQuery);
            $totalRows = $stmt->fetch()['total'];

            // Get table data with pagination
            $dataQuery = QueryBuilder::buildGetTableDataQuery($tableName);
            $stmt = $this->connection->execute($dataQuery, [
                'limit' => $limit,
                'offset' => $offset
            ]);
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

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Get table data error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve table data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get detailed table schema information
     * 
     * @param string $tableName Table name
     * @return array Operation result
     */
    public function getTableSchema(string $tableName): array
    {
        try {
            // Validate table name
            $validation = DatabaseValidator::validateTableName($tableName);
            if (!$validation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $validation['errors'])
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '{$tableName}' does not exist"
                ];
            }

            // Get detailed column information
            $query = QueryBuilder::buildGetTableSchemaQuery();
            $stmt = $this->connection->execute($query, ['table_name' => $tableName]);
            $columns = $stmt->fetchAll();

            // Get table row count
            $countQuery = QueryBuilder::buildGetTableCountQuery($tableName);
            $stmt = $this->connection->execute($countQuery);
            $rowCount = $stmt->fetch()['total'];

            return [
                'status' => 'success',
                'table_name' => $tableName,
                'columns' => $columns,
                'row_count' => $rowCount
            ];

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Get table schema error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve table schema: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add a new column to an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $columnType Column data type
     * @param bool $isNullable Whether column allows NULL values
     * @param mixed $defaultValue Default value for the column
     * @return array Operation result
     */
    public function addColumn(
        string $tableName,
        string $columnName,
        string $columnType,
        bool $isNullable = true,
        $defaultValue = null
    ): array {
        try {
            // Validate inputs
            $tableValidation = DatabaseValidator::validateTableName($tableName);
            if (!$tableValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $tableValidation['errors'])
                ];
            }

            $columnValidation = DatabaseValidator::validateColumnName($columnName);
            if (!$columnValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $columnValidation['errors'])
                ];
            }

            $typeValidation = DatabaseValidator::validateDataType($columnType);
            if (!$typeValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $typeValidation['errors'])
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '{$tableName}' does not exist"
                ];
            }

            // Check if column already exists
            if ($this->columnExists($tableName, $columnName)) {
                return [
                    'status' => 'error',
                    'message' => "Column '{$columnName}' already exists in table '{$tableName}'"
                ];
            }

            // Build column definition
            $column = [
                'name' => $columnName,
                'type' => $columnType,
                'nullable' => $isNullable
            ];

            // Build and execute ADD COLUMN query
            $sql = QueryBuilder::buildAddColumnQuery($tableName, $column);
            
            // Handle default value separately if provided
            if ($defaultValue !== null) {
                $connection = $this->connection->getConnection();
                $sql .= " DEFAULT " . $connection->quote($defaultValue);
            }

            $this->connection->execute($sql);

            return [
                'status' => 'success',
                'message' => "Column '{$columnName}' added to table '{$tableName}' successfully",
                'sql' => $sql
            ];

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Add column error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to add column: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Drop a column from an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param bool $cascade Whether to cascade the drop
     * @return array Operation result
     */
    public function dropColumn(string $tableName, string $columnName, bool $cascade = false): array
    {
        try {
            // Validate inputs
            $tableValidation = DatabaseValidator::validateTableName($tableName);
            if (!$tableValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $tableValidation['errors'])
                ];
            }

            $columnValidation = DatabaseValidator::validateColumnName($columnName);
            if (!$columnValidation['valid']) {
                return [
                    'status' => 'error',
                    'message' => implode(', ', $columnValidation['errors'])
                ];
            }

            // Check if table exists
            if (!$this->tableExists($tableName)) {
                return [
                    'status' => 'error',
                    'message' => "Table '{$tableName}' does not exist"
                ];
            }

            // Check if column exists
            if (!$this->columnExists($tableName, $columnName)) {
                return [
                    'status' => 'error',
                    'message' => "Column '{$columnName}' does not exist in table '{$tableName}'"
                ];
            }

            // Check if this is the only column
            $columnCount = $this->getColumnCount($tableName);
            if ($columnCount <= 1) {
                return [
                    'status' => 'error',
                    'message' => "Cannot drop the last column from table '{$tableName}'"
                ];
            }

            // Build and execute DROP COLUMN query
            $sql = QueryBuilder::buildDropColumnQuery($tableName, $columnName, $cascade);
            $this->connection->execute($sql);

            return [
                'status' => 'success',
                'message' => "Column '{$columnName}' dropped from table '{$tableName}' successfully",
                'sql' => $sql
            ];

        } catch (DatabaseException $e) {
            $e->logError();
            return $e->toArray();
        } catch (Exception $e) {
            error_log("Drop column error: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to drop column: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generic CRUD operations
     */

    /**
     * Execute a SELECT query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array|null Query results
     */
    public function select(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->execute($query, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Select error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute an INSERT query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return string|false Last insert ID or false on failure
     */
    public function insert(string $query, array $params = [])
    {
        try {
            $this->connection->execute($query, $params);
            return $this->connection->getLastInsertId();
        } catch (Exception $e) {
            error_log("Insert error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute an UPDATE query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return int|false Number of affected rows or false on failure
     */
    public function update(string $query, array $params = [])
    {
        try {
            $stmt = $this->connection->execute($query, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a DELETE query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return int|false Number of affected rows or false on failure
     */
    public function delete(string $query, array $params = [])
    {
        try {
            $stmt = $this->connection->execute($query, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper methods
     */

    /**
     * Check if table exists
     * 
     * @param string $tableName Table name
     * @return bool True if table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $query = QueryBuilder::buildTableExistsQuery();
            $stmt = $this->connection->execute($query, ['table_name' => $tableName]);
            return $stmt->fetch()['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if a column exists in a table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool True if column exists
     */
    private function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $query = QueryBuilder::buildColumnExistsQuery();
            $stmt = $this->connection->execute($query, [
                'table_name' => $tableName,
                'column_name' => $columnName
            ]);
            return $stmt->fetch()['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the number of columns in a table
     * 
     * @param string $tableName Table name
     * @return int Number of columns
     */
    private function getColumnCount(string $tableName): int
    {
        try {
            $query = QueryBuilder::buildColumnExistsQuery();
            // Modify query to count all columns for the table
            $query = str_replace('AND column_name = :column_name', '', $query);
            $stmt = $this->connection->execute($query, ['table_name' => $tableName]);
            return $stmt->fetch()['count'];
        } catch (Exception $e) {
            return 0;
        }
    }
}
