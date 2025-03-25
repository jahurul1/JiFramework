<?php
namespace JiFramework\Core\Cache;

use PDO;
use PDOException;
use JiFramework\Config\Config;

class DatabaseCache implements CacheInterface
{
    /**
     * @var PDO The PDO instance for SQLite connection.
     */
    protected $pdo;

    /**
     * @var string The path to the SQLite database file.
     */
    protected $databasePath;

    /**
     * Constructor to initialize the SQLite database connection.
     *
     * @param string|null $databasePath Optional path to the SQLite database file.
     * @throws \Exception If the PDO connection fails.
     */
    public function __construct($databasePath = null)
    {
        $this->databasePath = $databasePath ?? Config::CACHE_DATABASE_PATH;

        // Ensure the directory for the database file exists
        $directory = dirname($this->databasePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception("Unable to create directory for database: {$directory}");
            }
        }

        // Initialize the PDO connection
        try {
            $this->pdo = new PDO('sqlite:' . $this->databasePath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create the cache table if it doesn't exist
            $this->createTableIfNotExists();

        } catch (PDOException $e) {
            throw new \Exception('SQLite connection error: ' . $e->getMessage());
        }
    }

    /**
     * Create the cache table if it does not exist.
     *
     * @return void
     */
    protected function createTableIfNotExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS cache (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            value BLOB NOT NULL,
            expiration INTEGER
        );
        CREATE INDEX IF NOT EXISTS idx_key ON cache (key);
        CREATE INDEX IF NOT EXISTS idx_expiration ON cache (expiration);";

        $this->pdo->exec($sql);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key The cache key.
     * @return mixed The cached value, or null if the item does not exist or is expired.
     */
    public function get($key)
    {
        $sql = "SELECT value, expiration FROM cache WHERE key = :key LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $expiration = $row['expiration'];
            if ($expiration !== null && $expiration < time()) {
                // The item has expired; delete it
                $this->delete($key);
                return null;
            }
            return unserialize($row['value']);
        }

        return null;
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key The cache key.
     * @param mixed $value The value to store.
     * @param int|null $ttl Optional time-to-live in seconds.
     * @return bool True if the cache item was successfully stored.
     */
    public function set($key, $value, $ttl = null)
    {
        $expiration = $ttl ? time() + $ttl : null;
        $serializedValue = serialize($value);

        $sql = "REPLACE INTO cache (key, value, expiration) VALUES (:key, :value, :expiration)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':key' => $key,
            ':value' => $serializedValue,
            ':expiration' => $expiration,
        ]);
    }

    /**
     * Delete an item from the cache by key.
     *
     * @param string $key The cache key.
     * @return bool True if the cache item was successfully deleted, false otherwise.
     */
    public function delete($key)
    {
        $sql = "DELETE FROM cache WHERE key = :key";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':key' => $key]);
    }

    /**
     * Check if a cache item exists and is still valid.
     *
     * @param string $key The cache key.
     * @return bool True if the cache item exists and has not expired.
     */
    public function has($key)
    {
        $sql = "SELECT COUNT(*) FROM cache WHERE key = :key AND (expiration IS NULL OR expiration >= :current_time)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':key' => $key,
            ':current_time' => time(),
        ]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Increment the value of a numeric cache item.
     *
     * @param string $key The cache key.
     * @param int $value The value to increment by.
     * @return int|bool The new value after incrementing, or false on failure.
     */
    public function increment($key, $value = 1)
    {
        $currentValue = $this->get($key);

        if (!is_numeric($currentValue)) {
            return false;
        }

        $newValue = $currentValue + $value;
        if ($this->set($key, $newValue)) {
            return $newValue;
        }

        return false;
    }

    /**
     * Decrement the value of a numeric cache item.
     *
     * @param string $key The cache key.
     * @param int $value The value to decrement by.
     * @return int|bool The new value after decrementing, or false on failure.
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * Clear all cache items.
     *
     * @return bool True on success, false on failure.
     */
    public function clear()
    {
        $sql = "DELETE FROM cache";
        return $this->pdo->exec($sql) !== false;
    }

    /**
     * Garbage collection to remove expired cache items.
     *
     * @return bool True on success, false on failure.
     */
    public function gc()
    {
        $sql = "DELETE FROM cache WHERE expiration IS NOT NULL AND expiration < :current_time";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':current_time' => time()]);
    }

}


