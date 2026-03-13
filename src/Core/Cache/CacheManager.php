<?php
namespace JiFramework\Core\Cache;

use JiFramework\Config\Config;

class CacheManager
{
    /**
     * @var CacheInterface|null The single cache instance.
     */
    protected static ?CacheInterface $instance = null;

    /**
     * Get the cache instance based on the configured cache driver.
     * The instance is created once and reused on all subsequent calls.
     *
     * @param string|null $cacheDriver 'file' or 'sqlite'. Defaults to Config::$cacheDriver.
     * @return CacheInterface
     * @throws \Exception If an unsupported cache driver is specified.
     */
    public static function getInstance($cacheDriver = null): CacheInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $cDriver = $cacheDriver ?? Config::$cacheDriver;

        switch ($cDriver) {
            case 'file':
                self::$instance = new FileCache(Config::$cachePath);
                break;
            case 'sqlite':
                self::$instance = new DatabaseCache(Config::$cacheDatabasePath);
                break;
            default:
                throw new \Exception('Unsupported cache driver: ' . $cDriver);
        }

        return self::$instance;
    }
}


