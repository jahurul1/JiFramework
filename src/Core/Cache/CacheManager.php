<?php
namespace JiFramework\Core\Cache;

use JiFramework\Config\Config;

class CacheManager
{
    /**
     * @var CacheInterface|null The instance of the selected cache driver.
     */
    protected static $cacheInstance = null;

    /**
     * Get the cache instance based on the configured cache driver.
     *
     * @param string|null $cacheDriver Optional cache driver to use.
     * @param string|null $cachePath Optional path to the cache directory.
     * @return CacheInterface The cache instance.
     * @throws \Exception If an unsupported cache driver is configured.
     */
    public static function getInstance($cacheDriver=null)
    {
        // Set the cache driver and path based on the provided arguments or the configuration
        $cDriver = $cacheDriver ?? Config::CACHE_DRIVER;

        // Create a new cache instance based on the configured driver
        switch ($cDriver) {
            case 'file':
                self::$cacheInstance = new FileCache(Config::CACHE_PATH);
                break;
            case 'sqlite':
                self::$cacheInstance = new DatabaseCache(Config::CACHE_DATABASE_PATH);
                break;
            default:
                throw new \Exception('Unsupported cache driver: ' . Config::CACHE_DRIVER);
        }

        // Return the cache instance
        return self::$cacheInstance;
    }
}


