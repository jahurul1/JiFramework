<?php
namespace JiFramework\Core\Error;

use JiFramework\Config\Config;
use JiFramework\Core\Logger\Logger;
use JiFramework\Core\Error\ErrorPageHandler;
use Throwable;

class ErrorHandler
{
    /**
     * @var Logger Instance of Logger.
     */
    private $logger;

    /**
     * @var ErrorPageHandler Instance of ErrorPageHandler.
     */
    private $errorPageHandler;

    /**
     * @var string Application environment ('development' or 'production').
     */
    private $environment;

    /**
     * Constructor.
     *
     * @param Logger $logger
     * @param ErrorPageHandler $errorPageHandler
     */
    public function __construct(Logger $logger, ErrorPageHandler $errorPageHandler)
    {
        $this->logger = $logger;
        $this->errorPageHandler = $errorPageHandler;

        // Initialize configuration values
        $this->environment = Config::APP_MODE;
    }

    /**
     * Register error and exception handlers.
     *
     * @return void
     */
    public function register(): void
    {
        // Set custom error handler
        set_error_handler([$this, 'handleError']);

        // Set custom exception handler
        set_exception_handler([$this, 'handleException']);

        // Set shutdown handler to catch fatal errors
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Error handler function.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return bool
     * @throws \ErrorException
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        // Convert error to ErrorException
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Exception handler function.
     *
     * @param Throwable $exception
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        // Log the exception
        $this->logger->error('Unhandled Exception: {message}'.PHP_EOL.'{exception}'.PHP_EOL, [
            'message' => $exception->getMessage(),
            'exception' => $this->formatException($exception),
        ]);

        // Determine the appropriate HTTP status code
        $statusCode = $this->getStatusCode($exception);

        // Display error page
        if ($this->environment === 'development') {
            // In development, show detailed error
            $message = $this->formatException($exception);
        } else {
            // In production, show generic message
            $message = 'An unexpected error occurred. Please try again later.';
        }

        $this->errorPageHandler->handle($statusCode, $message);
    }

    /**
     * Shutdown handler function to catch fatal errors.
     *
     * @return void
     */
    public function handleShutdown()
    {
        $error = error_get_last();

        if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            // Fatal error occurred
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            $this->handleException($exception);
        }
    }

    /**
     * Get appropriate HTTP status code based on exception.
     *
     * @param Throwable $exception
     * @return int
     */
    private function getStatusCode(Throwable $exception)
    {
        // You can map different exception types to different status codes
        // For simplicity, we'll return 500 for all exceptions
        return 500;
    }

    /**
     * Format exception details.
     *
     * @param Throwable $exception
     * @return string
     */
    private function formatException(Throwable $exception)
    {
        $message = 'Exception: ' . get_class($exception) . "\n";
        $message .= 'Message: ' . $exception->getMessage() . "\n";
        $message .= 'File: ' . $exception->getFile() . ' (Line ' . $exception->getLine() . ")\n";
        $message .= "Stack trace:\n" . $exception->getTraceAsString();

        return $message;
    }
}


