<?php
/**
 * DatabaseException Class
 * 
 * Custom exception class for database-related errors.
 * Provides structured error handling for database operations.
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

class DatabaseException extends Exception
{
    /**
     * Error context data
     * 
     * @var array
     */
    private $context;

    /**
     * DatabaseException constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get error context
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convert exception to array format for API responses
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'status' => 'error',
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context
        ];
    }

    /**
     * Log the exception with context
     * 
     * @return void
     */
    public function logError(): void
    {
        $logMessage = sprintf(
            "[%s] DatabaseException: %s (Code: %d)",
            date('Y-m-d H:i:s'),
            $this->getMessage(),
            $this->getCode()
        );

        if (!empty($this->context)) {
            $logMessage .= " Context: " . json_encode($this->context);
        }

        if ($this->getPrevious()) {
            $logMessage .= " Previous: " . $this->getPrevious()->getMessage();
        }

        error_log($logMessage);
    }
}
