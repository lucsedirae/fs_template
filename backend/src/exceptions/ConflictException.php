<?php
/**
 * Conflict exception
 * 
 * For debugging conflict events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * ConflictException
 * 
 * Thrown when resource conflicts occur (e.g., duplicate names)
 */
class ConflictException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'conflict_error';
    }

    public function __construct(
        string $message = "Resource conflict",
        array $context = [],
        int $code = 409
    ) {
        parent::__construct($message, $code, null, $context, 409);
    }
}
