<?php
namespace JiFramework\Config;

class Config
{
    /**
     * Initialize Session
     *
     * Starts a new session if one hasn't already been started.
     */
    public static function initSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Application Mode
     *
     * Defines the operational mode of the application.
     * - 'development' for detailed error reporting.
     * - 'production' for minimal error reporting.
     */
    const APP_MODE = 'development';

    /**
     * Administrator Email
     *
     * Specifies the email address of the system administrator.
     */
    const ADMIN_EMAIL = 'jahurulce@gmail.com';

    /**
     * Default Time Zone
     *
     * Sets the default time zone used by all date/time functions.
     */
    const TIMEZONE = 'Asia/Kolkata';

    /**
     * Primary Database Configuration
     */
    public static $primaryDatabase = [
        'host'       => 'localhost',
        'database'   => 'backoffice_jahurul_in',
        'username'   => 'root',
        'password'   => '',
        'driver'     => 'mysql',
        'charset'    => 'utf8mb4',
        'collation'  => 'utf8mb4_unicode_ci',
        'options'    => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ],
    ];

    /**
     * Additional Database Configurations
     *
     * Allows specifying configurations for connecting to multiple databases.
     */
    public static $databases = [
        'db1' => [
            'host'       => 'localhost',
            'database'   => '',
            'username'   => '',
            'password'   => '',
            'driver'     => 'mysql',
            'charset'    => 'utf8mb4',
            'collation'  => 'utf8mb4_unicode_ci',
            'options'    => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        ],
    ];

    /**
     * Session and Cookie Names
     */
    const USER_SESSION_KEY    = '_user_session_jmanager_v3';
    const ADMIN_SESSION_KEY   = '_admin_session_jmanager_v3';
    const ADMIN_REMEMBER_COOKIE  = '_admin_remember_me_jmanager_v3';
    const USER_REMEMBER_COOKIE   = '_user_remember_me_jmanager_v3';

    /**
     * CSRF Token Configuration
     */
    const CSRF_TOKEN_KEY      = '_csrf_token_jmanager_v3';
    const FLASH_MESSAGE_KEY   = '_flash_messages_jmanager_v3';
    const CSRF_TOKEN_EXPIRY   = 3600 * 12; // 12 hours
    const CSRF_TOKEN_LIMIT    = 100;
    const CSRF_TOKEN_LENGTH   = 32;

    /**
     * Project base path configuration
     */
    // Base path of the project
    const BASE_PATH = __DIR__ . '/../../../';
    // Storage path 
    const STORAGE_PATH = self::BASE_PATH . 'src/Storage/';

    /**
     * File Manager Configuration
     */
    const UPLOAD_DIRECTORY      = self::STORAGE_PATH . 'Uploads/';              // Directory paths
    const ALLOWED_IMAGE_TYPES   = ['image/jpeg', 'image/png', 'image/gif'];     // Allowed image types
    const MAX_FILE_SIZE         = 5 * 1024 * 1024;                              // Maximum file sizes (in bytes) | 5 MB
    const IMAGE_MAX_DIMENSION   = 800;                                          // Image settings: Max width or height in pixels

    /**
     * Cache Configuration
     */
    const CACHE_DRIVER          = 'file';                                                           // Options: 'file', 'sqlite'
    const CACHE_PATH            = self::STORAGE_PATH . 'Cache/FileCache/';                          // Path to cache directory
    const CACHE_DATABASE_PATH   = self::STORAGE_PATH . 'Cache/DatabaseCache/ji_sqlite_cache.db';    // Path to SQLite database


    /**
     * Rate Limiting Configuration
     */
    const RATE_LIMIT_ENABLED        = false;                                             // Enable or disable rate limiting
    const RATE_LIMIT_REQUESTS       = 500;                                              // Number of allowed requests
    const RATE_LIMIT_TIME_WINDOW    = 60;                                               // Time window in seconds | 60 = 1 minute
    const RATE_LIMIT_BAN_ENABLED    = true;                                             // Enable or disable banning
    const RATE_LIMIT_BAN_DURATION   = 3600;                                             // Ban duration in seconds | 3600 = 1 hour
    const RATE_LIMIT_DATABASE_PATH  = self::STORAGE_PATH . 'RateLimit/rate_limit.db';   // Path to SQLite database

    // Add this static property for the RateLimiter tests
    public static $RATE_LIMIT_DATABASE_PATH = self::STORAGE_PATH . 'RateLimit/rate_limit.db';

    /**
     * Multi-Language Configuration
     */
    const MULTI_LANG                = false;                        // Enable or disable multi-language support
    const MULTI_LANG_METHOD         = 'url';                        // 'url' or 'cookie'
    const MULTI_LANG_DEFAULT_LANG   = 'en';                         // Default language code
    const MULTI_LANG_KEY            = 'lang';                       // The parameter name in URL or cookie
    const MULTI_LANG_DIR            = self::BASE_PATH . 'lang/';    // Path to the lang directory

    /**
     * Logging Configuration
     */
    const LOG_ENABLED       = true;                         // Enable or disable logging
    const LOG_LEVEL         = 'DEBUG';                      // Default log level
    const LOG_FILE_PATH     = self::STORAGE_PATH . 'Logs/'; // Path to log directory
    const LOG_FILE_NAME     = 'app.log';                    // Default log file name
    const LOG_MAX_FILE_SIZE = 5242880;                      // Max log file size in bytes (e.g., 5MB)
    const LOG_MAX_FILES     = 20;                           // Max number of log files to keep

    /**
     * Access Control Configuration
     */
    const IP_BLOCKING_ENABLED = false; // Enable or disable IP blocking
    const IP_BLOCK_LIST_PATH = self::BASE_PATH . 'src/Config/ip_block_list.json'; // Path to IP block list JSON file

    const COUNTRY_BLOCKING_ENABLED = false; // Enable or disable country blocking
    const COUNTRY_BLOCK_LIST_PATH = self::BASE_PATH . 'src/Config/country_block_list.json'; // Path to country block list JSON file
    const ALLOW_VPN_PROXY = true; // Allow or disallow VPN/proxy connections

    // ProxyCheck API Configuration [https://proxycheck.io/]
    const PROXYCHECK_API_KEY = '707vg9-1l93vt-22g6ax-w04169'; // Replace with your actual API key
    const PROXYCHECK_API_URL = 'https://proxycheck.io/v2/{ip}';

    /**
     * Initialize Configuration
     *
     * Call this method at the start of your application to set up configurations.
     */
    public static function initialize()
    {
        // Set the default time zone
        date_default_timezone_set(self::TIMEZONE);

        // Initialize the session
        self::initSession();

        // Set error reporting based on application mode
        if (self::APP_MODE === 'development') {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }
    }

    /**
     * Get Configuration Value
     *
     * Utility method to get configuration values using dot notation.
     *
     * @param string $key The configuration key, e.g., 'database.host'
     * @param mixed $default Default value if the key does not exist
     * @return mixed The configuration value or default
     */
    public static function get($key, $default = null)
    {
        $segments = explode('.', $key);
        $config = new \ReflectionClass(__CLASS__);

        $property = array_shift($segments);
        if ($config->hasProperty($property)) {
            $value = $config->getStaticPropertyValue($property);
            foreach ($segments as $segment) {
                if (is_array($value) && isset($value[$segment])) {
                    $value = $value[$segment];
                } else {
                    return $default;
                }
            }
            return $value;
        } elseif ($config->hasConstant($property)) {
            return $config->getConstant($property);
        }

        return $default;
    }
}


