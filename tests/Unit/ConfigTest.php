<?php

namespace JiFramework\Tests\Unit;

use JiFramework\Config\Config;
use JiFramework\Tests\TestCase;

class ConfigTest extends TestCase
{
    // ── Defaults ─────────────────────────────────────────────────────────────

    public function testDefaultAppMode(): void
    {
        $this->assertSame('development', Config::$appMode);
    }

    public function testDefaultTimezone(): void
    {
        $this->assertNotEmpty(Config::$timezone);
    }

    public function testDefaultRateLimitDisabled(): void
    {
        // bootstrap disables rate limiting
        $this->assertFalse(Config::$rateLimitEnabled);
    }

    public function testDefaultIpBlockingDisabled(): void
    {
        $this->assertFalse(Config::$ipBlockingEnabled);
    }

    public function testStoragePathIsSet(): void
    {
        $this->assertNotNull(Config::$storagePath);
        $this->assertStringEndsWith('/', Config::$storagePath);
    }

    public function testBasePathIsSet(): void
    {
        $this->assertNotNull(Config::$basePath);
        $this->assertStringEndsWith('/', Config::$basePath);
    }

    public function testDerivedPathsNotNull(): void
    {
        $this->assertNotNull(Config::$logFilePath);
        $this->assertNotNull(Config::$cachePath);
        $this->assertNotNull(Config::$cacheDatabasePath);
        $this->assertNotNull(Config::$rateLimitDatabasePath);
        $this->assertNotNull(Config::$uploadDirectory);
    }

    // ── Config::get() ────────────────────────────────────────────────────────

    public function testGetTopLevelProperty(): void
    {
        $this->assertSame(Config::$appMode, Config::get('appMode'));
    }

    public function testGetDotNotationDatabaseHost(): void
    {
        $host = Config::get('primaryDatabase.host');
        $this->assertNotNull($host);
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $this->assertSame('fallback', Config::get('nonExistentKey', 'fallback'));
        $this->assertNull(Config::get('nonExistentKey'));
    }

    public function testGetDotNotationMissingNestedKey(): void
    {
        $this->assertSame('default', Config::get('primaryDatabase.nonexistent', 'default'));
    }

    // ── Direct property mutation ──────────────────────────────────────────────

    public function testPropertyMutation(): void
    {
        $original = Config::$appMode;
        Config::$appMode = 'production';
        $this->assertSame('production', Config::$appMode);
        Config::$appMode = $original; // restore
    }
}
