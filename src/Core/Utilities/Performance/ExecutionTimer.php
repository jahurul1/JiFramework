<?php
namespace JiFramework\Core\Utilities\Performance;

class ExecutionTimer
{
    /**
     * Start time in microseconds.
     *
     * @var float
     */
    private $startTime = 0.0;

    /**
     * End time in microseconds.
     *
     * @var float
     */
    private $endTime = 0.0;

    /**
     * Elapsed time in microseconds.
     *
     * @var float
     */
    private $elapsedTime = 0.0;

    /**
     * Starts the timer.
     *
     * @return void
     */
    public function start()
    {
        $this->startTime = microtime(true);
        $this->endTime = 0.0;
        $this->elapsedTime = 0.0;
    }

    /**
     * Stops the timer and calculates the elapsed time.
     *
     * @return void
     */
    public function stop()
    {
        $this->endTime = microtime(true);
        $this->elapsedTime = $this->endTime - $this->startTime;
    }

    /**
     * Gets the elapsed time in seconds.
     *
     * @return float Elapsed time in seconds.
     */
    public function getElapsedTime()
    {
        if ($this->elapsedTime === 0.0 && $this->endTime === 0.0) {
            // Timer has not been stopped yet, calculate current elapsed time
            return microtime(true) - $this->startTime;
        }
        return $this->elapsedTime;
    }

    /**
     * Gets the elapsed time in milliseconds.
     *
     * @return float Elapsed time in milliseconds.
     */
    public function getElapsedTimeInMilliseconds()
    {
        return $this->getElapsedTime() * 1000;
    }

    /**
     * Gets the elapsed time in microseconds.
     *
     * @return float Elapsed time in microseconds.
     */
    public function getElapsedTimeInMicroseconds()
    {
        return $this->getElapsedTime() * 1000000;
    }

    /**
     * Resets the timer.
     *
     * @return void
     */
    public function reset()
    {
        $this->startTime = 0.0;
        $this->endTime = 0.0;
        $this->elapsedTime = 0.0;
    }

    /**
     * Static method to measure the execution time of a callback.
     *
     * @param callable $callback The function to execute.
     * @param mixed    ...$args  Arguments to pass to the callback.
     * @return array             An array with 'result' and 'elapsed_time'.
     */
    public static function measure(callable $callback, ...$args)
    {
        $timer = new self();
        $timer->start();
        $result = call_user_func_array($callback, $args);
        $timer->stop();
        return [
            'result'       => $result,
            'elapsed_time' => $timer->getElapsedTime(),
        ];
    }
}


