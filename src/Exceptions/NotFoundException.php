<?php
namespace JiFramework\Exceptions;

/**
 * Thrown when a requested resource cannot be found.
 * Automatically maps to HTTP 404.
 */
class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', \Throwable $previous = null)
    {
        parent::__construct(404, $message, $previous);
    }
}
