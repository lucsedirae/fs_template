<?php
/**
 * Not found exception
 * 
 * For debugging resource not found events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * NotFoundException
 * 
 * Thrown when requested resources are not found
 */
class NotFoundException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'not_found_error';
    }

    public function __construct(
        string $message = "Resource not found",
        array $context = [],
        int $code = 404
    ) {
        parent::__construct($message, $code, null, $context, 404);
    }
}
