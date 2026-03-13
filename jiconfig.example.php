<?php
/**
 * JiFramework Configuration
 *
 * Copy this file to jiconfig.php in your project root and fill in your values.
 * You only need to include the keys you want to change — everything else
 * falls back to the framework defaults.
 *
 * Usage:
 *   cp jiconfig.example.php jiconfig.php
 */

return [

    // -------------------------------------------------------------------------
    // App
    // -------------------------------------------------------------------------

    'app_mode'    => 'development',     // 'development' | 'production'
    'admin_email' => 'admin@example.com',
    'timezone'    => 'UTC',             // e.g. 'Asia/Dhaka', 'America/New_York'

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    // Absolute path to your custom error page template.
    // Two variables are available inside it: $errorCode (int) and $errorMessage (string).
    // Leave commented out to use the built-in error page.
    // 'error_template' => __DIR__ . '/views/errors/error.php',

    // -------------------------------------------------------------------------
    // Database — primary connection
    // -------------------------------------------------------------------------

    'database' => [
        'host'      => 'localhost',
        'database'  => 'my_database',
        'username'  => 'root',
        'password'  => '',
        'driver'    => 'mysql',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options'   => [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
    ],

    // -------------------------------------------------------------------------
    // Database — additional named connections (optional)
    // Access via: $app->db->connection('secondary')
    // -------------------------------------------------------------------------

    'databases' => [
        // 'secondary' => [
        //     'host'      => 'localhost',
        //     'database'  => 'second_db',
        //     'username'  => 'root',
        //     'password'  => '',
        //     'driver'    => 'mysql',
        //     'charset'   => 'utf8mb4',
        //     'collation' => 'utf8mb4_unicode_ci',
        //     'options'   => [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        // ],
    ],

    // -------------------------------------------------------------------------
    // Router
    // -------------------------------------------------------------------------

    'router_enabled'   => false,
    // Base path for subdirectory installs — e.g. '/myapp' for localhost/myapp/
    // Leave empty if running at the domain/server root.
    'router_base_path' => '',

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    'cache_driver' => 'file',   // 'file' | 'sqlite'
    // 'cache_path'          => '/custom/path/cache/',
    // 'cache_database_path' => '/custom/path/cache.db',

    // -------------------------------------------------------------------------
    // File uploads
    // -------------------------------------------------------------------------

    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif'],
    'max_file_size'       => 5242880,   // bytes — 5 MB
    'image_max_dimension' => 800,       // px
    // 'upload_directory' => '/custom/path/uploads/',

    // -------------------------------------------------------------------------
    // Rate limiting
    // -------------------------------------------------------------------------

    'rate_limit_enabled'      => false,
    'rate_limit_requests'     => 500,   // max requests per window
    'rate_limit_time_window'  => 60,    // seconds
    'rate_limit_ban_enabled'  => true,
    'rate_limit_ban_duration' => 3600,  // seconds
    // 'rate_limit_database_path' => '/custom/path/rate_limit.db',

    // -------------------------------------------------------------------------
    // Logging
    // -------------------------------------------------------------------------

    'log_enabled'       => true,
    'log_level'         => 'DEBUG',  // DEBUG | INFO | NOTICE | WARNING | ERROR | CRITICAL
    'log_file_name'     => 'app.log',
    'log_max_file_size' => 5242880,  // bytes — 5 MB
    'log_max_files'     => 20,
    // 'log_file_path'  => '/custom/path/logs/',

    // -------------------------------------------------------------------------
    // Access control
    // -------------------------------------------------------------------------

    'ip_blocking_enabled'      => false,
    'country_blocking_enabled' => false,
    'allow_vpn_proxy'          => true,
    'proxycheck_api_key'       => '',   // https://proxycheck.io/
    'proxycheck_api_url'       => 'https://proxycheck.io/v2/{ip}',
    // 'ip_block_list_path'      => null, // default: storage/AccessControl/ip_block_list.json
    // 'country_block_list_path' => null, // default: storage/AccessControl/country_block_list.json

    // Trusted reverse proxy IPs — leave empty if not behind a load balancer or CDN.
    // When set, getClientIp() will trust X-Forwarded-For from these IPs only.
    // 'trusted_proxies' => ['127.0.0.1', '10.0.0.1'],

    // -------------------------------------------------------------------------
    // Development / debugging
    // -------------------------------------------------------------------------

    // Override the detected client IP for testing IP-based features on localhost.
    // Only active when app_mode = 'development' — automatically ignored in production.
    // 'debug_ip' => '203.0.113.42',  // replace with your real public IP

    // -------------------------------------------------------------------------
    // Multi-language
    // -------------------------------------------------------------------------

    'multi_lang'              => false,
    'multi_lang_method'       => 'url',     // 'url' | 'cookie'
    'multi_lang_default_lang' => 'en',
    'multi_lang_key'          => 'lang',
    // 'multi_lang_dir'       => '/custom/path/lang/',

    // -------------------------------------------------------------------------
    // Auth tables — set these to match your actual database table names
    // -------------------------------------------------------------------------

    'auth_admin_table' => 'admin',   // table for admin accounts
    'auth_user_table'  => 'users',   // table for user accounts
    'auth_token_table' => 'tokens',  // table for remember-me tokens

    // -------------------------------------------------------------------------
    // Session & CSRF  (change these to unique values per project)
    // -------------------------------------------------------------------------

    'session_user_key'      => '_ji_user_session',
    'session_admin_key'     => '_ji_admin_session',
    'admin_remember_cookie' => '_ji_admin_remember',
    'user_remember_cookie'  => '_ji_user_remember',
    'csrf_token_key'        => '_ji_csrf_token',
    'flash_message_key'     => '_ji_flash_messages',
    'csrf_token_expiry'     => 43200,   // seconds — 12 hours
    'csrf_token_limit'      => 100,
    'csrf_token_length'     => 32,

];
