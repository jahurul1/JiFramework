<?php
namespace JiFramework\Core\Utilities;

class ExecutionTimer
{
    /**
     * Start time recorded by microtime(true).
     *
     * @var float
     */
    private float $startTime = 0.0;

    /**
     * End time recorded by microtime(true).
     *
     * @var float
     */
    private float $endTime = 0.0;

    /**
     * Elapsed time in seconds (set when stop() is called).
     *
     * @var float
     */
    private float $elapsedTime = 0.0;

    /**
     * Whether the timer is currently running (started but not stopped).
     *
     * @var bool
     */
    private bool $running = false;

    // =========================================================================
    // Control
    // =========================================================================

    /**
     * Start (or restart) the timer.
     *
     * Resets any previous measurements before starting.
     *
     * @return void
     */
    public function start(): void
    {
        $this->startTime   = microtime(true);
        $this->endTime     = 0.0;
        $this->elapsedTime = 0.0;
        $this->running     = true;
    }

    /**
     * Stop the timer and record the elapsed time.
     *
     * Does nothing when the timer has not been started.
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->endTime     = microtime(true);
        $this->elapsedTime = $this->endTime - $this->startTime;
        $this->running     = false;
    }

    /**
     * Reset the timer to its initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->startTime   = 0.0;
        $this->endTime     = 0.0;
        $this->elapsedTime = 0.0;
        $this->running     = false;
    }

    /**
     * Reset and immediately start the timer.
     *
     * Equivalent to calling reset() then start().
     *
     * @return void
     */
    public function restart(): void
    {
        $this->start();
    }

    // =========================================================================
    // State
    // =========================================================================

    /**
     * Check whether the timer is currently running (started but not stopped).
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    // =========================================================================
    // Reading
    // =========================================================================

    /**
     * Get the elapsed time in seconds.
     *
     * When the timer is still running, returns the live elapsed time since
     * start() was called. When stopped, returns the recorded duration.
     * Returns 0.0 when the timer has never been started.
     *
     * @return float Elapsed time in seconds.
     */
    public function getElapsedTime(): float
    {
        if (!$this->running && $this->startTime === 0.0) {
            return 0.0;
        }

        if ($this->running) {
            return microtime(true) - $this->startTime;
        }

        return $this->elapsedTime;
    }

    /**
     * Get the elapsed time in milliseconds.
     *
     * @return float Elapsed time in milliseconds.
     */
    public function getElapsedTimeInMilliseconds(): float
    {
        return $this->getElapsedTime() * 1000;
    }

    /**
     * Get the elapsed time in microseconds.
     *
     * @return float Elapsed time in microseconds.
     */
    public function getElapsedTimeInMicroseconds(): float
    {
        return $this->getElapsedTime() * 1_000_000;
    }

    // =========================================================================
    // Static utility
    // =========================================================================

    /**
     * Measure the execution time of a callable.
     *
     * @param callable $callback The function to execute.
     * @param mixed    ...$args  Arguments to pass to the callback.
     * @return array{result: mixed, elapsed_time: float, elapsed_ms: float}
     */
    public static function measure(callable $callback, mixed ...$args): array
    {
        $timer = new self();
        $timer->start();
        $result = $callback(...$args);
        $timer->stop();

        return [
            'result'       => $result,
            'elapsed_time' => $timer->getElapsedTime(),
            'elapsed_ms'   => $timer->getElapsedTimeInMilliseconds(),
        ];
    }
}
