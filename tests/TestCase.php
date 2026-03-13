<?php

namespace JiFramework\Tests;

use JiFramework\Config\Config;
use JiFramework\Core\Cache\CacheManager;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;

/**
 * Base test case for JiFramework.
 *
 * Provides:
 *   - A per-test isolated temp directory ($this->tempDir) with auto-cleanup.
 *   - resetCacheManager() — forces a fresh CacheManager singleton.
 *   - resetConfig()       — resets Config to known test defaults.
 */
abstract class TestCase extends BaseTestCase
{
    /** Isolated temp directory created fresh for each test method. */
    protected string $tempDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/ji_test_' . uniqid('', true) . '/';
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
        $this->resetCacheManager();
        parent::tearDown();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Recursively delete a directory, suppressing failures for open handles (Windows). */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . $item;
            is_dir($path) ? $this->removeDir($path . '/') : @unlink($path);
        }
        @rmdir($dir);
    }

    /** Force the CacheManager singleton to be recreated on next access. */
    protected function resetCacheManager(): void
    {
        $ref = new ReflectionClass(CacheManager::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /**
     * Apply default test Config overrides.
     * Call from setUp() in tests that construct App or services directly.
     */
    protected function applyTestConfig(): void
    {
        $testStorage = sys_get_temp_dir() . '/ji_framework_tests/';

        Config::$rateLimitEnabled       = false;
        Config::$ipBlockingEnabled      = false;
        Config::$countryBlockingEnabled = false;
        Config::$logEnabled             = false;
        Config::$multiLang              = false;
        Config::$routerEnabled          = false;
        Config::$logFilePath            = $testStorage . 'Logs/';
        Config::$cachePath              = $testStorage . 'Cache/FileCache/';
        Config::$cacheDatabasePath      = $testStorage . 'Cache/DatabaseCache/ji_sqlite_cache.db';
        Config::$rateLimitDatabasePath  = $testStorage . 'RateLimit/rate_limit.db';
    }
}
