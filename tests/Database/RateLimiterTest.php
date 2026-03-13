<?php

namespace JiFramework\Tests\Database;

use JiFramework\Config\Config;
use JiFramework\Core\Security\RateLimiter;
use JiFramework\Core\Utilities\Request;
use JiFramework\Tests\TestCase;

/**
 * RateLimiter tests use an isolated SQLite database in the temp directory.
 */
class RateLimiterTest extends TestCase
{
    private RateLimiter $limiter;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR']    = '1.2.3.4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';

        // Point SQLite to an isolated temp file
        Config::$rateLimitEnabled      = true;
        Config::$rateLimitRequests     = 5;
        Config::$rateLimitTimeWindow   = 60;
        Config::$rateLimitBanEnabled   = true;
        Config::$rateLimitBanDuration  = 3600;
        Config::$rateLimitDatabasePath = $this->tempDir . 'rate_limit.db';

        $this->request = new Request();
        $this->limiter = new RateLimiter($this->request);
    }

    protected function tearDown(): void
    {
        Config::$rateLimitEnabled = false;
        parent::tearDown();
    }

    // ── getRemainingRequests() ────────────────────────────────────────────────

    public function testRemainingRequestsInitially(): void
    {
        $remaining = $this->limiter->getRemainingRequests('1.2.3.4');
        $this->assertSame(5, $remaining);
    }

    // ── isBannedIp() ─────────────────────────────────────────────────────────

    public function testIpNotBannedInitially(): void
    {
        $this->assertFalse($this->limiter->isBannedIp('1.2.3.4'));
    }

    // ── banIp() / isBannedIp() / getBanInfo() ────────────────────────────────

    public function testBanIp(): void
    {
        $this->limiter->banIp('5.5.5.5', 3600);
        $this->assertTrue($this->limiter->isBannedIp('5.5.5.5'));
    }

    public function testGetBanInfoForBannedIp(): void
    {
        $this->limiter->banIp('6.6.6.6', 3600);
        $info = $this->limiter->getBanInfo('6.6.6.6');
        $this->assertIsArray($info);
        // getBanInfo returns: ['ip', 'ban_expires', 'seconds_remaining']
        $this->assertArrayHasKey('ip', $info);
        $this->assertArrayHasKey('ban_expires', $info);
        $this->assertArrayHasKey('seconds_remaining', $info);
        $this->assertSame('6.6.6.6', $info['ip']);
    }

    public function testGetBanInfoForUnbannedIp(): void
    {
        $this->assertNull($this->limiter->getBanInfo('9.9.9.9'));
    }

    // ── unbanIp() ────────────────────────────────────────────────────────────

    public function testUnbanIp(): void
    {
        $this->limiter->banIp('7.7.7.7', 3600);
        $this->assertTrue($this->limiter->isBannedIp('7.7.7.7'));

        $this->limiter->unbanIp('7.7.7.7');
        $this->assertFalse($this->limiter->isBannedIp('7.7.7.7'));
    }

    // ── resetIp() ────────────────────────────────────────────────────────────

    public function testResetIp(): void
    {
        // resetIp on an unknown IP should return true (no error)
        $result = $this->limiter->resetIp('10.0.0.1');
        $this->assertTrue($result);
    }

    // ── enforceRateLimit() — disabled ────────────────────────────────────────

    public function testEnforceRateLimitDisabledDoesNothing(): void
    {
        Config::$rateLimitEnabled = false;
        $limiter = new RateLimiter($this->request);
        $limiter->enforceRateLimit(); // must not throw
        $this->assertTrue(true);
    }

    // ── enforceRateLimit() — under limit ─────────────────────────────────────

    public function testEnforceRateLimitUnderLimitDoesNotThrow(): void
    {
        // 5 requests allowed — call enforceRateLimit once, should be fine
        $this->limiter->enforceRateLimit();
        $this->assertTrue(true);
    }
}
