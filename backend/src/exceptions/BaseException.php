<?php
/**
 * BaseException Class
 * 
 * Base exception class that provides consistent error handling across the application.
 * All custom exceptions should extend this class.
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

use Exception;
use Throwable;

abstract class BaseException extends Exception
{
    /**
     * Error context data
     * 
     * @var array
     */
    protected $context;

    /**
     * HTTP status code
     * 
     * @var int
     */
    protected $httpStatusCode;

    /**
     * Error type for categorization
     * 
     * @var string
     */
    protected $errorType;

    /**
     * BaseException constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param array $context Additional context data
     * @param int $httpStatusCode HTTP status code
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        Throwable $previous = null,
        array $context = [],
        int $httpStatusCode = 500
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->httpStatusCode = $httpStatusCode;
        $this->errorType = $this->getErrorType();
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
     * Get HTTP status code
     * 
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get error type
     * 
     * @return string
     */
    abstract protected function getErrorType(): string;

    /**
     * Convert exception to array format for API responses
     * 
     * @param bool $includeTrace Whether to include stack trace
     * @return array
     */
    public function toArray(bool $includeTrace = false): array
    {
        $result = [
            'status' => 'error',
            'error_type' => $this->errorType,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (!empty($this->context)) {
            $result['context'] = $this->context;
        }

        if ($includeTrace && (php_sapi_name() === 'cli' || $_ENV['APP_DEBUG'] ?? false)) {
            $result['trace'] = $this->getTraceAsString();
        }

        return $result;
    }

    /**
     * Log the exception with context
     * 
     * @param string $level Log level (error, warning, info)
     * @return void
     */
    public function logError(string $level = 'error'): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s (Code: %d)",
            date('Y-m-d H:i:s'),
            static::class,
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

    /**
     * Create a standardized error response
     * 
     * @param bool $includeTrace Whether to include stack trace
     * @return array
     */
    public function getErrorResponse(bool $includeTrace = false): array
    {
        return $this->toArray($includeTrace);
    }
}
