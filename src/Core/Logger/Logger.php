<?php
namespace JiFramework\Core\Logger;

use JiFramework\Config\Config;
use JiFramework\Core\Utilities\DateTimeHelper;
use Exception;

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
     * @var int Max number of log files to keep.
     */
    private $maxFiles;

    /**
     * @var bool Whether logging is enabled.
     */
    private $logEnabled;

    /**
     * @var string Date format in logs.
     */
    private $dateFormat = 'Y-m-d H:i:s'; // Defined directly in the class

    /**
     * Constructor.
     *
     * @param string|null $logFilePath Optional log file path. If null, uses default from Config.
     * @throws Exception
     */
    public function __construct($logFilePath = null)
    {
        // Initialize configuration properties
        $this->logEnabled = Config::LOG_ENABLED;

        if (!$this->logEnabled) {
            return;
        }

        // Set the current log level
        $configLogLevel = strtoupper(Config::LOG_LEVEL);
        $this->currentLogLevel = $this->logLevels[$configLogLevel] ?? $this->logLevels['DEBUG'];

        // Determine the log file path
        $defaultLogFilePath = Config::LOG_FILE_PATH . Config::LOG_FILE_NAME;
        $this->logFilePath = $logFilePath ?? $defaultLogFilePath;

        // Set max file size and max files
        $this->maxFileSize = Config::LOG_MAX_FILE_SIZE;
        $this->maxFiles = Config::LOG_MAX_FILES;

        // Ensure log directory exists
        $logDir = dirname($this->logFilePath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Open the log file
        $this->openLogFile();
    }

    /**
     * Open the log file.
     *
     * @return void
     * @throws Exception
     */
    private function openLogFile()
    {
        // Rotate log files if necessary
        if(file_exists($this->logFilePath) && filesize($this->logFilePath) >= $this->maxFileSize) {
            $this->rotateLogs();
        }

        // Open the log file
        $this->logFileHandle = fopen($this->logFilePath, 'a');
        if (!$this->logFileHandle) {
            throw new Exception('Could not open log file for writing.');
        }
    }

    /**
     * Rotate log files.
     *
     * @return void
     */
    private function rotateLogs()
    {
        $logFile = $this->logFilePath;

        // Delete oldest log file if max files exceeded
        for ($i = $this->maxFiles - 1; $i >= 0; $i--) {
            $oldLogFile = $logFile . '.' . $i;
            $newLogFile = $logFile . '.' . ($i + 1);

            if (file_exists($oldLogFile)) {
                if ($i + 1 >= $this->maxFiles) {
                    unlink($oldLogFile); // Delete oldest log file
                } else {
                    rename($oldLogFile, $newLogFile); // Rename log files
                }
            }
        }

        // Rename current log file
        rename($logFile, $logFile . '.0');
    }

    /**
     * Log a message with a given level.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->logEnabled) {
            return;
        }

        $level = strtoupper($level);
        if (!isset($this->logLevels[$level])) {
            $level = 'DEBUG';
        }

        if ($this->logLevels[$level] < $this->currentLogLevel) {
            return;
        }

        $date = DateTimeHelper::getCurrentDatetime($this->dateFormat);
        $message = $this->interpolateMessage($message, $context);
        $logMessage = str_replace(
            ['{date}', '{level}', '{message}'],
            [$date, $level, $message],
            $this->logFormat
        );

        fwrite($this->logFileHandle, $logMessage . PHP_EOL);
    }

    /**
     * Interpolate context values into the message placeholders.
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolateMessage($message, array $context = [])
    {
        // Replace placeholders in message
        if (!empty($context)) {
            foreach ($context as $key => $val) {
                // Ensure that the value is a string
                if (!is_scalar($val)) {
                    $val = json_encode($val);
                }
                $message = str_replace('{' . $key . '}', $val, $message);
            }
        }
        return $message;
    }

    /**
     * Close the log file when the object is destroyed.
     */
    public function __destruct()
    {
        if ($this->logFileHandle) {
            fclose($this->logFileHandle);
        }
    }

    /**
     * Set a custom log file path.
     *
     * @param string $logFilePath
     * @throws Exception
     */
    public function setLogFile($logFilePath)
    {
        if ($this->logFileHandle) {
            fclose($this->logFileHandle);
        }

        $this->logFilePath = $logFilePath;

        // Ensure log directory exists
        $logDir = dirname($this->logFilePath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Open the new log file
        $this->openLogFile();
    }

    /**
     * Log debug message.
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log info message.
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log warning message.
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log error message.
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log critical message.
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = [])
    {
        $this->log('CRITICAL', $message, $context);
    }
}


