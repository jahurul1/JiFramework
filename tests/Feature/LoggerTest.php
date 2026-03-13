<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Config\Config;
use JiFramework\Core\Logger\Logger;
use JiFramework\Tests\TestCase;

class LoggerTest extends TestCase
{
    private Logger $logger;
    private string $logDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = $this->tempDir . 'logs/';
        mkdir($this->logDir, 0755, true);

        Config::$logEnabled     = true;
        Config::$logLevel       = 'DEBUG';
        Config::$logFilePath    = $this->logDir;
        Config::$logFileName    = 'test.log';
        Config::$logMaxFileSize = 5242880;
        Config::$logMaxFiles    = 5;

        $this->logger = new Logger($this->logDir . 'test.log');
    }

    protected function tearDown(): void
    {
        Config::$logEnabled = false;
        parent::tearDown();
    }

    private function readLog(): string
    {
        $file = $this->logDir . 'test.log';
        return file_exists($file) ? file_get_contents($file) : '';
    }

    // ── Basic log levels ──────────────────────────────────────────────────────

    public function testDebugWritesToFile(): void
    {
        $this->logger->debug('debug message');
        $this->assertStringContainsString('[DEBUG]', $this->readLog());
        $this->assertStringContainsString('debug message', $this->readLog());
    }

    public function testInfoWritesToFile(): void
    {
        $this->logger->info('info message');
        $this->assertStringContainsString('[INFO]', $this->readLog());
    }

    public function testWarningWritesToFile(): void
    {
        $this->logger->warning('warn message');
        $this->assertStringContainsString('[WARNING]', $this->readLog());
    }

    public function testErrorWritesToFile(): void
    {
        $this->logger->error('error message');
        $this->assertStringContainsString('[ERROR]', $this->readLog());
    }

    public function testCriticalWritesToFile(): void
    {
        $this->logger->critical('critical message');
        $this->assertStringContainsString('[CRITICAL]', $this->readLog());
    }

    public function testNoticeWritesToFile(): void
    {
        $this->logger->notice('notice message');
        $this->assertStringContainsString('[NOTICE]', $this->readLog());
    }

    public function testAlertWritesToFile(): void
    {
        $this->logger->alert('alert message');
        $this->assertStringContainsString('[ALERT]', $this->readLog());
    }

    public function testEmergencyWritesToFile(): void
    {
        $this->logger->emergency('emergency message');
        $this->assertStringContainsString('[EMERGENCY]', $this->readLog());
    }

    // ── Context interpolation ─────────────────────────────────────────────────

    public function testContextInterpolation(): void
    {
        $this->logger->info('User {user} logged in', ['user' => 'Alice']);
        $this->assertStringContainsString('Alice', $this->readLog());
    }

    public function testBooleanContextValue(): void
    {
        $this->logger->info('Flag is {flag}', ['flag' => true]);
        $this->assertStringContainsString('true', $this->readLog());
    }

    public function testNullContextValue(): void
    {
        $this->logger->info('Value is {val}', ['val' => null]);
        $this->assertStringContainsString('null', $this->readLog());
    }

    // ── Log level filtering ───────────────────────────────────────────────────

    public function testLogLevelFilteringIgnoresLowerLevel(): void
    {
        Config::$logLevel = 'ERROR';
        $logger = new Logger($this->logDir . 'filtered.log');
        $logger->debug('this should not appear');

        $file = $this->logDir . 'filtered.log';
        $content = file_exists($file) ? file_get_contents($file) : '';
        $this->assertStringNotContainsString('this should not appear', $content);
    }

    // ── Disabled logger ───────────────────────────────────────────────────────

    public function testDisabledLoggerDoesNotWrite(): void
    {
        Config::$logEnabled = false;
        $logger = new Logger($this->logDir . 'disabled.log');
        $logger->error('should not be written');

        $file = $this->logDir . 'disabled.log';
        $this->assertFalse(file_exists($file));

        Config::$logEnabled = true;
    }

    // ── Log format ────────────────────────────────────────────────────────────

    public function testLogEntryContainsTimestamp(): void
    {
        $this->logger->info('timestamp test');
        $content = $this->readLog();
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }

    // ── setLogFile() ─────────────────────────────────────────────────────────

    public function testSetLogFile(): void
    {
        $newFile = $this->logDir . 'new.log';
        $this->logger->setLogFile($newFile);
        $this->logger->info('written to new file');
        $this->assertFileExists($newFile);
        $this->assertStringContainsString('written to new file', file_get_contents($newFile));
    }
}
