<?php
namespace JiFramework\Core\Utilities;

use JiFramework\Exceptions\ValidationException;

class Validator
{
    /** @var array The data being validated. */
    protected array $data = [];

    /** @var array The validation rules. */
    protected array $rules = [];

    /** @var array Collected errors: ['field' => ['msg', ...]] */
    protected array $errors = [];

    /** @var array Custom error messages. */
    protected array $customMessages = [];

    /** @var array Custom rule callbacks. */
    protected array $customRules = [];

    /** @var bool Stop validating a field after its first failure. */
    protected bool $stopOnFirstFailure = false;

    // =========================================================================
    // Primary API
    // =========================================================================

    /**
     * Bind data and rules, run validation, and return this instance for fluent use.
     *
     * @param array $data     The data to validate, e.g. $_POST or any associative array.
     * @param array $rules    Rules map: ['field' => 'required|email|max:255']
     * @param array $messages Optional custom messages: ['field.rule' => 'message'] or ['rule' => 'message']
     */
    public function make(array $data, array $rules, array $messages = []): static
    {
        $this->data           = $data;
        $this->rules          = $rules;
        $this->errors         = [];
        $this->customMessages = array_merge($this->customMessages, $messages);

        $this->runValidation();

        return $this;
    }

    /**
     * Return true if all rules passed.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Return true if any rule failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Return all collected errors: ['field' => ['message', ...]]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Return the first error message for each field (or for a specific field).
     *
     * With no argument: returns ['field' => 'first message', ...]
     * With a field name: returns that field's first message, or empty string if none.
     */
    public function first(?string $field = null): string|array
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? '';
        }

        $first = [];
        foreach ($this->errors as $f => $messages) {
            $first[$f] = $messages[0];
        }
        return $first;
    }

    /**
     * Throw a ValidationException if validation failed.
     * Passes the full errors array as the exception's context.
     */
    public function throw(): void
    {
        if ($this->fails()) {
            throw new ValidationException(
                'Validation failed.',
                $this->errors
            );
        }
    }

    /**
     * Validate a single value against a set of rules. Returns true/false.
     * Does not affect the instance's stored data or errors.
     *
     * @param mixed        $value The value to test.
     * @param string|array $rules Rule string or array.
     */
    public function check(mixed $value, string|array $rules): bool
    {
        $saved = [$this->data, $this->rules, $this->errors];

        $this->data   = ['_value' => $value];
        $this->rules  = ['_value' => $rules];
        $this->errors = [];
        $this->runValidation();
        $result = empty($this->errors);

        [$this->data, $this->rules, $this->errors] = $saved;

        return $result;
    }

    /**
     * Like check() but throws a ValidationException on failure.
     */
    public function checkOrFail(mixed $value, string|array $rules): void
    {
        if (!$this->check($value, $rules)) {
            throw new ValidationException('Validation failed.', $this->errors);
        }
    }

    /**
     * Stop validating remaining rules for a field after its first failure.
     */
    public function stopOnFirstFailure(bool $stop = true): static
    {
        $this->stopOnFirstFailure = $stop;
        return $this;
    }

    /**
     * Register a custom validation rule.
     *
     * The callback receives ($field, $value, $params, $data) and must return bool.
     */
    public function addRule(string $ruleName, callable $callback): static
    {
        $this->customRules[$ruleName] = $callback;
        return $this;
    }

    /**
     * Merge custom error messages.
     *
     * Keys can be 'field.rule' (field-specific) or just 'rule' (global override).
     */
    public function addMessages(array $messages): static
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
        return $this;
    }

    // =========================================================================
    // Internal engine
    // =========================================================================

    /**
     * Run all rules against all fields and populate $this->errors.
     */
    protected function runValidation(): void
    {
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->getValue($field);

            $fieldRules = is_string($fieldRules)
                ? explode('|', $fieldRules)
                : (array) $fieldRules;

            // If 'nullable' is present and value is empty, skip all other rules.
            if (in_array('nullable', $fieldRules, true) && $this->isEmpty($value)) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                [$ruleName, $params] = $this->parseRule($rule);

                $passed = $this->applyRule($field, $value, $ruleName, $params);

                if (!$passed) {
                    $this->addError($field, $ruleName, $params);
                    if ($this->stopOnFirstFailure) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Parse a rule string like 'min:8' into ['min', ['8']].
     */
    protected function parseRule(string $rule): array
    {
        if (strpos($rule, ':') === false) {
            return [$rule, []];
        }

        [$name, $paramString] = explode(':', $rule, 2);
        return [$name, explode(',', $paramString)];
    }

    /**
     * Apply a single rule to a field value. Returns bool.
     */
    protected function applyRule(string $field, mixed $value, string $ruleName, array $params): bool
    {
        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            return (bool) $this->$method($field, $value, $params);
        }

        if (isset($this->customRules[$ruleName]) && is_callable($this->customRules[$ruleName])) {
            return (bool) call_user_func(
                $this->customRules[$ruleName],
                $field, $value, $params, $this->data
            );
        }

        throw new ValidationException("Validation rule '{$ruleName}' does not exist.");
    }

    /**
     * Retrieve a field value, supporting dot notation for nested arrays.
     */
    protected function getValue(string $field): mixed
    {
        $keys  = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Check if a value is considered empty (null, '', or []).
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Add a formatted error message for a field + rule.
     */
    protected function addError(string $field, string $rule, array $params = []): void
    {
        $this->errors[$field][] = $this->buildMessage($field, $rule, $params);
    }

    /**
     * Build the error message for a field + rule, applying placeholders.
     */
    protected function buildMessage(string $field, string $rule, array $params): string
    {
        $key = "{$field}.{$rule}";

        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            $defaults = $this->defaultMessages();
            $message  = $defaults[$rule] ?? 'The :field field is invalid.';
        }

        $label   = ucfirst(str_replace(['_', '.'], ' ', $field));
        $message = str_replace(':field', $label, $message);
        $message = str_replace(':values', implode(', ', $params), $message);

        foreach ($params as $i => $param) {
            $message = str_replace(":param{$i}", $param, $message);
        }

        return $message;
    }

    /**
     * Default error messages for all built-in rules.
     */
    protected function defaultMessages(): array
    {
        return [
            'required'       => 'The :field field is required.',
            'nullable'       => '',
            'email'          => 'The :field field must be a valid email address.',
            'min'            => 'The :field field must be at least :param0 characters.',
            'max'            => 'The :field field must not exceed :param0 characters.',
            'size'           => 'The :field field must be exactly :param0 characters.',
            'numeric'        => 'The :field field must be a number.',
            'integer'        => 'The :field field must be an integer.',
            'float'          => 'The :field field must be a decimal number.',
            'boolean'        => 'The :field field must be true or false.',
            'alpha'          => 'The :field field may only contain letters.',
            'alphaNum'       => 'The :field field may only contain letters and numbers.',
            'alphaDash'      => 'The :field field may only contain letters, numbers, dashes, and underscores.',
            'regex'          => 'The :field field format is invalid.',
            'date'           => 'The :field field must be a valid date.',
            'dateFormat'     => 'The :field field must match the format :param0.',
            'before'         => 'The :field field must be a date before :param0.',
            'after'          => 'The :field field must be a date after :param0.',
            'url'            => 'The :field field must be a valid URL.',
            'ip'             => 'The :field field must be a valid IP address.',
            'ipv4'           => 'The :field field must be a valid IPv4 address.',
            'ipv6'           => 'The :field field must be a valid IPv6 address.',
            'in'             => 'The :field field must be one of: :values.',
            'notIn'          => 'The :field field must not be one of: :values.',
            'confirmed'      => 'The :field field confirmation does not match.',
            'same'           => 'The :field field must match :param0.',
            'different'      => 'The :field field must be different from :param0.',
            'minValue'       => 'The :field field must be at least :param0.',
            'maxValue'       => 'The :field field must not exceed :param0.',
            'between'        => 'The :field field must be between :param0 and :param1.',
            'array'          => 'The :field field must be an array.',
            'minCount'       => 'The :field field must contain at least :param0 items.',
            'maxCount'       => 'The :field field must contain at most :param0 items.',
            'json'           => 'The :field field must be valid JSON.',
            'accepted'       => 'The :field field must be accepted.',
            'declined'       => 'The :field field must be declined.',
            'uuid'           => 'The :field field must be a valid UUID.',
            'slug'           => 'The :field field may only contain lowercase letters, numbers, and hyphens.',
            'phone'          => 'The :field field must be a valid phone number.',
            'strongPassword' => 'The :field field must be at least 8 characters and include uppercase, lowercase, a number, and a special character.',
        ];
    }

    // =========================================================================
    // Built-in validation rules
    // =========================================================================

    // ── Presence ─────────────────────────────────────────────────────────────

    protected function validateRequired(string $field, mixed $value, array $params): bool
    {
        return !$this->isEmpty($value);
    }

    // ── Type checks ──────────────────────────────────────────────────────────

    protected function validateNumeric(string $field, mixed $value, array $params): bool
    {
        return is_numeric($value);
    }

    protected function validateInteger(string $field, mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateFloat(string $field, mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    protected function validateBoolean(string $field, mixed $value, array $params): bool
    {
        return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'off', 'yes', 'no'], true);
    }

    protected function validateArray(string $field, mixed $value, array $params): bool
    {
        return is_array($value);
    }

    // ── String format ─────────────────────────────────────────────────────────

    protected function validateEmail(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateUrl(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateIp(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function validateIpv4(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    protected function validateIpv6(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    protected function validateAlpha(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && $value !== '' && preg_match('/^\p{L}+$/u', $value) === 1;
    }

    protected function validateAlphaNum(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && $value !== '' && preg_match('/^[\p{L}\p{N}]+$/u', $value) === 1;
    }

    protected function validateAlphaDash(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && $value !== '' && preg_match('/^[\p{L}\p{N}_\-]+$/u', $value) === 1;
    }

    protected function validateJson(string $field, mixed $value, array $params): bool
    {
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function validateUuid(string $field, mixed $value, array $params): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    protected function validateSlug(string $field, mixed $value, array $params): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }

    protected function validatePhone(string $field, mixed $value, array $params): bool
    {
        return is_string($value)
            && preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', trim($value)) === 1;
    }

    protected function validateRegex(string $field, mixed $value, array $params): bool
    {
        $pattern = $params[0] ?? '';
        if ($pattern === '') {
            return false;
        }
        $result = @preg_match($pattern, (string) $value);
        return $result === 1;
    }

    protected function validateStrongPassword(string $field, mixed $value, array $params): bool
    {
        if (!is_string($value) || mb_strlen($value, 'UTF-8') < 8) {
            return false;
        }
        return preg_match('/[A-Z]/', $value) === 1
            && preg_match('/[a-z]/', $value) === 1
            && preg_match('/[0-9]/', $value) === 1
            && preg_match('/[\W_]/', $value) === 1;
    }

    // ── String length ─────────────────────────────────────────────────────────

    protected function validateMin(string $field, mixed $value, array $params): bool
    {
        $min = (int) ($params[0] ?? 0);
        return mb_strlen((string) $value, 'UTF-8') >= $min;
    }

    protected function validateMax(string $field, mixed $value, array $params): bool
    {
        $max = isset($params[0]) ? (int) $params[0] : PHP_INT_MAX;
        return mb_strlen((string) $value, 'UTF-8') <= $max;
    }

    protected function validateSize(string $field, mixed $value, array $params): bool
    {
        $size = (int) ($params[0] ?? 0);
        return mb_strlen((string) $value, 'UTF-8') === $size;
    }

    // ── Numeric range ─────────────────────────────────────────────────────────

    protected function validateMinValue(string $field, mixed $value, array $params): bool
    {
        return is_numeric($value) && (float) $value >= (float) ($params[0] ?? 0);
    }

    protected function validateMaxValue(string $field, mixed $value, array $params): bool
    {
        return is_numeric($value) && (float) $value <= (float) ($params[0] ?? PHP_INT_MAX);
    }

    protected function validateBetween(string $field, mixed $value, array $params): bool
    {
        if (!is_numeric($value) || count($params) < 2) {
            return false;
        }
        $v = (float) $value;
        return $v >= (float) $params[0] && $v <= (float) $params[1];
    }

    // ── Array size ────────────────────────────────────────────────────────────

    protected function validateMinCount(string $field, mixed $value, array $params): bool
    {
        return is_array($value) && count($value) >= (int) ($params[0] ?? 0);
    }

    protected function validateMaxCount(string $field, mixed $value, array $params): bool
    {
        return is_array($value) && count($value) <= (int) ($params[0] ?? PHP_INT_MAX);
    }

    // ── Inclusion / exclusion ─────────────────────────────────────────────────

    protected function validateIn(string $field, mixed $value, array $params): bool
    {
        return in_array((string) $value, $params, true);
    }

    protected function validateNotIn(string $field, mixed $value, array $params): bool
    {
        return !in_array((string) $value, $params, true);
    }

    protected function validateAccepted(string $field, mixed $value, array $params): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    protected function validateDeclined(string $field, mixed $value, array $params): bool
    {
        return in_array($value, [false, 0, '0', 'false', 'no', 'off'], true);
    }

    // ── Cross-field ───────────────────────────────────────────────────────────

    protected function validateConfirmed(string $field, mixed $value, array $params): bool
    {
        return $value === $this->getValue($field . '_confirmation');
    }

    protected function validateSame(string $field, mixed $value, array $params): bool
    {
        $otherField = $params[0] ?? '';
        return $value === $this->getValue($otherField);
    }

    protected function validateDifferent(string $field, mixed $value, array $params): bool
    {
        $otherField = $params[0] ?? '';
        return $value !== $this->getValue($otherField);
    }

    // ── Date ─────────────────────────────────────────────────────────────────

    protected function validateDate(string $field, mixed $value, array $params): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        return strtotime((string) $value) !== false;
    }

    protected function validateDateFormat(string $field, mixed $value, array $params): bool
    {
        $format = $params[0] ?? 'Y-m-d';
        if ($value === null || $value === '') {
            return false;
        }
        $d = \DateTime::createFromFormat($format, (string) $value);
        return $d !== false && $d->format($format) === (string) $value;
    }

    protected function validateBefore(string $field, mixed $value, array $params): bool
    {
        $date = $params[0] ?? '';
        if ($value === null || $value === '' || $date === '') {
            return false;
        }
        $ts    = strtotime((string) $value);
        $limit = $date === 'today' ? strtotime('today') : strtotime($date);
        return $ts !== false && $limit !== false && $ts < $limit;
    }

    protected function validateAfter(string $field, mixed $value, array $params): bool
    {
        $date = $params[0] ?? '';
        if ($value === null || $value === '' || $date === '') {
            return false;
        }
        $ts    = strtotime((string) $value);
        $limit = $date === 'today' ? strtotime('today') : strtotime($date);
        return $ts !== false && $limit !== false && $ts > $limit;
    }
}
