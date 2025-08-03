<?php
/**
 * Config exception
 * 
 * For debugging config events
 * 
 * @package    Backend\Exceptions
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Exceptions;

/**
 * ConfigurationException
 * 
 * Thrown when configuration issues are encountered
 */
class ConfigurationException extends BaseException
{
    protected function getErrorType(): string
    {
        return 'configuration_error';
    }

    public function __construct(
        string $message = "Configuration error",
        array $context = [],
        int $code = 500
    ) {
        parent::__construct($message, $code, null, $context, 500);
    }
}
