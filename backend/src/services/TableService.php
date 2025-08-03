<?php
/**
 * Table Service Class
 * 
 * Handles all table-related business logic including creation, deletion,
 * structure management, and data operations.
 * 
 * @package    Backend\Services
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Services;

use Backend\Exceptions\ValidationException;
use Backend\Exceptions\DatabaseException;
use Backend\Exceptions\NotFoundException;
use Backend\Exceptions\ConflictException;
use Backend\Database\QueryBuilder;

class TableService extends BaseService
{
    /**
     * Valid PostgreSQL data types
     */
    private const VALID_DATA_TYPES = [
        'SERIAL',
        'INTEGER',
        'BIGINT',
        'SMALLINT',
        'VARCHAR',
        'TEXT',
        'CHAR',
        'BOOLEAN',
        'DATE',
        'TIMESTAMP',
        'TIME',
        'DECIMAL',
        'NUMERIC',
        'REAL',
        'DOUBLE PRECISION',
        'JSON',
        'JSONB',
        'UUID'
    ];

    /**
     * Get list of all tables in the database
     * 
     * @param bool $includeSystemTables Whether to include system tables
     * @return array Array of table information
     * @throws DatabaseException
     */
    public function getAllTables(bool $includeSystemTables = false): array
    {
        try {
            $this->log('get_all_tables_started', ['include_system' => $includeSystemTables]);

            $query = QueryBuilder::buildGetTablesQuery();
            $stmt = $this->db->execute($query);
            $tables = $stmt->fetchAll();

            $this->log('get_all_tables_completed', ['count' => count($tables)]);

            return $tables;
        } catch (\Throwable $e) {
            $this->log('get_all_tables_failed', ['error' => $e->getMessage()], 'error');
            throw new DatabaseException('Failed to retrieve tables: ' . $e->getMessage());
        }
    }

    /**
     * Create a new table
     * 
     * @param string $tableName Table name
     * @param array $columns Array of column definitions
     * @return array Creation result with SQL
     * @throws ValidationException|DatabaseException|ConflictException
     */
    public function createTable(string $tableName, array $columns): array
    {
        $this->log('create_table_started', ['table' => $tableName, 'columns' => count($columns)]);

        // Validate input
        $this->validateTableCreation($tableName, $columns);

        // Check if table already exists
        if ($this->tableExists($tableName)) {
            throw new ConflictException("Table '{$tableName}' already exists");
        }

        try {
            return $this->transaction(function () use ($tableName, $columns) {
                // Build and execute CREATE TABLE query
                $sql = QueryBuilder::buildCreateTableQuery($tableName, $columns);
                $this->db->execute($sql);

                $this->log('create_table_completed', ['table' => $tableName, 'sql' => $sql]);

                return [
                    'table_name' => $tableName,
                    'sql' => $sql,
                    'columns' => $columns
                ];
            });
        } catch (\Throwable $e) {
            $this->log('create_table_failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ], 'error');

            throw new DatabaseException('Failed to create table: ' . $e->getMessage());
        }
    }

    /**
     * Drop a table
     * 
     * @param string $tableName Table name
     * @param bool $cascade Whether to cascade the drop
     * @return array Deletion result
     * @throws NotFoundException|DatabaseException
     */
    public function dropTable(string $tableName, bool $cascade = false): array
    {
        $this->log('drop_table_started', ['table' => $tableName, 'cascade' => $cascade]);

        // Validate table name
        $this->validateTableName($tableName);

        // Check if table exists
        if (!$this->tableExists($tableName)) {
            throw new NotFoundException("Table '{$tableName}' does not exist");
        }

        try {
            return $this->transaction(function () use ($tableName, $cascade) {
                // Build and execute DROP TABLE query
                $sql = QueryBuilder::buildDropTableQuery($tableName, $cascade);
                $this->db->execute($sql);

                $this->log('drop_table_completed', ['table' => $tableName]);

                return [
                    'table_name' => $tableName,
                    'sql' => $sql
                ];
            });
        } catch (\Throwable $e) {
            $this->log('drop_table_failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ], 'error');

            throw new DatabaseException('Failed to drop table: ' . $e->getMessage());
        }
    }

    /**
     * Get table data with pagination
     * 
     * @param string $tableName Table name
     * @param int $limit Number of rows to return
     * @param int $offset Number of rows to skip
     * @return array Table data with metadata
     * @throws NotFoundException|DatabaseException
     */
    public function getTableData(string $tableName, int $limit = 50, int $offset = 0): array
    {
        $this->log('get_table_data_started', [
            'table' => $tableName,
            'limit' => $limit,
            'offset' => $offset
        ]);

        // Validate table name
        $this->validateTableName($tableName);

        // Check if table exists
        if (!$this->tableExists($tableName)) {
            throw new NotFoundException("Table '{$tableName}' does not exist");
        }

        try {
            // Get table structure
            $columns = $this->getTableColumns($tableName);

            // Get total count
            $totalRows = $this->getTableRowCount($tableName);

            // Get table data
            $dataQuery = QueryBuilder::buildGetTableDataQuery($tableName);
            $stmt = $this->db->execute($dataQuery, [
                'limit' => $limit,
                'offset' => $offset
            ]);
            $rows = $stmt->fetchAll();

            $result = [
                'table_name' => $tableName,
                'columns' => $columns,
                'rows' => $rows,
                'total_rows' => $totalRows,
                'current_page' => floor($offset / $limit) + 1,
                'per_page' => $limit,
                'has_more' => ($offset + $limit) < $totalRows
            ];

            $this->log('get_table_data_completed', [
                'table' => $tableName,
                'rows_returned' => count($rows),
                'total_rows' => $totalRows
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->log('get_table_data_failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ], 'error');

            throw new DatabaseException('Failed to retrieve table data: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed table schema information
     * 
     * @param string $tableName Table name
     * @return array Table schema with column details
     * @throws NotFoundException|DatabaseException
     */
    public function getTableSchema(string $tableName): array
    {
        $this->log('get_table_schema_started', ['table' => $tableName]);

        // Validate table name
        $this->validateTableName($tableName);

        // Check if table exists
        if (!$this->tableExists($tableName)) {
            throw new NotFoundException("Table '{$tableName}' does not exist");
        }

        try {
            // Get detailed column information
            $query = QueryBuilder::buildGetTableSchemaQuery();
            $stmt = $this->db->execute($query, ['table_name' => $tableName]);
            $columns = $stmt->fetchAll();

            // Get table row count
            $rowCount = $this->getTableRowCount($tableName);

            $result = [
                'table_name' => $tableName,
                'columns' => $columns,
                'row_count' => $rowCount
            ];

            $this->log('get_table_schema_completed', [
                'table' => $tableName,
                'column_count' => count($columns)
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->log('get_table_schema_failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ], 'error');

            throw new DatabaseException('Failed to retrieve table schema: ' . $e->getMessage());
        }
    }

    /**
     * Add a column to an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $columnType Column data type
     * @param bool $isNullable Whether column allows NULL values
     * @param mixed $defaultValue Default value for the column
     * @return array Operation result
     * @throws ValidationException|NotFoundException|ConflictException|DatabaseException
     */
    public function addColumn(
        string $tableName,
        string $columnName,
        string $columnType,
        bool $isNullable = true,
        $defaultValue = null
    ): array {
        $this->log('add_column_started', [
            'table' => $tableName,
            'column' => $columnName,
            'type' => $columnType
        ]);

        // Validate inputs
        $this->validateTableName($tableName);
        $this->validateColumnName($columnName);
        $this->validateDataType($columnType);

        // Check if table exists
        if (!$this->tableExists($tableName)) {
            throw new NotFoundException("Table '{$tableName}' does not exist");
        }

        // Check if column already exists
        if ($this->columnExists($tableName, $columnName)) {
            throw new ConflictException("Column '{$columnName}' already exists in table '{$tableName}'");
        }

        try {
            return $this->transaction(function () use ($tableName, $columnName, $columnType, $isNullable, $defaultValue) {
                // Build column definition
                $column = [
                    'name' => $columnName,
                    'type' => $columnType,
                    'nullable' => $isNullable
                ];

                // Build and execute ADD COLUMN query
                $sql = QueryBuilder::buildAddColumnQuery($tableName, $column);

                // Handle default value if provided
                if ($defaultValue !== null) {
                    $connection = $this->db->getConnection();
                    $sql .= " DEFAULT " . $connection->quote($defaultValue);
                }

                $this->db->execute($sql);

                $this->log('add_column_completed', [
                    'table' => $tableName,
                    'column' => $columnName,
                    'sql' => $sql
                ]);

                return [
                    'table_name' => $tableName,
                    'column_name' => $columnName,
                    'sql' => $sql
                ];
            });
        } catch (\Throwable $e) {
            $this->log('add_column_failed', [
                'table' => $tableName,
                'column' => $columnName,
                'error' => $e->getMessage()
            ], 'error');

            throw new DatabaseException('Failed to add column: ' . $e->getMessage());
        }
    }

    /**
     * Drop a column from an existing table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param bool $cascade Whether to cascade the drop
     * @return array Operation result
     * @throws ValidationException|NotFoundException|DatabaseException
     */
    public function dropColumn(string $tableName, string $columnName, bool $cascade = false): array
    {
        $this->log('drop_column_started', [
            'table' => $tableName,
            'column' => $columnName,
            'cascade' => $cascade
        ]);

        // Validate inputs
        $this->validateTableName($tableName);
        $this->validateColumnName($columnName);

        // Check if table exists
        if (!$this->tableExists($tableName)) {
            throw new NotFoundException("Table '{$tableName}' does not exist");
        }

        // Check if column exists
        if (!$this->columnExists($tableName, $columnName)) {
            throw new NotFoundException("Column '{$columnName}' does not exist in table '{$tableName}'");
        }

        // Check if this is the only column
        $columnCount = $this->getColumnCount($tableName);
        if ($columnCount <= 1) {
            throw new ValidationException("Cannot drop the last column from table '{$tableName}'");
        }

        try {
            return $this->transaction(function () use ($tableName, $columnName, $cascade) {
                // Build and execute DROP COLUMN query
                $sql = QueryBuilder::buildDropColumnQuery($tableName, $columnName, $cascade);
                $this->db->execute($sql);

                $this->log('drop_column_completed', [
                    'table' => $tableName,
                    'column' => $columnName,
                    'sql' => $sql
                ]);

                return [
                    'table_name' => $tableName,
                    'column_name' => $columnName,
                    'sql' => $sql
                ];
            });
        } catch (\Throwable $e) {
            $this->log('drop_column_failed', [
                'table' => $tableName,
                'column' => $columnName,
                'error' => $e->getMessage()
            ], 'error');

            throw new DatabaseException('Failed to drop column: ' . $e->getMessage());
        }
    }

    /**
     * Check if table exists
     * 
     * @param string $tableName Table name
     * @return bool True if table exists
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $query = QueryBuilder::buildTableExistsQuery();
            $stmt = $this->db->execute($query, ['table_name' => $tableName]);
            return $stmt->fetch()['count'] > 0;
        } catch (\Throwable $e) {
            $this->log('table_exists_check_failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    /**
     * Check if column exists in table
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool True if column exists
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $query = QueryBuilder::buildColumnExistsQuery();
            $stmt = $this->db->execute($query, [
                'table_name' => $tableName,
                'column_name' => $columnName
            ]);
            return $stmt->fetch()['count'] > 0;
        } catch (\Throwable $e) {
            $this->log('column_exists_check_failed', [
                'table' => $tableName,
                'column' => $columnName,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    /**
     * Validate table creation request
     * 
     * @param string $tableName Table name
     * @param array $columns Column definitions
     * @throws ValidationException
     */
    private function validateTableCreation(string $tableName, array $columns): void
    {
        // Validate table name
        $this->validateTableName($tableName);

        // Validate columns array
        if (empty($columns)) {
            throw new ValidationException('At least one column is required');
        }

        $columnNames = [];
        $primaryKeyCount = 0;

        foreach ($columns as $index => $column) {
            $this->validateColumnDefinition($column, $index + 1);

            // Check for duplicate column names
            $columnName = strtolower(trim($column['name']));
            if (in_array($columnName, $columnNames)) {
                throw new ValidationException("Duplicate column name: '{$column['name']}'");
            }
            $columnNames[] = $columnName;

            // Count primary keys
            if (isset($column['isPrimary']) && $column['isPrimary']) {
                $primaryKeyCount++;
            }
        }
    }

    /**
     * Validate table name
     * 
     * @param string $tableName Table name
     * @throws ValidationException
     */
    private function validateTableName(string $tableName): void
    {
        $rules = [
            'tableName' => 'required|string|min:1|max:63|regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'
        ];

        $this->validate(['tableName' => $tableName], $rules, 'table name');
    }

    /**
     * Validate column name
     * 
     * @param string $columnName Column name
     * @throws ValidationException
     */
    private function validateColumnName(string $columnName): void
    {
        $rules = [
            'columnName' => 'required|string|min:1|max:63|regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'
        ];

        $this->validate(['columnName' => $columnName], $rules, 'column name');
    }

    /**
     * Validate data type
     * 
     * @param string $dataType Data type
     * @throws ValidationException
     */
    private function validateDataType(string $dataType): void
    {
        $trimmedType = trim(strtoupper($dataType));

        // Extract base type (remove length/precision specifications)
        $baseType = preg_replace('/\([^)]*\)/', '', $trimmedType);

        // Check if it's a valid PostgreSQL data type
        $isValid = false;
        foreach (self::VALID_DATA_TYPES as $validType) {
            if (strpos($baseType, $validType) === 0) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new ValidationException("'{$dataType}' is not a valid PostgreSQL data type");
        }
    }

    /**
     * Validate column definition
     * 
     * @param array $column Column definition
     * @param int $index Column index for error messages
     * @throws ValidationException
     */
    private function validateColumnDefinition(array $column, int $index): void
    {
        if (!isset($column['name'])) {
            throw new ValidationException("Column {$index}: name is required");
        }

        if (!isset($column['type'])) {
            throw new ValidationException("Column {$index}: type is required");
        }

        $this->validateColumnName($column['name']);
        $this->validateDataType($column['type']);
    }

    /**
     * Get table columns information
     * 
     * @param string $tableName Table name
     * @return array Array of column information
     */
    private function getTableColumns(string $tableName): array
    {
        $query = QueryBuilder::buildGetTableColumnsQuery();
        $stmt = $this->db->execute($query, ['table_name' => $tableName]);
        return $stmt->fetchAll();
    }

    /**
     * Get table row count
     * 
     * @param string $tableName Table name
     * @return int Number of rows
     */
    private function getTableRowCount(string $tableName): int
    {
        $query = QueryBuilder::buildGetTableCountQuery($tableName);
        $stmt = $this->db->execute($query);
        return $stmt->fetch()['total'];
    }

    /**
     * Get number of columns in table
     * 
     * @param string $tableName Table name
     * @return int Number of columns
     */
    private function getColumnCount(string $tableName): int
    {
        $query = str_replace('AND column_name = :column_name', '', QueryBuilder::buildColumnExistsQuery());
        $stmt = $this->db->execute($query, ['table_name' => $tableName]);
        return $stmt->fetch()['count'];
    }
}
