<?php

namespace JiFramework\Tests\Unit;

use JiFramework\Core\Utilities\ExecutionTimer;
use JiFramework\Tests\TestCase;

class ExecutionTimerTest extends TestCase
{
    private ExecutionTimer $timer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timer = new ExecutionTimer();
    }

    public function testStartAndIsRunning(): void
    {
        $this->assertFalse($this->timer->isRunning());
        $this->timer->start();
        $this->assertTrue($this->timer->isRunning());
    }

    public function testStopStopsTimer(): void
    {
        $this->timer->start();
        $this->timer->stop();
        $this->assertFalse($this->timer->isRunning());
    }

    public function testGetElapsedTimeAfterStop(): void
    {
        $this->timer->start();
        usleep(10000); // 10 ms
        $this->timer->stop();

        $elapsed = $this->timer->getElapsedTime();
        $this->assertGreaterThan(0.0, $elapsed);
    }

    public function testGetElapsedTimeInMilliseconds(): void
    {
        $this->timer->start();
        usleep(10000); // 10 ms
        $this->timer->stop();

        $ms = $this->timer->getElapsedTimeInMilliseconds();
        $this->assertGreaterThan(0.0, $ms);
        $this->assertGreaterThan($this->timer->getElapsedTime(), $ms); // ms > seconds
    }

    public function testGetElapsedTimeInMicroseconds(): void
    {
        $this->timer->start();
        usleep(10000);
        $this->timer->stop();

        $us = $this->timer->getElapsedTimeInMicroseconds();
        $this->assertGreaterThan(0.0, $us);
    }

    public function testReset(): void
    {
        $this->timer->start();
        $this->timer->stop();
        $this->timer->reset();
        $this->assertFalse($this->timer->isRunning());
        $this->assertSame(0.0, $this->timer->getElapsedTime());
    }

    public function testRestart(): void
    {
        $this->timer->start();
        usleep(5000);
        $this->timer->restart();
        $this->assertTrue($this->timer->isRunning());
        // After restart, elapsed should be very small
        $this->timer->stop();
        $this->assertLessThan(0.1, $this->timer->getElapsedTime());
    }

    public function testMeasureCallable(): void
    {
        $elapsed = ExecutionTimer::measure(function () {
            usleep(10000); // 10 ms
        });
        $this->assertGreaterThan(0.0, $elapsed);
    }

    public function testMeasurePassesArguments(): void
    {
        $result = null;
        ExecutionTimer::measure(function (int $n) use (&$result) {
            $result = $n * 2;
        }, 21);
        $this->assertSame(42, $result);
    }
}
