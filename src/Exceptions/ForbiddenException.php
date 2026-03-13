<?php
namespace JiFramework\Exceptions;

/**
 * Thrown when a user lacks permission to perform an action.
 * Automatically maps to HTTP 403.
 */
class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden', \Throwable $previous = null)
    {
        parent::__construct(403, $message, $previous);
    }
}
