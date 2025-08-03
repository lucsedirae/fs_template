<?php
/**
 * QueryBuilder Class
 * 
 * Provides methods for building SQL queries in a safe and reusable way.
 * Focuses on PostgreSQL-specific query construction.
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

class QueryBuilder
{
    /**
     * Build CREATE TABLE SQL statement
     * 
     * @param string $tableName Table name
     * @param array $columns Array of column definitions
     * @return string SQL statement
     * @throws DatabaseException If invalid parameters
     */
    public static function buildCreateTableQuery(string $tableName, array $columns): string
    {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        $columnDefinitions = [];
        $primaryKeys = [];

        foreach ($columns as $column) {
            $columnDef = self::buildColumnDefinition($column);
            $columnDefinitions[] = $columnDef;

            // Collect primary key columns
            if (isset($column['isPrimary']) && $column['isPrimary']) {
                $primaryKeys[] = DatabaseValidator::sanitizeIdentifier($column['name']);
            }
        }

        $sql = "CREATE TABLE {$sanitizedTableName} (\n";
        $sql .= "    " . implode(",\n    ", $columnDefinitions);

        // Add primary key constraint if any primary keys exist
        if (!empty($primaryKeys)) {
            $sql .= ",\n    PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
        }

        $sql .= "\n)";

        return $sql;
    }

    /**
     * Build DROP TABLE SQL statement
     * 
     * @param string $tableName Table name
     * @param bool $cascade Whether to cascade the drop
     * @return string SQL statement
     */
    public static function buildDropTableQuery(string $tableName, bool $cascade = false): string
    {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        $sql = "DROP TABLE {$sanitizedTableName}";
        
        if ($cascade) {
            $sql .= " CASCADE";
        }

        return $sql;
    }

    /**
     * Build ADD COLUMN SQL statement
     * 
     * @param string $tableName Table name
     * @param array $column Column definition
     * @return string SQL statement
     */
    public static function buildAddColumnQuery(string $tableName, array $column): string
    {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        $columnDefinition = self::buildColumnDefinition($column);

        return "ALTER TABLE {$sanitizedTableName} ADD COLUMN {$columnDefinition}";
    }

    /**
     * Build DROP COLUMN SQL statement
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param bool $cascade Whether to cascade the drop
     * @return string SQL statement
     */
    public static function buildDropColumnQuery(string $tableName, string $columnName, bool $cascade = false): string
    {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        $sanitizedColumnName = DatabaseValidator::sanitizeIdentifier($columnName);
        
        $sql = "ALTER TABLE {$sanitizedTableName} DROP COLUMN {$sanitizedColumnName}";
        
        if ($cascade) {
            $sql .= " CASCADE";
        }

        return $sql;
    }

    /**
     * Build query to check if table exists
     * 
     * @return string SQL statement
     */
    public static function buildTableExistsQuery(): string
    {
        return "
            SELECT COUNT(*) as count
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = :table_name
        ";
    }

    /**
     * Build query to check if column exists
     * 
     * @return string SQL statement
     */
    public static function buildColumnExistsQuery(): string
    {
        return "
            SELECT COUNT(*) as count
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = :table_name 
            AND column_name = :column_name
        ";
    }

    /**
     * Build query to get all tables
     * 
     * @return string SQL statement
     */
    public static function buildGetTablesQuery(): string
    {
        return "
            SELECT 
                table_name,
                table_type
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ";
    }

    /**
     * Build query to get table data with pagination
     * 
     * @param string $tableName Table name
     * @return string SQL statement
     */
    public static function buildGetTableDataQuery(string $tableName): string
    {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        
        return "
            SELECT * FROM {$sanitizedTableName} 
            ORDER BY 1
            LIMIT :limit OFFSET :offset
        ";
    }

    /**
     * Build query to get table row count
     * 
     * @param string $tableName Table name
     * @return string SQL statement
     */
    public static function buildGetTableCountQuery(string $tableName): string
    {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        
        return "SELECT COUNT(*) as total FROM {$sanitizedTableName}";
    }

    /**
     * Build query to get table schema information
     * 
     * @return string SQL statement
     */
    public static function buildGetTableSchemaQuery(): string
    {
        return "
            SELECT 
                c.column_name,
                c.data_type,
                c.character_maximum_length,
                c.numeric_precision,
                c.numeric_scale,
                c.is_nullable,
                c.column_default,
                c.ordinal_position,
                CASE 
                    WHEN pk.column_name IS NOT NULL THEN true 
                    ELSE false 
                END as is_primary_key
            FROM information_schema.columns c
            LEFT JOIN (
                SELECT ku.column_name
                FROM information_schema.table_constraints tc
                INNER JOIN information_schema.key_column_usage ku
                    ON tc.constraint_name = ku.constraint_name
                    AND tc.table_schema = ku.table_schema
                WHERE tc.constraint_type = 'PRIMARY KEY'
                    AND tc.table_schema = 'public'
                    AND tc.table_name = :table_name
            ) pk ON c.column_name = pk.column_name
            WHERE c.table_schema = 'public' 
                AND c.table_name = :table_name
            ORDER BY c.ordinal_position
        ";
    }

    /**
     * Build query to get table columns information
     * 
     * @return string SQL statement
     */
    public static function buildGetTableColumnsQuery(): string
    {
        return "
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
    }

    /**
     * Build column definition string
     * 
     * @param array $column Column definition
     * @return string Column definition SQL
     */
    private static function buildColumnDefinition(array $column): string
    {
        $sanitizedColumnName = DatabaseValidator::sanitizeIdentifier($column['name']);
        $columnDef = "{$sanitizedColumnName} {$column['type']}";

        // Add NOT NULL constraint
        if (isset($column['nullable']) && !$column['nullable']) {
            $columnDef .= ' NOT NULL';
        }

        // Note: PRIMARY KEY constraint is handled separately in CREATE TABLE
        // to support composite primary keys

        return $columnDef;
    }

    /**
     * Build SELECT query with optional WHERE conditions
     * 
     * @param string $tableName Table name
     * @param array $columns Columns to select (empty for *)
     * @param array $where WHERE conditions
     * @param array $orderBy ORDER BY conditions
     * @param int|null $limit LIMIT value
     * @param int|null $offset OFFSET value
     * @return string SQL statement
     */
    public static function buildSelectQuery(
        string $tableName,
        array $columns = [],
        array $where = [],
        array $orderBy = [],
        ?int $limit = null,
        ?int $offset = null
    ): string {
        $sanitizedTableName = DatabaseValidator::sanitizeIdentifier($tableName);
        
        // Build SELECT clause
        if (empty($columns)) {
            $selectClause = "SELECT *";
        } else {
            $sanitizedColumns = array_map([DatabaseValidator::class, 'sanitizeIdentifier'], $columns);
            $selectClause = "SELECT " . implode(', ', $sanitizedColumns);
        }

        $sql = "{$selectClause} FROM {$sanitizedTableName}";

        // Add WHERE clause
        if (!empty($where)) {
            $whereConditions = [];
            foreach ($where as $column => $value) {
                $sanitizedColumn = DatabaseValidator::sanitizeIdentifier($column);
                $whereConditions[] = "{$sanitizedColumn} = :{$column}";
            }
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        // Add ORDER BY clause
        if (!empty($orderBy)) {
            $orderConditions = [];
            foreach ($orderBy as $column => $direction) {
                $sanitizedColumn = DatabaseValidator::sanitizeIdentifier($column);
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $orderConditions[] = "{$sanitizedColumn} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderConditions);
        }

        // Add LIMIT and OFFSET
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        return $sql;
    }
}
