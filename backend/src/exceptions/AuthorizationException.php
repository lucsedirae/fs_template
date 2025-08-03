<?php
/**
 * Authorization exception
 * 
 * For debugging auth events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * AuthorizationException
 * 
 * Thrown when authorization fails
 */
class AuthorizationException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'authorization_error';
    }

    public function __construct(
        string $message = "Access denied",
        array $context = [],
        int $code = 403
    ) {
        parent::__construct($message, $code, null, $context, 403);
    }
}
