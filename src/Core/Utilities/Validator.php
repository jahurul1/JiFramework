<?php
namespace JiFramework\Core\Utilities;

class Validator
{
    /**
     * The data to validate.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The validation rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * The validation errors.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Custom error messages.
     *
     * @var array
     */
    protected $customMessages = [];

    /**
     * Custom validation rules.
     *
     * @var array
     */
    protected $customRules = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Initialize if needed
    }

    /**
     * Validate a single value against a set of rules.
     *
     * @param mixed        $value The value to validate.
     * @param string|array $rules The validation rules.
     * @return bool True if validation passes, false otherwise.
     */
    public function validateField($value, $rules)
    {
        $this->data = ['value' => $value];
        $this->rules = ['value' => $rules];
        $this->errors = [];

        return $this->validate();
    }

    /**
     * Validate an array of data against a set of rules.
     *
     * @param array $data  The data to validate.
     * @param array $rules The validation rules.
     * @return bool True if validation passes, false otherwise.
     */
    public function validateArray(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->errors = [];

        return $this->validate();
    }

    /**
     * Perform the validation.
     *
     * @return bool True if validation passes, false otherwise.
     */

    protected function validate()
    {
        foreach ($this->rules as $field => $rules) {
            $value = $this->getValue($field);

            // Split multiple rules separated by '|'
            $rules = is_string($rules) ? explode('|', $rules) : (array) $rules;

            foreach ($rules as $rule) {
                // Extract rule name and parameters
                $ruleName = $rule;
                $params = [];

                if (strpos($rule, ':') !== false) {
                    [$ruleName, $paramString] = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                $methodName = 'validate' . ucfirst($ruleName);

                if (method_exists($this, $methodName)) {
                    $result = $this->$methodName($field, $value, $params);

                    if (!$result) {
                        $this->addError($field, $ruleName, $params);
                    }
                } else {
                    // Handle custom rules if a closure is provided
                    if (isset($this->customRules[$ruleName]) && is_callable($this->customRules[$ruleName])) {
                        $result = call_user_func($this->customRules[$ruleName], $field, $value, $params, $this->data);

                        if (!$result) {
                            $this->addError($field, $ruleName, $params);
                        }
                    } else {
                        throw new \Exception("Validation rule '{$ruleName}' does not exist.");
                    }
                }
            }
        }
        return empty($this->errors);    
    }

    /**
     * Get the value of a field, supporting nested fields using dot notation.
     *
     * @param string $field The field name.
     * @return mixed The value.
     */
    protected function getValue(string $field)
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Add an error message for a field.
     *
     * @param string $field
     * @param string $rule
     * @param array  $params
     */
    protected function addError(string $field, string $rule, array $params = [])
    {
        $message = $this->getErrorMessage($field, $rule, $params);
        $this->errors[$field][] = $message;
    }

    /**
     * Retrieve an error message for a rule.
     *
     * @param string $field
     * @param string $rule
     * @param array  $params
     * @return string
     */
    protected function getErrorMessage(string $field, string $rule, array $params = [])
    {
        // Check for custom message
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } elseif (isset($this->customMessages[$rule])) {
            $message = $this->customMessages[$rule];
        } else {
            // Default error messages
            $defaultMessages = $this->getDefaultMessages();
            $message = $defaultMessages[$rule] ?? 'The :field field has an error.';
        }

        // Replace placeholders
        $message = str_replace(':field', ucfirst(str_replace('_', ' ', $field)), $message);

        foreach ($params as $index => $param) {
            $message = str_replace(":param{$index}", $param, $message);
        }

        return $message;
    }

    /**
     * Get default error messages.
     *
     * @return array
     */
    protected function getDefaultMessages()
    {
        return [
            'required'   => 'The :field field is required.',
            'email'      => 'The :field field must be a valid email address.',
            'min'        => 'The :field field must be at least :param0 characters.',
            'max'        => 'The :field field must not exceed :param0 characters.',
            'numeric'    => 'The :field field must be a number.',
            'regex'      => 'The :field field format is invalid.',
            'date'       => 'The :field field must be a valid date.',
            'in'         => 'The :field field must be one of the following values: :param0.',
            'unique'     => 'The :field field must be unique.',
            'alpha'      => 'The :field field may only contain letters.',
            'alphaNum'   => 'The :field field may only contain letters and numbers.',
            'confirmed'  => 'The :field field confirmation does not match.',
            // Add more default messages as needed
        ];
    }

    /**
     * Add custom validation rules.
     *
     * @param string   $ruleName
     * @param callable $callback
     * @return self
     */
    public function addRule(string $ruleName, callable $callback)
    {
        $this->customRules[$ruleName] = $callback;
        return $this;
    }

    /**
     * Add custom error messages.
     *
     * @param array $messages The custom messages.
     * @return self
     */
    public function addMessages(array $messages)
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
        return $this;
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    // Validation methods

    protected function validateRequired(string $field, $value, array $params)
    {
        return !is_null($value) && $value !== '';
    }
    protected function validateEmail(string $field, $value, array $params)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(string $field, $value, array $params)
    {
        $min = (int) ($params[0] ?? 0);
        return strlen($value) >= $min;
    }

    protected function validateMax(string $field, $value, array $params)
    {
        $max = (int) ($params[0] ?? PHP_INT_MAX);
        return strlen($value) <= $max;
    }

    protected function validateNumeric(string $field, $value, array $params)
    {
        return is_numeric($value);
    }

    protected function validateRegex(string $field, $value, array $params)
    {
        $pattern = $params[0] ?? '';
        return preg_match($pattern, $value) === 1;
    }

    protected function validateDate(string $field, $value, array $params)
    {
        return strtotime($value) !== false;
    }

    protected function validateIn(string $field, $value, array $params)
    {
        return in_array($value, $params);
    }

    protected function validateAlpha(string $field, $value, array $params)
    {
        return ctype_alpha($value);
    }

    protected function validateAlphaNum(string $field, $value, array $params)
    {
        return ctype_alnum($value);
    }

    protected function validateConfirmed(string $field, $value, array $params)
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);
        return $value === $confirmationValue;
    }

    // Add more validation methods as needed

}


