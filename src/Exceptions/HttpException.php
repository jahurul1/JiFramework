<?php
namespace JiFramework\Exceptions;

/**
 * Thrown for HTTP-level errors such as 404 Not Found or 405 Method Not Allowed.
 * The status code is stored and retrievable via getStatusCode().
 */
class HttpException extends \RuntimeException
{
    protected int $statusCode;

    public function __construct(int $statusCode, string $message = '', \Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
