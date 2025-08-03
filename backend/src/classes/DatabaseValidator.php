<?php
/**
 * DatabaseValidator Class
 * 
 * Centralizes validation logic for database operations.
 * Provides consistent validation rules and error messages.
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

class DatabaseValidator
{
    /**
     * Maximum length for PostgreSQL identifiers
     */
    const MAX_IDENTIFIER_LENGTH = 63;

    /**
     * Valid PostgreSQL data types
     */
    const VALID_DATA_TYPES = [
        'SERIAL', 'INTEGER', 'BIGINT', 'SMALLINT',
        'VARCHAR', 'TEXT', 'CHAR',
        'BOOLEAN', 'DATE', 'TIMESTAMP', 'TIME',
        'DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION',
        'JSON', 'JSONB', 'UUID'
    ];

    /**
     * Validate table name
     * 
     * @param string $tableName Table name to validate
     * @return array Validation result
     */
    public static function validateTableName(string $tableName): array
    {
        $errors = [];

        // Check if empty
        if (empty(trim($tableName))) {
            $errors[] = 'Table name cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }

        $trimmedName = trim($tableName);

        // Check length
        if (strlen($trimmedName) > self::MAX_IDENTIFIER_LENGTH) {
            $errors[] = sprintf(
                'Table name cannot exceed %d characters',
                self::MAX_IDENTIFIER_LENGTH
            );
        }

        // Check format (must start with letter or underscore, then letters, numbers, underscores)
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $trimmedName)) {
            $errors[] = 'Table name must start with a letter or underscore and contain only letters, numbers, and underscores';
        }

        // Check for PostgreSQL reserved words
        if (self::isReservedWord($trimmedName)) {
            $errors[] = sprintf('"%s" is a reserved PostgreSQL keyword', $trimmedName);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate column name
     * 
     * @param string $columnName Column name to validate
     * @return array Validation result
     */
    public static function validateColumnName(string $columnName): array
    {
        $errors = [];

        // Check if empty
        if (empty(trim($columnName))) {
            $errors[] = 'Column name cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }

        $trimmedName = trim($columnName);

        // Check length
        if (strlen($trimmedName) > self::MAX_IDENTIFIER_LENGTH) {
            $errors[] = sprintf(
                'Column name cannot exceed %d characters',
                self::MAX_IDENTIFIER_LENGTH
            );
        }

        // Check format
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $trimmedName)) {
            $errors[] = 'Column name must start with a letter or underscore and contain only letters, numbers, and underscores';
        }

        // Check for PostgreSQL reserved words
        if (self::isReservedWord($trimmedName)) {
            $errors[] = sprintf('"%s" is a reserved PostgreSQL keyword', $trimmedName);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate column definition
     * 
     * @param array $column Column definition to validate
     * @return array Validation result
     */
    public static function validateColumnDefinition(array $column): array
    {
        $errors = [];

        // Validate column name
        if (!isset($column['name'])) {
            $errors[] = 'Column name is required';
        } else {
            $nameValidation = self::validateColumnName($column['name']);
            if (!$nameValidation['valid']) {
                $errors = array_merge($errors, $nameValidation['errors']);
            }
        }

        // Validate column type
        if (!isset($column['type'])) {
            $errors[] = 'Column type is required';
        } else {
            $typeValidation = self::validateDataType($column['type']);
            if (!$typeValidation['valid']) {
                $errors = array_merge($errors, $typeValidation['errors']);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate data type
     * 
     * @param string $dataType Data type to validate
     * @return array Validation result
     */
    public static function validateDataType(string $dataType): array
    {
        $errors = [];

        if (empty(trim($dataType))) {
            $errors[] = 'Data type cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }

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
            $errors[] = sprintf('"%s" is not a valid PostgreSQL data type', $dataType);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate table columns array
     * 
     * @param array $columns Array of column definitions
     * @return array Validation result
     */
    public static function validateTableColumns(array $columns): array
    {
        $errors = [];

        if (empty($columns)) {
            $errors[] = 'At least one column is required';
            return ['valid' => false, 'errors' => $errors];
        }

        $columnNames = [];
        $primaryKeyCount = 0;

        foreach ($columns as $index => $column) {
            $columnValidation = self::validateColumnDefinition($column);
            
            if (!$columnValidation['valid']) {
                foreach ($columnValidation['errors'] as $error) {
                    $errors[] = sprintf('Column %d: %s', $index + 1, $error);
                }
            }

            // Check for duplicate column names
            if (isset($column['name'])) {
                $columnName = strtolower(trim($column['name']));
                if (in_array($columnName, $columnNames)) {
                    $errors[] = sprintf('Duplicate column name: "%s"', $column['name']);
                } else {
                    $columnNames[] = $columnName;
                }
            }

            // Count primary keys
            if (isset($column['isPrimary']) && $column['isPrimary']) {
                $primaryKeyCount++;
            }
        }

        // Note: Multiple primary key columns are allowed (composite primary key)
        // So we don't need to validate primaryKeyCount > 1

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize identifier (table/column name) for SQL queries
     * 
     * @param string $identifier Identifier to sanitize
     * @return string Sanitized identifier
     */
    public static function sanitizeIdentifier(string $identifier): string
    {
        // PostgreSQL identifier quoting
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Check if a word is a PostgreSQL reserved keyword
     * 
     * @param string $word Word to check
     * @return bool True if reserved word
     */
    private static function isReservedWord(string $word): bool
    {
        $reservedWords = [
            'ALL', 'AND', 'ANY', 'AS', 'ASC', 'BETWEEN', 'BY', 'CASE', 'CAST',
            'CHECK', 'COLUMN', 'CONSTRAINT', 'CREATE', 'CROSS', 'CURRENT_DATE',
            'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'DEFAULT',
            'DELETE', 'DESC', 'DISTINCT', 'DROP', 'ELSE', 'END', 'EXCEPT',
            'EXISTS', 'FALSE', 'FETCH', 'FOR', 'FOREIGN', 'FROM', 'FULL',
            'GROUP', 'HAVING', 'IN', 'INNER', 'INSERT', 'INTERSECT', 'INTO',
            'IS', 'JOIN', 'LEFT', 'LIKE', 'LIMIT', 'NATURAL', 'NOT', 'NULL',
            'ON', 'OR', 'ORDER', 'OUTER', 'PRIMARY', 'REFERENCES', 'RIGHT',
            'SELECT', 'SESSION_USER', 'SOME', 'TABLE', 'THEN', 'TO', 'TRUE',
            'UNION', 'UNIQUE', 'UPDATE', 'USER', 'USING', 'VALUES', 'WHEN',
            'WHERE', 'WITH'
        ];

        return in_array(strtoupper($word), $reservedWords);
    }
}
