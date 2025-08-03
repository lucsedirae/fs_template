<?php
/**
 * Base Service Class
 * 
 * Provides common functionality for all service classes including
 * validation, error handling, and business logic coordination.
 * 
 * @package    Backend\Services
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Services;

use Backend\Exceptions\ValidationException;
use Backend\Exceptions\DatabaseException;
use Backend\Database\Connection;

abstract class BaseService
{
    /**
     * Database connection instance
     * 
     * @var Connection
     */
    protected $db;

    /**
     * Service logger
     * 
     * @var array
     */
    protected $logs = [];

    /**
     * BaseService constructor
     * 
     * @param Connection|null $db Database connection
     */
    public function __construct(?Connection $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
    }

    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param string $context Context for error messages
     * @throws ValidationException
     * @return void
     */
    protected function validate(array $data, array $rules, string $context = 'data'): void
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->validateField($field, $value, $rule, $data);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(
                "Validation failed for {$context}",
                ['validation_errors' => $errors]
            );
        }
    }

    /**
     * Validate a single field against a rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @param array $data Full data array for context
     * @return string|null Error message or null if valid
     */
    private function validateField(string $field, $value, string $rule, array $data): ?string
    {
        // Parse rule and parameters
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        switch ($ruleName) {
            case 'required':
                if (is_null($value) || (is_string($value) && trim($value) === '')) {
                    return "{$field} is required";
                }
                break;

            case 'string':
                if (!is_null($value) && !is_string($value)) {
                    return "{$field} must be a string";
                }
                break;

            case 'integer':
                if (!is_null($value) && !is_int($value) && !ctype_digit((string) $value)) {
                    return "{$field} must be an integer";
                }
                break;

            case 'boolean':
                if (!is_null($value) && !is_bool($value)) {
                    return "{$field} must be a boolean";
                }
                break;

            case 'array':
                if (!is_null($value) && !is_array($value)) {
                    return "{$field} must be an array";
                }
                break;

            case 'min':
                $minLength = (int) ($params[0] ?? 0);
                if (is_string($value) && strlen($value) < $minLength) {
                    return "{$field} must be at least {$minLength} characters";
                }
                if (is_int($value) && $value < $minLength) {
                    return "{$field} must be at least {$minLength}";
                }
                break;

            case 'max':
                $maxLength = (int) ($params[0] ?? 0);
                if (is_string($value) && strlen($value) > $maxLength) {
                    return "{$field} must not exceed {$maxLength} characters";
                }
                if (is_int($value) && $value > $maxLength) {
                    return "{$field} must not exceed {$maxLength}";
                }
                break;

            case 'in':
                if (!is_null($value) && !in_array($value, $params, true)) {
                    return "{$field} must be one of: " . implode(', ', $params);
                }
                break;

            case 'unique':
                // This would need database access to check uniqueness
                // Implementation depends on specific requirements
                break;

            case 'regex':
                $pattern = $params[0] ?? '';
                if (!is_null($value) && !preg_match($pattern, $value)) {
                    return "{$field} format is invalid";
                }
                break;

            default:
                // Custom validation rules can be added here
                break;
        }

        return null;
    }

    /**
     * Sanitize input data
     * 
     * @param array $data Data to sanitize
     * @param array $fields Fields to sanitize with their types
     * @return array Sanitized data
     */
    protected function sanitize(array $data, array $fields): array
    {
        $sanitized = [];

        foreach ($fields as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            switch ($type) {
                case 'string':
                    $sanitized[$field] = is_string($value) ? trim($value) : (string) $value;
                    break;

                case 'int':
                case 'integer':
                    $sanitized[$field] = (int) $value;
                    break;

                case 'float':
                    $sanitized[$field] = (float) $value;
                    break;

                case 'bool':
                case 'boolean':
                    $sanitized[$field] = (bool) $value;
                    break;

                case 'array':
                    $sanitized[$field] = is_array($value) ? $value : [$value];
                    break;

                case 'email':
                    $sanitized[$field] = filter_var($value, FILTER_SANITIZE_EMAIL);
                    break;

                case 'url':
                    $sanitized[$field] = filter_var($value, FILTER_SANITIZE_URL);
                    break;

                default:
                    $sanitized[$field] = $value;
                    break;
            }
        }

        return $sanitized;
    }

    /**
     * Execute database transaction
     * 
     * @param callable $callback Transaction callback
     * @return mixed Transaction result
     * @throws DatabaseException
     */
    protected function transaction(callable $callback)
    {
        try {
            $this->db->beginTransaction();
            $result = $callback($this->db);
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->db->rollback();

            if ($e instanceof DatabaseException) {
                throw $e;
            }

            throw new DatabaseException(
                'Transaction failed: ' . $e->getMessage(),
                ['original_error' => $e->getMessage()]
            );
        }
    }

    /**
     * Log service activity
     * 
     * @param string $action Action description
     * @param array $context Additional context
     * @param string $level Log level
     * @return void
     */
    protected function log(string $action, array $context = [], string $level = 'info'): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => static::class,
            'action' => $action,
            'level' => $level,
            'context' => $context
        ];

        $this->logs[] = $logEntry;
        error_log("[{$level}] Service: " . json_encode($logEntry));
    }

    /**
     * Get service logs
     * 
     * @return array Array of log entries
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Clear service logs
     * 
     * @return void
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Format timestamp for database storage
     * 
     * @param string|null $timestamp Timestamp string or null for current time
     * @return string Formatted timestamp
     */
    protected function formatTimestamp(?string $timestamp = null): string
    {
        return date('Y-m-d H:i:s', $timestamp ? strtotime($timestamp) : time());
    }

    /**
     * Generate unique identifier
     * 
     * @param string $prefix Optional prefix
     * @return string Unique identifier
     */
    protected function generateId(string $prefix = ''): string
    {
        $unique = uniqid('', true);
        return $prefix ? $prefix . '_' . $unique : $unique;
    }

    /**
     * Check if resource exists by ID
     * 
     * @param string $table Table name
     * @param int|string $id Resource ID
     * @param string $idColumn ID column name
     * @return bool True if exists
     */
    protected function exists(string $table, $id, string $idColumn = 'id'): bool
    {
        try {
            $query = "SELECT 1 FROM \"{$table}\" WHERE \"{$idColumn}\" = :id LIMIT 1";
            $stmt = $this->db->execute($query, ['id' => $id]);
            return $stmt->fetchColumn() !== false;
        } catch (\Throwable $e) {
            $this->log('exists_check_failed', [
                'table' => $table,
                'id' => $id,
                'error' => $e->getMessage()
            ], 'error');
            return false;
        }
    }

    /**
     * Get database connection for direct access
     * 
     * @return Connection Database connection instance
     */
    protected function getConnection(): Connection
    {
        return $this->db;
    }
}
