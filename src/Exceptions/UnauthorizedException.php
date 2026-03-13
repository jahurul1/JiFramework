<?php
namespace JiFramework\Exceptions;

/**
 * Thrown when a request requires authentication.
 * Automatically maps to HTTP 401.
 */
class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', \Throwable $previous = null)
    {
        parent::__construct(401, $message, $previous);
    }
}
