<?php
/**
 * Database exception
 * 
 * For debugging database related events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * DatabaseException
 * 
 * Thrown when database operations fail
 */
class DatabaseException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'database_error';
    }

    public function __construct(
        string $message = "Database operation failed",
        array $context = [],
        int $code = 500
    ) {
        parent::__construct($message, $code, null, $context, 500);
    }
}
