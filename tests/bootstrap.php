<?php

/**
 * PHPUnit bootstrap — runs once before the entire test suite.
 *
 * Sets up a minimal, test-safe environment:
 *   - Superglobals that the framework reads during boot
 *   - Config::initialize() called exactly once
 *   - All file-I/O paths redirected to a writable temp dir
 *   - Dangerous features (rate-limit, IP/country blocking) disabled
 */

$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['HTTP_HOST']      = 'localhost';
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS']          = 'off';
$_SERVER['SERVER_PORT']    = '80';

// Make Config::detectBasePath() resolve to the project root so that the
// optional config/jiconfig.php is found (or gracefully skipped).
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/index.php';

require dirname(__DIR__) . '/vendor/autoload.php';

use JiFramework\Config\Config;

// Boot Config once — idempotent, won't re-run in later test classes.
// Suppress the "jiconfig.php not found" warning — expected in test environment.
set_error_handler(function () { return true; }, E_USER_WARNING);
Config::initialize();
restore_error_handler();

// ── Redirect all storage I/O to an isolated temp directory ──────────────────
$testStorage = sys_get_temp_dir() . '/ji_framework_tests/';
@mkdir($testStorage, 0755, true);

Config::$storagePath           = $testStorage;
Config::$logFilePath           = $testStorage . 'Logs/';
Config::$cachePath             = $testStorage . 'Cache/FileCache/';
Config::$cacheDatabasePath     = $testStorage . 'Cache/DatabaseCache/ji_sqlite_cache.db';
Config::$rateLimitDatabasePath = $testStorage . 'RateLimit/rate_limit.db';
Config::$uploadDirectory       = $testStorage . 'Uploads/';
Config::$ipBlockListPath       = $testStorage . 'AccessControl/ip_block_list.json';
Config::$countryBlockListPath  = $testStorage . 'AccessControl/country_block_list.json';

// ── Disable features that talk to external services or need real DB ──────────
Config::$rateLimitEnabled       = false;
Config::$ipBlockingEnabled      = false;
Config::$countryBlockingEnabled = false;
Config::$logEnabled             = false; // individual tests re-enable as needed
Config::$multiLang              = false;
Config::$routerEnabled          = false;
Config::$appMode                = 'development';

// ── Reset CacheManager singleton between test runs (tests use fresh instances)
// Done via tearDown in TestCase — nothing extra needed here.
