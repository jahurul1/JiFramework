<?php
namespace JiFramework\Core\Security;

use JiFramework\Config\Config;
use JiFramework\Core\Utilities\Request;
use JiFramework\Exceptions\HttpException;
use PDO;
use PDOException;

class RateLimiter
{
    // =========================================================================
    // Internal state
    // =========================================================================

    private ?PDO $pdo = null;

    private Request $request;

    /**
     * Set to true when the SQLite backend fails to initialise.
     * Causes enforceRateLimit() to fail open (allow all requests) rather than
     * crashing the application because of a storage problem.
     */
    private bool $disabled = false;

    // =========================================================================
    // Bootstrap
    // =========================================================================

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        // Skip all database work when rate limiting is turned off in config
        if (!Config::$rateLimitEnabled) {
            return;
        }

        $databasePath = Config::$rateLimitDatabasePath;
        $directory    = dirname($databasePath);

        if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
            trigger_error('[RateLimiter] Cannot create directory: ' . $directory, E_USER_WARNING);
            $this->disabled = true;
            return;
        }

        try {
            $this->pdo = new PDO('sqlite:' . $databasePath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA synchronous = NORMAL;');
            $this->createTablesIfNotExists();

            // Probabilistic GC: run on roughly 1% of requests to keep the DB tidy
            // without adding overhead on every request.
            if (random_int(1, 100) === 1) {
                $this->performGarbageCollection();
            }

        } catch (PDOException $e) {
            trigger_error('[RateLimiter] SQLite error: ' . $e->getMessage(), E_USER_WARNING);
            $this->disabled = true;
        }
    }

    // =========================================================================
    // Core enforcement
    // =========================================================================

    /**
     * Enforce the rate limit for the current request.
     *
     * Call this once per request (App::__construct() already does this automatically).
     * Does nothing when rate limiting is disabled or the database backend is unavailable.
     *
     * @throws HttpException 429 when the client is banned or has exceeded the limit.
     */
    public function enforceRateLimit(): void
    {
        if (!Config::$rateLimitEnabled || $this->disabled) {
            return;
        }

        $ip = $this->getIpAddress();

        // Single query covers both "is banned?" and "when does the ban expire?"
        $ban = $this->fetchActiveBan($ip);

        if ($ban !== null) {
            $remaining = max(0, $ban['ban_expires'] - time());
            throw new HttpException(
                429,
                'You are banned. Try again in ' . $remaining . ' second' . ($remaining !== 1 ? 's' : '') . '.'
            );
        }

        if (!$this->isAllowed($ip)) {
            if (Config::$rateLimitBanEnabled) {
                $this->banIpInternal($ip);
                $duration = Config::$rateLimitBanDuration;
                throw new HttpException(
                    429,
                    'Rate limit exceeded. You are banned for ' . $duration . ' second' . ($duration !== 1 ? 's' : '') . '.'
                );
            }

            throw new HttpException(429, 'Too many requests. Please try again later.');
        }

        $this->logRequest($ip);
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Check whether an IP address is currently banned.
     *
     * @param string $ip
     * @return bool
     */
    public function isBannedIp(string $ip): bool
    {
        if ($this->disabled || $this->pdo === null) {
            return false;
        }

        return $this->fetchActiveBan($ip) !== null;
    }

    /**
     * Manually ban an IP address.
     *
     * If the IP is already banned the existing ban is replaced (effectively
     * extending or shortening it).
     *
     * @param string   $ip
     * @param int|null $duration Seconds from now. Defaults to Config::$rateLimitBanDuration.
     * @return bool True on success, false when the backend is unavailable.
     */
    public function banIp(string $ip, ?int $duration = null): bool
    {
        if ($this->disabled || $this->pdo === null) {
            return false;
        }

        try {
            $expires = time() + ($duration ?? Config::$rateLimitBanDuration);
            $stmt    = $this->pdo->prepare('INSERT OR REPLACE INTO bans (ip_address, ban_expires) VALUES (:ip, :expires)');
            $stmt->execute([':ip' => $ip, ':expires' => $expires]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Remove an active ban for an IP address.
     * Safe to call when the IP is not banned — returns true in that case.
     *
     * @param string $ip
     * @return bool True on success, false when the backend is unavailable.
     */
    public function unbanIp(string $ip): bool
    {
        if ($this->disabled || $this->pdo === null) {
            return false;
        }

        try {
            $this->pdo->prepare('DELETE FROM bans WHERE ip_address = :ip')->execute([':ip' => $ip]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get detailed ban information for an IP address.
     * Returns null when the IP is not currently banned.
     *
     * @param string $ip
     * @return array|null ['ip' => string, 'ban_expires' => int, 'seconds_remaining' => int]
     */
    public function getBanInfo(string $ip): ?array
    {
        if ($this->disabled || $this->pdo === null) {
            return null;
        }

        $ban = $this->fetchActiveBan($ip);

        if ($ban === null) {
            return null;
        }

        return [
            'ip'                => $ip,
            'ban_expires'       => $ban['ban_expires'],
            'seconds_remaining' => max(0, $ban['ban_expires'] - time()),
        ];
    }

    /**
     * Get the number of requests remaining in the current time window.
     *
     * @param string|null $ip IP to check. Uses the current request IP when null.
     * @return int Remaining requests. Returns the full limit when the backend is unavailable.
     */
    public function getRemainingRequests(?string $ip = null): int
    {
        if ($this->disabled || $this->pdo === null) {
            return Config::$rateLimitRequests;
        }

        $ip              = $ip ?? $this->getIpAddress();
        $timeWindowStart = time() - Config::$rateLimitTimeWindow;

        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM requests WHERE ip_address = :ip AND timestamp >= :window');
            $stmt->execute([':ip' => $ip, ':window' => $timeWindowStart]);
            $used = (int) $stmt->fetchColumn();
            return max(0, Config::$rateLimitRequests - $used);
        } catch (PDOException $e) {
            return Config::$rateLimitRequests;
        }
    }

    /**
     * Reset all rate limit data for an IP address (clears both request log and active ban).
     *
     * @param string $ip
     * @return bool True on success, false when the backend is unavailable.
     */
    public function resetIp(string $ip): bool
    {
        if ($this->disabled || $this->pdo === null) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare('DELETE FROM requests WHERE ip_address = :ip')->execute([':ip' => $ip]);
            $this->pdo->prepare('DELETE FROM bans WHERE ip_address = :ip')->execute([':ip' => $ip]);
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    // =========================================================================
    // Private — core logic
    // =========================================================================

    private function createTablesIfNotExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS requests (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT    NOT NULL,
                timestamp  INTEGER NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_requests_ip_timestamp ON requests (ip_address, timestamp);

            CREATE TABLE IF NOT EXISTS bans (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address  TEXT    NOT NULL UNIQUE,
                ban_expires INTEGER NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_bans_ip ON bans (ip_address);
        ");
    }

    /**
     * Return true when the IP has fewer requests than the limit in the current window.
     */
    private function isAllowed(string $ip): bool
    {
        $timeWindowStart = time() - Config::$rateLimitTimeWindow;
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM requests WHERE ip_address = :ip AND timestamp >= :window');
        $stmt->execute([':ip' => $ip, ':window' => $timeWindowStart]);
        return (int) $stmt->fetchColumn() < Config::$rateLimitRequests;
    }

    /**
     * Fetch an active (non-expired) ban row for the given IP.
     * Returns null when the IP is not banned.
     */
    private function fetchActiveBan(string $ip): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ban_expires FROM bans WHERE ip_address = :ip AND ban_expires > :now LIMIT 1');
        $stmt->execute([':ip' => $ip, ':now' => time()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Write a ban record for the given IP. Uses INSERT OR REPLACE so it is safe
     * to call even when the IP is already banned.
     */
    private function banIpInternal(string $ip): void
    {
        $expires = time() + Config::$rateLimitBanDuration;
        $stmt    = $this->pdo->prepare('INSERT OR REPLACE INTO bans (ip_address, ban_expires) VALUES (:ip, :expires)');
        $stmt->execute([':ip' => $ip, ':expires' => $expires]);
    }

    /**
     * Record a request timestamp for the given IP.
     */
    private function logRequest(string $ip): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO requests (ip_address, timestamp) VALUES (:ip, :ts)');
        $stmt->execute([':ip' => $ip, ':ts' => time()]);
    }

    /**
     * Delete expired request logs and expired bans.
     * Called probabilistically — not on every request.
     */
    private function performGarbageCollection(): void
    {
        $windowStart = time() - Config::$rateLimitTimeWindow;
        $this->pdo->prepare('DELETE FROM requests WHERE timestamp < :window')->execute([':window' => $windowStart]);
        $this->pdo->prepare('DELETE FROM bans WHERE ban_expires <= :now')->execute([':now' => time()]);
    }

    /**
     * Return the client IP address for the current request.
     */
    private function getIpAddress(): string
    {
        return $this->request->getClientIp();
    }
}
