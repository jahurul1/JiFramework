<?php
namespace JiFramework\Core\Cache;

use JiFramework\Config\Config;

class FileCache implements CacheInterface
{
    /**
     * @var string The path where the cache files will be stored.
     */
    protected $cachePath;

    /**
     * Constructor to initialize the cache path.
     * If no path is provided, it defaults to the configured cache path.
     *
     * @param string|null $cachePath Optional path to the cache directory.
     */

    public function __construct($cachePath = null)
    {
        $this->cachePath = $cachePath ?? Config::CACHE_PATH;

        // Ensure the cache directory exists, create if it doesn't
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
        
    }

    /**
     * Generate the file path for a given cache key.
     *
     * @param string $key Cache key.
     * @return string Full file path for the cache file.
     */
    protected function getCacheFilePath($key)
    {
        $filename = md5($key) . '.cache';
        return rtrim($this->cachePath, '/') . '/' . $filename;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key The cache key.
     * @return mixed The cached value, or null if the item does not exist or is expired.
     */
    public function get($key)
    {
        // Get the cache file path
        $filePath = $this->getCacheFilePath($key);

        // If the file doesn't exist, return null
        if (!file_exists($filePath)) {
            return null;
        }

        // Get the file contents
        $data = file_get_contents($filePath);

        // Check if the data is false
        if ($data === false) {
            return null;
        }

        // Unserialize the data
        $cacheItem = unserialize($data);

        // Check if the cache item has expired
        if($cacheItem['expiration'] !== null && $cacheItem['expiration'] < time()) {
            // Delete the cache file
            unlink($filePath);
            return null;
        }

        // Return the cached value
        return $cacheItem['value'];
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
        // Get the cache file path
        $filePath = $this->getCacheFilePath($key);

        // Calculate the expiration time
        $expiration = $ttl ? time() + $ttl : null;

        // Create the cache item
        $cacheItem = [
            'value' => $value,
            'expiration' => $expiration,
        ];

        // Serialize the cache item
        $data = serialize($cacheItem);

        // Write the data to the cache file
        return file_put_contents($filePath, $data) !== false;
    }

    /**
     * Delete an item from the cache by key.
     *
     * @param string $key The cache key.
     * @return bool True if the cache item was successfully deleted, false otherwise.
     */
    public function delete($key)
    {
        // Get the cache file path
        $filePath = $this->getCacheFilePath($key);

        // If the file doesn't exist, return false
        if (!file_exists($filePath)) {
            return false;
        }

        // Delete the cache file
        return unlink($filePath);
    }

    /**
     * Check if a cache item exists and is still valid.
     *
     * @param string $key The cache key.
     * @return bool True if the cache item exists and has not expired.
     */
    public function has($key)
    {
        // Get the cache file path
        $filePath = $this->getCacheFilePath($key);

        // If the file doesn't exist, return false
        if (!file_exists($filePath)) {
            return false;
        }

        // Get the file contents
        $data = file_get_contents($filePath);

        // Check if the data is false
        if ($data === false) {
            return false;
        }

        // Unserialize the data
        $cacheItem = unserialize($data);

        // Check if the cache item has expired
        if($cacheItem['expiration'] !== null && $cacheItem['expiration'] < time()) {
            // Delete the cache file
            unlink($filePath);
            return false;
        }

        return true;
    }

    /**
     * Increment the value of a numeric cache item.
     *
     * @param string $key The cache key.
     * @param int $value The value to increment by.
     * @return int The new value after incrementing.
     */
    public function increment($key, $value = 1)
    {
        // Get the current value
        $currentValue = $this->get($key);

        // If the value is not numeric, return false
        if (!is_numeric($currentValue)) {
            return false;
        }

        // Increment the value
        $newValue = $currentValue + $value;

        // Store the new value in the cache
        $this->set($key, $newValue);

        return $newValue;
    }

    /**
     * Decrement the value of a numeric cache item.
     *
     * @param string $key The cache key.
     * @param int $value The value to decrement by.
     * @return int The new value after decrementing.
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, -$value);
    }

    /**
     * Clear all cache items.
     *
     * @return void
     */
    public function clear()
    {
        // Get all cache files
        $cacheFiles = glob($this->cachePath . '/*.cache');

        // Delete each cache file
        foreach ($cacheFiles as $file) {
            unlink($file);
        }
    }

    /**
     * Garbage collection to remove expired cache items.
     *
     * @return void
     */
    public function gc()
    {
        // Get all cache files in the cache directory
        $cacheFiles = glob($this->cachePath . '/*.cache');

        $currentTime = time();

        // Iterate over each cache file
        foreach ($cacheFiles as $file) {
            // Get the file contents
            $data = file_get_contents($file);

            // Check if the data is false
            if ($data === false) {
                // Unable to read file, skip it
                continue;
            }

            // Unserialize the data
            $cacheItem = unserialize($data);

            // Check if the cache item has expired
            if (isset($cacheItem['expiration']) && $cacheItem['expiration'] !== null && $cacheItem['expiration'] < $currentTime) {
                // Delete the cache file
                unlink($file);
            }
        }
    }


}


