<?php
/**
 * Bootstrap file for tests
 * 
 * This file ensures that the necessary components are loaded
 * before tests are run.
 */

// Check command line arguments for debug or progress mode
$debug = false;
$quiet = true; // Default to quiet mode
$progress = false;

if (isset($argv[1])) {
    if ($argv[1] === 'debug') {
        $debug = true;
        $quiet = false;
    } elseif ($argv[1] === 'progress') {
        $progress = true;
    }
}
if (isset($argv[2])) {
    if ($argv[2] === 'debug') {
        $debug = true;
        $quiet = false;
    } elseif ($argv[2] === 'progress') {
        $progress = true;
    }
}

// Function to output bootstrap information if not in quiet/progress mode
function bootstrap_output($message) {
    global $quiet, $progress, $debug;
    if (!$quiet && !$progress || $debug) {
        echo $message . "\n";
    }
}

// Autoload framework classes
require_once __DIR__ . '/../vendor/autoload.php';

// Import Config class
use JiFramework\Config\Config;

// Initialize the configuration
Config::initialize();

// Set up test environment
// Override any configuration needed for testing
// For example, use a test database instead of the production one
$testDbName = 'jiframework_test';
Config::$primaryDatabase['database'] = $testDbName;

bootstrap_output("==========================================================");
bootstrap_output("        jiFramework Test Bootstrap                        ");
bootstrap_output("==========================================================");
bootstrap_output("Setting up test database: {$testDbName}");

// Ensure test database exists
try {
    // Create a temporary PDO connection to the server without specifying a database
    $dsn = Config::$primaryDatabase['driver'] . ':host=' . Config::$primaryDatabase['host'];
    bootstrap_output("Connecting to database server using DSN: {$dsn}");
    
    $tempPdo = new PDO(
        $dsn,
        Config::$primaryDatabase['username'],
        Config::$primaryDatabase['password'],
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]
    );
    
    // Drop the test database if it exists and create a fresh one
    bootstrap_output("Dropping test database if it exists...");
    $tempPdo->exec("DROP DATABASE IF EXISTS `$testDbName`");
    
    bootstrap_output("Creating test database...");
    $tempPdo->exec("CREATE DATABASE `$testDbName`");
    
    bootstrap_output("Test database created successfully.");
    
    // Connect to the new database to make sure it works
    $testDbDsn = $dsn . ";dbname={$testDbName}";
    bootstrap_output("Testing connection to new database: {$testDbDsn}");
    
    $testDbPdo = new PDO(
        $testDbDsn,
        Config::$primaryDatabase['username'],
        Config::$primaryDatabase['password'],
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]
    );
    
    bootstrap_output("Successfully connected to test database.");
    
    // Create the test table in advance
    bootstrap_output("Creating test_table...");
    $createTableSql = "
        CREATE TABLE `test_table` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $testDbPdo->exec($createTableSql);
    bootstrap_output("Test table created successfully.");
    
} catch (PDOException $e) {
    bootstrap_output("Database setup error: " . $e->getMessage());
    // Continue execution to allow tests that don't require database to run
}

// Create test directories if they don't exist
$testCacheDir = Config::STORAGE_PATH . 'Cache/FileCache/test/';
if (!is_dir($testCacheDir)) {
    mkdir($testCacheDir, 0777, true);
}

$testLogsDir = Config::STORAGE_PATH . 'Logs/test/';
if (!is_dir($testLogsDir)) {
    mkdir($testLogsDir, 0777, true);
}

$testUploadsDir = Config::STORAGE_PATH . 'Uploads/test/';
if (!is_dir($testUploadsDir)) {
    mkdir($testUploadsDir, 0777, true);
}

// Function to help with test teardown
function cleanupTestFiles() {
    // Add cleanup logic here
    // For example, remove test files
}

// Register teardown function to run on script completion
register_shutdown_function('cleanupTestFiles'); 


