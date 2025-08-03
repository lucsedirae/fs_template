<?php
/**
 * Bad request exception
 * 
 * For debugging bad request events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * BadRequestException
 * 
 * Thrown when request format or content is invalid
 */
class BadRequestException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'bad_request_error';
    }

    public function __construct(
        string $message = "Bad request",
        array $context = [],
        int $code = 400
    ) {
        parent::__construct($message, $code, null, $context, 400);
    }
}
