<?php
namespace JiFramework\Config;

class Config
{
    // -------------------------------------------------------------------------
    // Internal state
    // -------------------------------------------------------------------------

    private static $initialized = false;

    /** Warnings collected during initialization (e.g. missing config file). */
    public static $warnings = [];

    // -------------------------------------------------------------------------
    // App
    // -------------------------------------------------------------------------

    public static $appMode       = 'development'; // 'development' | 'production'
    public static $adminEmail    = 'admin@example.com';
    public static $timezone      = 'UTC';
    public static $errorTemplate = null; // absolute path to custom error page template

    // -------------------------------------------------------------------------
    // Paths  (null = computed in initialize() from $basePath)
    // -------------------------------------------------------------------------

    public static $basePath    = null;
    public static $storagePath = null;

    // -------------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------------

    public static $primaryDatabase = [
        'host'       => 'localhost',
        'database'   => '',
        'username'   => 'root',
        'password'   => '',
        'driver'     => 'mysql',
        'charset'    => 'utf8mb4',
        'collation'  => 'utf8mb4_unicode_ci',
        'options'    => [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
    ];

    // Named additional connections — keyed by connection name
    public static $databases = [];

    // -------------------------------------------------------------------------
    // Session & Auth
    // -------------------------------------------------------------------------

    public static $userSessionKey      = '_ji_user_session';
    public static $adminSessionKey     = '_ji_admin_session';
    public static $adminRememberCookie = '_ji_admin_remember';
    public static $userRememberCookie  = '_ji_user_remember';

    // Auth table names — override in config/jiconfig.php if your DB uses different names
    public static $authAdminTable = 'admin';
    public static $authUserTable  = 'users';
    public static $authTokenTable = 'tokens';

    // -------------------------------------------------------------------------
    // CSRF & Flash
    // -------------------------------------------------------------------------

    public static $csrfTokenKey    = '_ji_csrf_token';
    public static $flashMessageKey = '_ji_flash_messages';
    public static $csrfTokenExpiry = 43200; // 12 hours
    public static $csrfTokenLimit  = 100;
    public static $csrfTokenLength = 32;

    // -------------------------------------------------------------------------
    // File uploads
    // -------------------------------------------------------------------------

    public static $uploadDirectory   = null; // default: storage/Uploads/
    public static $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    public static $maxFileSize       = 5242880; // 5 MB
    public static $imageMaxDimension = 800;     // px

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    public static $cacheDriver       = 'file'; // 'file' | 'sqlite'
    public static $cachePath         = null;   // default: storage/Cache/FileCache/
    public static $cacheDatabasePath = null;   // default: storage/Cache/DatabaseCache/ji_sqlite_cache.db

    // -------------------------------------------------------------------------
    // Rate limiting
    // -------------------------------------------------------------------------

    public static $rateLimitEnabled      = false;
    public static $rateLimitRequests     = 500;
    public static $rateLimitTimeWindow   = 60;   // seconds
    public static $rateLimitBanEnabled   = true;
    public static $rateLimitBanDuration  = 3600; // seconds
    public static $rateLimitDatabasePath = null; // default: storage/RateLimit/rate_limit.db

    // -------------------------------------------------------------------------
    // Router
    // -------------------------------------------------------------------------

    public static $routerEnabled  = false;
    public static $routerBasePath = ''; // e.g. '/myapp' for subdirectory installs

    // -------------------------------------------------------------------------
    // Multi-language
    // -------------------------------------------------------------------------

    public static $multiLang            = false;
    public static $multiLangMethod      = 'url'; // 'url' | 'cookie'
    public static $multiLangDefaultLang = 'en';
    public static $multiLangKey         = 'lang';
    public static $multiLangDir         = null; // default: basePath/lang/

    // -------------------------------------------------------------------------
    // Logging
    // -------------------------------------------------------------------------

    public static $logEnabled     = true;
    public static $logLevel       = 'DEBUG';
    public static $logFilePath    = null;    // default: storage/Logs/
    public static $logFileName    = 'app.log';
    public static $logMaxFileSize = 5242880; // 5 MB
    public static $logMaxFiles    = 20;

    // -------------------------------------------------------------------------
    // Access control
    // -------------------------------------------------------------------------

    public static $ipBlockingEnabled      = false;
    public static $ipBlockListPath        = null; // default: storage/AccessControl/ip_block_list.json
    public static $countryBlockingEnabled = false;
    public static $countryBlockListPath   = null; // default: storage/AccessControl/country_block_list.json
    public static $allowVpnProxy          = true;
    public static $proxycheckApiKey       = '';
    public static $proxycheckApiUrl       = 'https://proxycheck.io/v2/{ip}';

    // -------------------------------------------------------------------------
    // Trusted proxies
    // -------------------------------------------------------------------------

    // List of trusted reverse proxy IPs (e.g. load balancer, Nginx, Cloudflare).
    // When empty (default), REMOTE_ADDR is always used as the authoritative client IP.
    // When set, getClientIp() reads X-Forwarded-For only if REMOTE_ADDR is in this list.
    public static $trustedProxies = [];

    // -------------------------------------------------------------------------
    // Development / debugging
    // -------------------------------------------------------------------------

    // Override the detected client IP — for testing IP-based features on localhost.
    // Only honoured when app_mode = 'development'. Silently ignored in production.
    public static $debugIp = null;

    // =========================================================================
    // Bootstrap
    // =========================================================================

    /**
     * Initialize the framework configuration.
     *
     * Safe to call multiple times — only executes once per request.
     */
    public static function initialize()
    {
        if (self::$initialized) {
            return;
        }

        // 1. Detect project root (walks up from Config.php until jiconfig.php found)
        self::$basePath = self::detectBasePath();

        // 2. Load user config and merge over defaults
        self::loadUserConfig();

        // 3. Resolve derived paths (only if not explicitly set in jiconfig.php)
        self::setDerivedPaths();

        // 4. Apply runtime settings
        date_default_timezone_set(self::$timezone);
        self::initSession();

        if (self::$appMode === 'development') {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }

        // Surface any warnings collected during initialization
        foreach (self::$warnings as $warning) {
            trigger_error('[JiFramework] ' . $warning, E_USER_WARNING);
        }

        self::$initialized = true;
    }

    /**
     * Start session if not already started.
     */
    public static function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Walk up the directory tree to find the project root containing config/jiconfig.php.
     *
     * Starts from the entry script directory ($_SERVER['SCRIPT_FILENAME']) so that
     * symlinked Composer installs resolve correctly — __DIR__ would point to the real
     * framework source, never reaching the user's project root.
     */
    private static function detectBasePath()
    {
        // Entry script gives the real project path regardless of symlinks
        $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? null;
        $startDir   = ($scriptPath && ($real = realpath($scriptPath)) !== false)
            ? dirname($real)
            : __DIR__;

        $dir = $startDir;

        while (true) {
            if (file_exists($dir . '/config/jiconfig.php')) {
                return $dir . '/';
            }

            $parent = dirname($dir);

            if ($parent === $dir) {
                // Reached filesystem root without finding config — use start dir as fallback
                return $startDir . '/';
            }

            $dir = $parent;
        }
    }

    /**
     * Load config/jiconfig.php from the project root and merge values over defaults.
     * Missing keys fall back to the defaults defined above.
     */
    private static function loadUserConfig()
    {
        $configFile = self::$basePath . 'config/jiconfig.php';

        if (!file_exists($configFile)) {
            self::$warnings[] = 'config/jiconfig.php not found — running on framework defaults. '
                . 'Copy config/jiconfig.example.php to config/jiconfig.php to configure your project.';
            return;
        }

        $userConfig = require $configFile;

        if (!is_array($userConfig)) {
            return;
        }

        $map = self::configMap();

        foreach ($userConfig as $key => $value) {
            if (isset($map[$key])) {
                $property = $map[$key];
                self::$$property = $value;
            }
        }
    }

    /**
     * Set path properties that derive from $basePath / $storagePath.
     * Only sets a path if it was not explicitly provided in jiconfig.php.
     */
    private static function setDerivedPaths()
    {
        $storage = self::$storagePath ?? (self::$basePath . 'storage/');
        self::$storagePath = $storage;

        if (self::$uploadDirectory === null) {
            self::$uploadDirectory = $storage . 'Uploads/';
        }
        if (self::$cachePath === null) {
            self::$cachePath = $storage . 'Cache/FileCache/';
        }
        if (self::$cacheDatabasePath === null) {
            self::$cacheDatabasePath = $storage . 'Cache/DatabaseCache/ji_sqlite_cache.db';
        }
        if (self::$rateLimitDatabasePath === null) {
            self::$rateLimitDatabasePath = $storage . 'RateLimit/rate_limit.db';
        }
        if (self::$logFilePath === null) {
            self::$logFilePath = $storage . 'Logs/';
        }
        if (self::$ipBlockListPath === null) {
            self::$ipBlockListPath = $storage . 'AccessControl/ip_block_list.json';
        }
        if (self::$countryBlockListPath === null) {
            self::$countryBlockListPath = $storage . 'AccessControl/country_block_list.json';
        }
        if (self::$multiLangDir === null) {
            self::$multiLangDir = self::$basePath . 'lang/';
        }
    }

    /**
     * Mapping from jiconfig.php keys to static property names.
     */
    private static function configMap()
    {
        return [
            'app_mode'                 => 'appMode',
            'admin_email'              => 'adminEmail',
            'timezone'                 => 'timezone',
            'error_template'           => 'errorTemplate',
            'database'                 => 'primaryDatabase',
            'databases'                => 'databases',
            'storage_path'             => 'storagePath',
            'upload_directory'         => 'uploadDirectory',
            'allowed_image_types'      => 'allowedImageTypes',
            'max_file_size'            => 'maxFileSize',
            'image_max_dimension'      => 'imageMaxDimension',
            'cache_driver'             => 'cacheDriver',
            'cache_path'               => 'cachePath',
            'cache_database_path'      => 'cacheDatabasePath',
            'rate_limit_enabled'       => 'rateLimitEnabled',
            'rate_limit_requests'      => 'rateLimitRequests',
            'rate_limit_time_window'   => 'rateLimitTimeWindow',
            'rate_limit_ban_enabled'   => 'rateLimitBanEnabled',
            'rate_limit_ban_duration'  => 'rateLimitBanDuration',
            'rate_limit_database_path' => 'rateLimitDatabasePath',
            'router_enabled'           => 'routerEnabled',
            'router_base_path'         => 'routerBasePath',
            'multi_lang'               => 'multiLang',
            'multi_lang_method'        => 'multiLangMethod',
            'multi_lang_default_lang'  => 'multiLangDefaultLang',
            'multi_lang_key'           => 'multiLangKey',
            'multi_lang_dir'           => 'multiLangDir',
            'log_enabled'              => 'logEnabled',
            'log_level'                => 'logLevel',
            'log_file_path'            => 'logFilePath',
            'log_file_name'            => 'logFileName',
            'log_max_file_size'        => 'logMaxFileSize',
            'log_max_files'            => 'logMaxFiles',
            'ip_blocking_enabled'      => 'ipBlockingEnabled',
            'ip_block_list_path'       => 'ipBlockListPath',
            'country_blocking_enabled' => 'countryBlockingEnabled',
            'country_block_list_path'  => 'countryBlockListPath',
            'allow_vpn_proxy'          => 'allowVpnProxy',
            'proxycheck_api_key'       => 'proxycheckApiKey',
            'proxycheck_api_url'       => 'proxycheckApiUrl',
            'trusted_proxies'          => 'trustedProxies',
            'debug_ip'                 => 'debugIp',
            'session_user_key'         => 'userSessionKey',
            'session_admin_key'        => 'adminSessionKey',
            'admin_remember_cookie'    => 'adminRememberCookie',
            'user_remember_cookie'     => 'userRememberCookie',
            'auth_admin_table'         => 'authAdminTable',
            'auth_user_table'          => 'authUserTable',
            'auth_token_table'         => 'authTokenTable',
            'csrf_token_key'           => 'csrfTokenKey',
            'flash_message_key'        => 'flashMessageKey',
            'csrf_token_expiry'        => 'csrfTokenExpiry',
            'csrf_token_limit'         => 'csrfTokenLimit',
            'csrf_token_length'        => 'csrfTokenLength',
        ];
    }

    // =========================================================================
    // Dot-notation config accessor
    // =========================================================================

    /**
     * Get a config value using dot notation.
     *
     * Examples:
     *   Config::get('appMode')
     *   Config::get('primaryDatabase.host')
     *   Config::get('databases.secondary.host')
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $segments = explode('.', $key);
        $property = array_shift($segments);

        if (!property_exists(static::class, $property)) {
            return $default;
        }

        $value = static::$$property;

        foreach ($segments as $segment) {
            if (is_array($value) && isset($value[$segment])) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }
}
