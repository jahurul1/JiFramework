<?php
namespace JiFramework\Exceptions;

/**
 * Thrown when validation fails (via Validator::throw() or checkOrFail())
 * or when an unknown rule name is used.
 *
 * Use getErrors() to retrieve the full structured errors array.
 */
class ValidationException extends \RuntimeException
{
    /** @var array ['field' => ['message', ...]] */
    protected array $errors;

    public function __construct(string $message = 'Validation failed.', array $errors = [], int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Return the structured validation errors.
     *
     * @return array ['field' => ['message', ...]]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
