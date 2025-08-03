<?php
/**
 * Configuration Manager
 * 
 * Centralized configuration management for the application.
 * Handles environment variables, default values, and configuration validation.
 * 
 * @package    Backend\Config
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Config;

use Backend\Exceptions\ConfigurationException;

class Config
{
    /**
     * Configuration cache
     * 
     * @var array
     */
    private static $config = [];

    /**
     * Whether configuration has been loaded
     * 
     * @var bool
     */
    private static $loaded = false;

    /**
     * Default configuration values
     * 
     * @var array
     */
    private static $defaults = [
        'app' => [
            'name' => 'Backend API',
            'version' => '2.0.0',
            'env' => 'production',
            'debug' => false,
            'timezone' => 'UTC',
            'log_requests' => false,
            'maintenance_mode' => false
        ],
        'database' => [
            'host' => 'localhost',
            'port' => 5432,
            'name' => 'app',
            'username' => 'app',
            'password' => '',
            'charset' => 'utf8',
            'timeout' => 30,
            'max_connections' => 100
        ],
        'cors' => [
            'allowed_origins' => ['http://localhost:3000'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin', 'Cache-Control'],
            'allow_credentials' => false,
            'max_age' => 86400
        ],
        'api' => [
            'rate_limit_enabled' => false,
            'rate_limit_requests' => 100,
            'rate_limit_window' => 3600,
            'default_page_size' => 50,
            'max_page_size' => 1000,
            'timeout' => 30
        ],
        'security' => [
            'encryption_key' => '',
            'jwt_secret' => '',
            'password_min_length' => 8,
            'session_timeout' => 3600
        ],
        'logging' => [
            'level' => 'error',
            'file' => '/var/log/app.log',
            'max_size' => '10M',
            'rotate' => true
        ]
    ];

    /**
     * Load configuration from environment and defaults
     * 
     * @return void
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Start with defaults
        self::$config = self::$defaults;

        // Override with environment variables
        self::loadFromEnvironment();

        // Validate critical configuration
        self::validate();

        self::$loaded = true;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::getNestedValue(self::$config, $key, $default);
    }

    /**
     * Set configuration value
     * 
     * @param string $key Configuration key (dot notation supported)
     * @param mixed $value Value to set
     * @return void
     */
    public static function set(string $key, $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        self::setNestedValue(self::$config, $key, $value);
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if key exists
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::getNestedValue(self::$config, $key, '__NOT_FOUND__') !== '__NOT_FOUND__';
    }

    /**
     * Get all configuration
     * 
     * @return array Complete configuration array
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Get database configuration
     * 
     * @return array Database configuration
     */
    public static function database(): array
    {
        return self::get('database', []);
    }

    /**
     * Get CORS configuration
     * 
     * @return array CORS configuration
     */
    public static function cors(): array
    {
        return self::get('cors', []);
    }

    /**
     * Check if application is in debug mode
     * 
     * @return bool True if debug mode is enabled
     */
    public static function isDebug(): bool
    {
        return (bool) self::get('app.debug', false);
    }

    /**
     * Check if application is in maintenance mode
     * 
     * @return bool True if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool
    {
        return (bool) self::get('app.maintenance_mode', false);
    }

    /**
     * Get application environment
     * 
     * @return string Application environment (development, staging, production)
     */
    public static function environment(): string
    {
        return self::get('app.env', 'production');
    }

    /**
     * Check if environment is development
     * 
     * @return bool True if development environment
     */
    public static function isDevelopment(): bool
    {
        return self::environment() === 'development';
    }

    /**
     * Check if environment is production
     * 
     * @return bool True if production environment
     */
    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    /**
     * Load configuration from environment variables
     * 
     * @return void
     */
    private static function loadFromEnvironment(): void
    {
        $envMappings = [
            // App configuration
            'APP_NAME' => 'app.name',
            'APP_VERSION' => 'app.version',
            'APP_ENV' => 'app.env',
            'APP_DEBUG' => 'app.debug',
            'APP_TIMEZONE' => 'app.timezone',
            'LOG_REQUESTS' => 'app.log_requests',
            'MAINTENANCE_MODE' => 'app.maintenance_mode',

            // Database configuration
            'DB_HOST' => 'database.host',
            'DB_PORT' => 'database.port',
            'DB_NAME' => 'database.name',
            'DB_DATABASE' => 'database.name', // Alternative name
            'DB_USERNAME' => 'database.username',
            'DB_USER' => 'database.username', // Alternative name
            'DB_PASSWORD' => 'database.password',
            'DB_CHARSET' => 'database.charset',
            'DB_TIMEOUT' => 'database.timeout',
            'DB_MAX_CONNECTIONS' => 'database.max_connections',

            // CORS configuration
            'CORS_ALLOWED_ORIGINS' => 'cors.allowed_origins',
            'CORS_ALLOWED_METHODS' => 'cors.allowed_methods',
            'CORS_ALLOWED_HEADERS' => 'cors.allowed_headers',
            'CORS_ALLOW_CREDENTIALS' => 'cors.allow_credentials',
            'CORS_MAX_AGE' => 'cors.max_age',

            // API configuration
            'API_RATE_LIMIT_ENABLED' => 'api.rate_limit_enabled',
            'API_RATE_LIMIT_REQUESTS' => 'api.rate_limit_requests',
            'API_RATE_LIMIT_WINDOW' => 'api.rate_limit_window',
            'API_DEFAULT_PAGE_SIZE' => 'api.default_page_size',
            'API_MAX_PAGE_SIZE' => 'api.max_page_size',
            'API_TIMEOUT' => 'api.timeout',

            // Security configuration
            'ENCRYPTION_KEY' => 'security.encryption_key',
            'JWT_SECRET' => 'security.jwt_secret',
            'PASSWORD_MIN_LENGTH' => 'security.password_min_length',
            'SESSION_TIMEOUT' => 'security.session_timeout',

            // Logging configuration
            'LOG_LEVEL' => 'logging.level',
            'LOG_FILE' => 'logging.file',
            'LOG_MAX_SIZE' => 'logging.max_size',
            'LOG_ROTATE' => 'logging.rotate'
        ];

        foreach ($envMappings as $envKey => $configKey) {
            $value = self::getEnvValue($envKey);
            if ($value !== null) {
                self::setNestedValue(self::$config, $configKey, $value);
            }
        }
    }

    /**
     * Get environment variable value with type conversion
     * 
     * @param string $key Environment variable key
     * @return mixed Environment value or null if not set
     */
    private static function getEnvValue(string $key)
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        // Convert string values to appropriate types
        return self::convertValue($value);
    }

    /**
     * Convert string value to appropriate type
     * 
     * @param string $value String value from environment
     * @return mixed Converted value
     */
    private static function convertValue(string $value)
    {
        // Boolean conversion
        if (in_array(strtolower($value), ['true', '1', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', '0', 'no', 'off'])) {
            return false;
        }

        // Numeric conversion
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        // Array conversion (comma-separated)
        if (strpos($value, ',') !== false) {
            return array_map('trim', explode(',', $value));
        }

        return $value;
    }

    /**
     * Get nested configuration value using dot notation
     * 
     * @param array $config Configuration array
     * @param string $key Dot-notated key
     * @param mixed $default Default value
     * @return mixed Value or default
     */
    private static function getNestedValue(array $config, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set nested configuration value using dot notation
     * 
     * @param array &$config Configuration array reference
     * @param string $key Dot-notated key
     * @param mixed $value Value to set
     * @return void
     */
    private static function setNestedValue(array &$config, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$config;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Validate critical configuration
     * 
     * @throws ConfigurationException
     * @return void
     */
    private static function validate(): void
    {
        $required = [
            'database.host',
            'database.port',
            'database.name',
            'database.username'
        ];

        $missing = [];
        foreach ($required as $key) {
            if (!self::has($key) || self::get($key) === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new ConfigurationException(
                'Missing required configuration: ' . implode(', ', $missing)
            );
        }

        // Validate database port is numeric
        $dbPort = self::get('database.port');
        if (!is_numeric($dbPort) || $dbPort <= 0 || $dbPort > 65535) {
            throw new ConfigurationException('Invalid database port: ' . $dbPort);
        }

        // Validate timezone
        $timezone = self::get('app.timezone');
        if (!in_array($timezone, timezone_identifiers_list())) {
            throw new ConfigurationException('Invalid timezone: ' . $timezone);
        }
    }

    /**
     * Reset configuration (mainly for testing)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$config = [];
        self::$loaded = false;
    }

    /**
     * Get configuration as JSON string
     * 
     * @param bool $pretty Whether to pretty print
     * @return string JSON configuration
     */
    public static function toJson(bool $pretty = false): string
    {
        if (!self::$loaded) {
            self::load();
        }

        $flags = JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode(self::$config, $flags);
    }

    /**
     * Load configuration from JSON file
     * 
     * @param string $file JSON file path
     * @throws ConfigurationException
     * @return void
     */
    public static function loadFromFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new ConfigurationException("Configuration file not found: {$file}");
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw new ConfigurationException("Cannot read configuration file: {$file}");
        }

        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConfigurationException("Invalid JSON in configuration file: {$file}");
        }

        // Merge with existing configuration
        self::$config = array_merge_recursive(self::$config, $config);
    }
}
