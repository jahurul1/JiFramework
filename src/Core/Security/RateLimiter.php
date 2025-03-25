<?php
namespace JiFramework\Core\Security;

use JiFramework\Config\Config;
use JiFramework\Core\Utilities\Environment\EnvironmentHelper;
use PDO;
use PDOException;

class RateLimiter
{
    /**
     * @var PDO The PDO instance for SQLite connection.
     */
    protected $pdo;

    /**
     * @var EnvironmentHelper
     */
    protected $environmentHelper;

    /**
     * Constructor to initialize the SQLite database connection.
     *
     * @param EnvironmentHelper $environmentHelper
     * @throws \Exception If the PDO connection fails.
     */
    public function __construct(EnvironmentHelper $environmentHelper)
    {
        $this->environmentHelper = $environmentHelper;
        $databasePath = Config::RATE_LIMIT_DATABASE_PATH;

        // Ensure the directory for the database file exists
        $directory = dirname($databasePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception("Unable to create directory for rate limit database: {$directory}");
            }
        }

        // Initialize the PDO connection
        try {
            $this->pdo = new PDO('sqlite:' . $databasePath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Set the PRAGMA options for performance optimization
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA synchronous = NORMAL;');

            // Create the tables if they do not exist
            $this->createTablesIfNotExists();

            // Perform garbage collection
            $this->performGarbageCollection();

        } catch (PDOException $e) {
            throw new \Exception('SQLite connection error: ' . $e->getMessage());
        }
    }

    /**
     * Create the required tables if they do not exist.
     *
     * @return void
     */
    protected function createTablesIfNotExists()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            timestamp INTEGER NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_requests_ip_timestamp ON requests (ip_address, timestamp);

        CREATE TABLE IF NOT EXISTS bans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL UNIQUE,
            ban_expires INTEGER NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_bans_ban_expires ON bans (ban_expires);
        ";

        $this->pdo->exec($sql);
    }

    /**
     * Enforce the rate limit.
     *
     * @return void
     */
    public function enforceRateLimit()
    {
        if (Config::RATE_LIMIT_ENABLED) {
            $ipAddress = $this->getIpAddress();

            if ($this->isBanned($ipAddress)) {
                $banExpires = $this->getBanExpiration($ipAddress);
                $remainingBanTime = $banExpires - time();
                http_response_code(429);
                echo 'You are banned for ' . $remainingBanTime . ' seconds.';
                exit();
            }

            if (!$this->isAllowed($ipAddress)) {
                if (Config::RATE_LIMIT_BAN_ENABLED) {
                    $this->banIp($ipAddress);
                    http_response_code(429);
                    echo 'You are banned for ' . Config::RATE_LIMIT_BAN_DURATION . ' seconds.';
                    exit();
                } else {
                    http_response_code(429);
                    echo 'Too many requests. Please try again later.';
                    exit();
                }
            }

            // Log the request
            $this->logRequest($ipAddress);
        }
    }

    /**
     * Check if the IP address is allowed to make a request.
     *
     * @param string $ipAddress
     * @return bool
     */
    protected function isAllowed($ipAddress)
    {
        $timeWindowStart = time() - Config::RATE_LIMIT_TIME_WINDOW;

        $sql = "SELECT COUNT(*) FROM requests WHERE ip_address = :ip_address AND timestamp >= :time_window_start";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':time_window_start' => $timeWindowStart,
        ]);

        $requestCount = $stmt->fetchColumn();

        return $requestCount < Config::RATE_LIMIT_REQUESTS;
    }

    /**
     * Check if the IP address is currently banned.
     *
     * @param string $ipAddress
     * @return bool
     */
    protected function isBanned($ipAddress)
    {
        $currentTime = time();

        $sql = "SELECT ban_expires FROM bans WHERE ip_address = :ip_address AND ban_expires > :current_time LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':current_time' => $currentTime,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Get the ban expiration timestamp for an IP address.
     *
     * @param string $ipAddress
     * @return int|null
     */
    protected function getBanExpiration($ipAddress)
    {
        $sql = "SELECT ban_expires FROM bans WHERE ip_address = :ip_address LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ip_address' => $ipAddress]);

        $banExpires = $stmt->fetchColumn();
        return $banExpires !== false ? (int)$banExpires : null;
    }

    /**
     * Ban an IP address.
     *
     * @param string $ipAddress
     * @return void
     */
    protected function banIp($ipAddress)
    {
        $banExpires = time() + Config::RATE_LIMIT_BAN_DURATION;

        $sql = "INSERT OR REPLACE INTO bans (ip_address, ban_expires) VALUES (:ip_address, :ban_expires)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':ban_expires' => $banExpires,
        ]);
    }

    /**
     * Log a request from an IP address.
     *
     * @param string $ipAddress
     * @return void
     */
    protected function logRequest($ipAddress)
    {
        $currentTime = time();

        $sql = "INSERT INTO requests (ip_address, timestamp) VALUES (:ip_address, :timestamp)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ip_address' => $ipAddress,
            ':timestamp' => $currentTime,
        ]);
    }

    /**
     * Perform garbage collection to clean up old requests and expired bans.
     *
     * @return void
     */
    protected function performGarbageCollection()
    {
        $timeWindowStart = time() - Config::RATE_LIMIT_TIME_WINDOW;
        $currentTime = time();

        // Delete old requests
        $sqlRequests = "DELETE FROM requests WHERE timestamp < :time_window_start";
        $stmtRequests = $this->pdo->prepare($sqlRequests);
        $stmtRequests->execute([':time_window_start' => $timeWindowStart]);

        // Delete expired bans
        $sqlBans = "DELETE FROM bans WHERE ban_expires <= :current_time";
        $stmtBans = $this->pdo->prepare($sqlBans);
        $stmtBans->execute([':current_time' => $currentTime]);
    }

    /**
     * Get the client's IP address.
     *
     * @return string
     */
    protected function getIpAddress()
    {
        return $this->environmentHelper->getUserIp();
    }
}


