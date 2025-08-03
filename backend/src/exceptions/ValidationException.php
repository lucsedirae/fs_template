<?php
/**
 * Validation exception
 * 
 * For debugging data validation related events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * ValidationException
 * 
 * Thrown when validation fails for user input or data
 */
class ValidationException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'validation_error';
    }

    public function __construct(
        string $message = "Validation failed",
        array $context = [],
        int $code = 400
    ) {
        parent::__construct($message, $code, null, $context, 400);
    }
}
