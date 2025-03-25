<?php
namespace JiFramework\Core\Database;

use PDO;
use PDOException;
use JiFramework\Config\Config;

class DatabaseConnection
{
    /**
     * The PDO instance.
     *
     * @var PDO|null
     */
    private static $connections = [];

    /**
     * Get a PDO connection instance.
     *
     * @param string $connectionName The name of the database connection ('primary' or a key from Config::$databases)
     * @return PDO
     * @throws \Exception If the connection fails or configuration is missing.
     */
    public static function getConnection($connectionName = 'primary')
    {
        if (!is_array(self::$connections)) {
            self::$connections = [];
        }

        if (isset(self::$connections[$connectionName])) {
            return self::$connections[$connectionName];
        }

        // Retrieve the appropriate database configuration
        if ($connectionName === 'primary') {
            $dbConfig = Config::$primaryDatabase;
        } else {
            $dbConfig = Config::$databases[$connectionName] ?? null;
            if (!$dbConfig) {
                throw new \Exception("Database configuration for '{$connectionName}' not found.");
            }
        }

        // Build the DSN string
        $dsn = "{$dbConfig['driver']}:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";

        try {
            // Create a new PDO instance
            $pdo = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['options']
            );

            // Store the connection for future use
            self::$connections[$connectionName] = $pdo;

            return $pdo;
        } catch (PDOException $e) {
            // Handle connection errors
            $message = "Database connection error ({$connectionName}): " . $e->getMessage();
            if (Config::APP_MODE === 'development') {
                throw new \Exception($message);
            } else {
                // In production mode, log the error and show a generic message
                error_log($message);
                throw new \Exception("Unable to connect to the database.");
            }
        }
    }
}


