<?php
namespace JiFramework\Core\Logger;

use JiFramework\Config\Config;
use JiFramework\Core\Utilities\DateTimeHelper;

class Logger
{
    /**
     * @var resource|null File handle for the log file.
     */
    private $logFileHandle;

    /**
     * @var string Log file path.
     */
    private $logFilePath;

    /**
     * @var array Log levels.
     */
    private $logLevels = [
        'DEBUG'     => 100,
        'INFO'      => 200,
        'NOTICE'    => 250,
        'WARNING'   => 300,
        'ERROR'     => 400,
        'CRITICAL'  => 500,
        'ALERT'     => 550,
        'EMERGENCY' => 600,
    ];

    /**
     * @var int Current log level.
     */
    private $currentLogLevel;

    /**
     * @var string Log format.
     */
    private $logFormat = '[{date}] [{level}] {message}';

    /**
     * @var int Max log file size in bytes.
     */
    private $maxFileSize;

    /**
     * @var int Max number of archived log files to keep.
     */
    private $maxFiles;

    /**
     * @var bool Whether logging is enabled.
     */
    private $logEnabled;

    /**
     * @var string Date format used in log entries.
     */
    private $dateFormat = 'Y-m-d H:i:s';

    /**
     * Constructor.
     *
     * @param string|null $logFilePath Optional log file path. If null, uses default from Config.
     */
    public function __construct($logFilePath = null)
    {
        $this->logEnabled = Config::$logEnabled;

        if (!$this->logEnabled) {
            return;
        }

        $configLogLevel        = strtoupper(Config::$logLevel);
        $this->currentLogLevel = $this->logLevels[$configLogLevel] ?? $this->logLevels['DEBUG'];
        $this->maxFileSize     = Config::$logMaxFileSize;
        $this->maxFiles        = Config::$logMaxFiles;

        $defaultLogFilePath = Config::$logFilePath . Config::$logFileName;
        $this->logFilePath  = $logFilePath ?? $defaultLogFilePath;

        $this->openLogFile();
    }

    /**
     * Open (or re-open) the log file, creating the directory if needed.
     * On failure, logging is silently disabled rather than crashing the app.
     *
     * @return void
     */
    private function openLogFile()
    {
        $logDir = dirname($this->logFilePath);

        if (!is_dir($logDir) && !@mkdir($logDir, 0755, true)) {
            trigger_error(
                '[JiFramework] Logger: cannot create log directory: ' . $logDir,
                E_USER_WARNING
            );
            $this->logEnabled = false;
            return;
        }

        if (file_exists($this->logFilePath) && filesize($this->logFilePath) >= $this->maxFileSize) {
            $this->rotateLogs();
        }

        $handle = @fopen($this->logFilePath, 'a');

        if ($handle === false) {
            trigger_error(
                '[JiFramework] Logger: cannot open log file for writing: ' . $this->logFilePath,
                E_USER_WARNING
            );
            $this->logEnabled = false;
            return;
        }

        $this->logFileHandle = $handle;
    }

    /**
     * Rotate log files.
     *
     * @return void
     */
    private function rotateLogs()
    {
        $logFile = $this->logFilePath;

        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $oldLogFile = $logFile . '.' . $i;
            $newLogFile = $logFile . '.' . ($i + 1);

            if (file_exists($oldLogFile)) {
                if ($i + 1 >= $this->maxFiles) {
                    unlink($oldLogFile);
                } else {
                    rename($oldLogFile, $newLogFile);
                }
            }
        }

        rename($logFile, $logFile . '.0');
    }

    /**
     * Log a message at the given level.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->logEnabled || !$this->logFileHandle) {
            return;
        }

        $level = strtoupper($level);
        if (!isset($this->logLevels[$level])) {
            $level = 'DEBUG';
        }

        if ($this->logLevels[$level] < $this->currentLogLevel) {
            return;
        }

        $date       = DateTimeHelper::now($this->dateFormat);
        $message    = $this->interpolateMessage($message, $context);
        $logMessage = str_replace(
            ['{date}', '{level}', '{message}'],
            [$date,    $level,    $message],
            $this->logFormat
        );

        $result = @fwrite($this->logFileHandle, $logMessage . PHP_EOL);

        if ($result === false) {
            trigger_error(
                '[JiFramework] Logger: failed to write to log file: ' . $this->logFilePath,
                E_USER_WARNING
            );
        }
    }

    /**
     * Interpolate {placeholder} tokens in the message using context values.
     *
     * @param string $message
     * @param array  $context
     * @return string
     */
    private function interpolateMessage($message, array $context = [])
    {
        if (empty($context)) {
            return $message;
        }

        foreach ($context as $key => $val) {
            if (is_bool($val)) {
                $val = $val ? 'true' : 'false';
            } elseif (is_null($val)) {
                $val = 'null';
            } elseif (!is_scalar($val)) {
                $val = json_encode($val);
            }
            $message = str_replace('{' . $key . '}', $val, $message);
        }

        return $message;
    }

    /**
     * Close the log file handle on object destruction.
     */
    public function __destruct()
    {
        if ($this->logFileHandle) {
            fclose($this->logFileHandle);
        }
    }

    /**
     * Switch to a different log file at runtime.
     *
     * @param string $logFilePath
     * @return void
     */
    public function setLogFile($logFilePath)
    {
        if (!$this->logEnabled) {
            return;
        }

        if ($this->logFileHandle) {
            fclose($this->logFileHandle);
            $this->logFileHandle = null;
        }

        $this->logFilePath = $logFilePath;
        $this->openLogFile();
    }

    // ─── Convenience methods ──────────────────────────────────────────────────

    public function debug($message, array $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log('NOTICE', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log('ALERT', $message, $context);
    }

    public function emergency($message, array $context = [])
    {
        $this->log('EMERGENCY', $message, $context);
    }
}
