<?php
namespace JiFramework\Core\Error;

class ErrorPageHandler
{
    /**
     * @var ErrorPageHandler The singleton instance.
     */
    private static $instance;

    /**
     * @var array Default error messages.
     */
    private $defaultMessages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        405 => 'Method Not Allowed',
        408 => 'Request Timeout',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        // Add other error codes and messages as needed
    ];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Do nothing
    }

    /**
     * Get the singleton instance of ErrorPageHandler.
     *
     * @return ErrorPageHandler
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ErrorPageHandler();
        }
        return self::$instance;
    }

    /**
     * Handle an HTTP error.
     *
     * @param int $errorCode
     * @param string|null $message Custom error message.
     * @return void
     */
    public function handle($errorCode, $message = null)
    {
        http_response_code($errorCode);

        if ($message === null) {
            $message = $this->defaultMessages[$errorCode] ?? 'An error occurred';
        }

        // Determine response type (HTML or JSON)
        if ($this->isJsonRequest()) {
            $this->renderJsonError($errorCode, $message);
        } else {
            $this->renderHtmlError($errorCode, $message);
        }
        exit;
    }

    /**
     * Render an HTML error page directly.
     *
     * @param int $errorCode
     * @param string $message
     * @return void
     */
    private function renderHtmlError($errorCode, $message)
    {
        // Error page content
        $errorTitle = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $errorMessage = htmlspecialchars($this->getDetailedErrorMessage($errorCode, $message), ENT_QUOTES, 'UTF-8');

        // Output the error page
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Error {$errorCode} - {$errorTitle}</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #f9f9f9;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    text-align: center;
                }
                .container {
                    max-width: 800px;
                    margin: 50px auto;
                    padding: 30px;
                    background-color: #fff;
                    border: 1px solid #e5e5e5;
                    border-radius: 4px;
                }
                .error-code {
                    font-size: 48px;
                    font-weight: bold;
                    color: #d9534f;
                    margin: 0 0 10px 0;
                }
                .error-title {
                    font-size: 16px;
                    margin: 0 0 20px 0;
                    color: #5a5a5a;
                }
                .error-message {
                    font-size: 16px;
                    line-height: 1.5;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                    color: #333;
                    background-color: #f7f7f7;
                    padding: 15px;
                    border-radius: 4px;
                    border: 1px solid #e1e1e1;
                    overflow: auto;
                    max-height: 400px;
                }
                .home-button {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background-color: #337ab7;
                    color: #fff;
                    text-decoration: none;
                    border-radius: 4px;
                }
                .home-button:hover {
                    background-color: #286090;
                }
                @media (max-width: 600px) {
                    .container {
                        margin: 20px;
                        padding: 20px;
                    }
                    .error-code {
                        font-size: 36px;
                    }
                    .error-title {
                        font-size: 20px;
                    }
                    .error-message {
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-code">Error {$errorCode}</div>
                <div class="error-title">{$errorTitle}</div>
                <div class="error-message">{$errorMessage}</div>
                <a href="/" class="home-button">Return to Home</a>
            </div>
        </body>
        </html>
        HTML;
    }


    /**
     * Render a JSON error response.
     *
     * @param int $errorCode
     * @param string $message
     * @return void
     */
    private function renderJsonError($errorCode, $message)
    {
        $errorData = [
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        header('Content-Type: application/json');
        echo json_encode($errorData);
    }

    /**
     * Check if the request expects a JSON response.
     *
     * @return bool
     */
    private function isJsonRequest()
    {
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }

        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get a detailed error message.
     *
     * @param int $errorCode
     * @param string $message
     * @return string
     */
    private function getDetailedErrorMessage($errorCode, $message)
    {
        return $this->defaultMessages[$errorCode] ?? 'An error occurred';
    }
}


