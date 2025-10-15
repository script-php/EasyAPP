<?php

/**
* @package      Validator
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
* @version      2.0.0
*/

namespace System\Framework;

use DateTime;
use Exception;

class Validator {
    private $originalValue;
    private $currentValue;
    private $errors = [];
    private $isValid = true;
    private $currentField = 'value';
    private $strictMode = false;
    private $skipValidation = false;
    private $customErrorMessages = [];

    public function __construct($value, bool $strictMode = false) {
        $this->originalValue = $value;
        $this->currentValue = $value;
        $this->strictMode = $strictMode;
    }

    public static function validate($value, bool $strictMode = false): self {
        return new self($value, $strictMode);
    }

    public static function make($value, bool $strictMode = false): self {
        return new self($value, $strictMode);
    }

    public static function strict($value): self {
        return new self($value, true);
    }

    public function field(string $path): self
    {
        $this->currentValue = $this->originalValue;
        $this->skipValidation = false;
        
        // Support nested path like "user.profile.name"
        $keys = explode('.', $path);
        $current = $this->currentValue;
        
        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_object($current) && property_exists($current, $key)) {
                $current = $current->$key;
            } else {
                $this->addError('field', "Field '{$path}' does not exist");
                $this->currentValue = null;
                $this->currentField = $path;
                return $this;
            }
        }
        
        $this->currentValue = $current;
        $this->currentField = $path;
        return $this;
    }

    public function fields(array $fieldNames): array
    {
        $results = [];
        foreach ($fieldNames as $fieldName) {
            $validator = clone $this;
            $results[$fieldName] = $validator->field($fieldName);
        }
        return $results;
    }

    public function required(array $fieldNames): self
    {
        foreach ($fieldNames as $fieldName) {
            $this->field($fieldName)->notEmpty();
        }
        return $this;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getErrorsForField(string $field): array {
        return array_filter($this->errors, function($error) use ($field) {
            return strpos($error, $field) === 0;
        });
    }

    public function getFirstError(): ?string {
        return empty($this->errors) ? null : $this->errors[0];
    }
    
    public function getValue() {
        return $this->currentValue;
    }

    public function getOriginalValue() {
        return $this->originalValue;
    }

    public function isValid(): bool {
        return $this->isValid;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function when($condition): self {
        if (is_callable($condition)) {
            $this->skipValidation = !call_user_func($condition, $this->currentValue);
        } else {
            $this->skipValidation = !$condition;
        }
        return $this;
    }

    public function unless($condition): self {
        if (is_callable($condition)) {
            $this->skipValidation = call_user_func($condition, $this->currentValue);
        } else {
            $this->skipValidation = $condition;
        }
        return $this;
    }

    public function setMessage(string $rule, string $message): self {
        $this->customErrorMessages[$rule] = $message;
        return $this;
    }

    public function notEmpty(): self {
        if ($this->skipValidation) return $this;
        
        if (empty($this->currentValue)) {
            $this->addError('notEmpty', "{$this->currentField} must not be empty");
        }
        return $this;
    }

    public function isNull(): self {
        if ($this->skipValidation) return $this;
        
        if ($this->currentValue !== null) {
            $this->addError('isNull', "{$this->currentField} must be null");
        }
        return $this;
    }

    public function notNull(): self {
        if ($this->skipValidation) return $this;
        
        if ($this->currentValue === null) {
            $this->addError('notNull', "{$this->currentField} must not be null");
        }
        return $this;
    }

    public function optional(): self {
        if ($this->currentValue === null || $this->currentValue === '') {
            $this->skipValidation = true;
        }
        return $this;
    }

    public function equals($compareTo): self {
        if ($this->skipValidation) return $this;
        
        if ($this->currentValue != $compareTo) {
            $this->addError('equals', "{$this->currentField} must be equal to " . print_r($compareTo, true));
        }
        return $this;
    }

    public function notEquals($compareTo): self {
        if ($this->skipValidation) return $this;
        
        if ($this->currentValue == $compareTo) {
            $this->addError('notEquals', "{$this->currentField} must not be equal to " . print_r($compareTo, true));
        }
        return $this;
    }

    public function isString(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_string($this->currentValue)) {
            $this->addError('isString', "{$this->currentField} must be a string");
        }
        return $this;
    }

    public function isInt(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_int($this->currentValue)) {
            $this->addError('isInt', "{$this->currentField} must be an integer");
        }
        return $this;
    }

    public function isFloat(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_float($this->currentValue)) {
            $this->addError('isFloat', "{$this->currentField} must be a float");
        }
        return $this;
    }

    public function isNumeric(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_numeric($this->currentValue)) {
            $this->addError('isNumeric', "{$this->currentField} must be numeric");
        }
        return $this;
    }

    public function isBool(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_bool($this->currentValue)) {
            $this->addError('isBool', "{$this->currentField} must be a boolean");
        }
        return $this;
    }

    public function isArray(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_array($this->currentValue)) {
            $this->addError('isArray', "{$this->currentField} must be an array");
        }
        return $this;
    }

    public function isObject(): self {
        if ($this->skipValidation) return $this;
        
        if (!is_object($this->currentValue)) {
            $this->addError('isObject', "{$this->currentField} must be an object");
        }
        return $this;
    }

    public function isInstanceOf(string $className): self {
        if ($this->skipValidation) return $this;
        
        if (!($this->currentValue instanceof $className)) {
            $this->addError('isInstanceOf', "{$this->currentField} must be an instance of {$className}");
        }
        return $this;
    }

    public function minLength(int $min): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && strlen($this->currentValue) < $min) {
            $this->addError('minLength', "{$this->currentField} must be at least {$min} characters long");
        }
        return $this;
    }

    public function maxLength(int $max): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && strlen($this->currentValue) > $max) {
            $this->addError('maxLength', "{$this->currentField} must be no more than {$max} characters long");
        }
        return $this;
    }

    public function lengthBetween(int $min, int $max): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            $length = strlen($this->currentValue);
            if ($length < $min || $length > $max) {
                $this->addError('lengthBetween', "{$this->currentField} must be between {$min} and {$max} characters long");
            }
        }
        return $this;
    }

    public function contains(string $substring): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && strpos($this->currentValue, $substring) === false) {
            $this->addError('contains', "{$this->currentField} must contain '{$substring}'");
        }
        return $this;
    }

    public function notContains(string $substring): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && strpos($this->currentValue, $substring) !== false) {
            $this->addError('notContains', "{$this->currentField} must not contain '{$substring}'");
        }
        return $this;
    }

    public function matchesRegex(string $pattern): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && !preg_match($pattern, $this->currentValue)) {
            $this->addError('matchesRegex', "{$this->currentField} must match pattern {$pattern}");
        }
        return $this;
    }

    public function isEmail(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_EMAIL)) {
            $this->addError('isEmail', "{$this->currentField} must be a valid email address");
        }
        return $this;
    }

    public function isUrl(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_URL)) {
            $this->addError('isUrl', "{$this->currentField} must be a valid URL");
        }
        return $this;
    }

    public function minValue($min): self {
        if ($this->skipValidation) return $this;
        
        if (is_numeric($this->currentValue) && $this->currentValue < $min) {
            $this->addError('minValue', "{$this->currentField} must be at least {$min}");
        }
        return $this;
    }

    public function maxValue($max): self {
        if ($this->skipValidation) return $this;
        
        if (is_numeric($this->currentValue) && $this->currentValue > $max) {
            $this->addError('maxValue', "{$this->currentField} must be no more than {$max}");
        }
        return $this;
    }

    public function between($min, $max): self {
        if ($this->skipValidation) return $this;
        
        if (is_numeric($this->currentValue)) {
            if ($this->currentValue < $min || $this->currentValue > $max) {
                $this->addError('between', "{$this->currentField} must be between {$min} and {$max}");
            }
        }
        return $this;
    }

    public function minCount(int $min): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue) && count($this->currentValue) < $min) {
            $this->addError('minCount', "{$this->currentField} must contain at least {$min} items");
        }
        return $this;
    }

    public function maxCount(int $max): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue) && count($this->currentValue) > $max) {
            $this->addError('maxCount', "{$this->currentField} must contain no more than {$max} items");
        }
        return $this;
    }

    public function countBetween(int $min, int $max): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue)) {
            $count = count($this->currentValue);
            if ($count < $min || $count > $max) {
                $this->addError('countBetween', "{$this->currentField} must contain between {$min} and {$max} items");
            }
        }
        return $this;
    }

    public function containsKey($key): self {
        if ($this->skipValidation) return $this;
        
        if ((is_array($this->currentValue) && !array_key_exists($key, $this->currentValue)) || 
            (is_object($this->currentValue) && !property_exists($this->currentValue, $key))) {
            $this->addError('containsKey', "{$this->currentField} must contain key '{$key}'");
        }
        return $this;
    }

    public function notContainsKey($key): self {
        if ($this->skipValidation) return $this;
        
        if ((is_array($this->currentValue) && array_key_exists($key, $this->currentValue))) {
            $this->addError('notContainsKey', "{$this->currentField} must not contain key '{$key}'");
        }
        return $this;
    }

    public function containsValue($value): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue) && !in_array($value, $this->currentValue, true)) {
            $this->addError('containsValue', "{$this->currentField} must contain value " . print_r($value, true));
        }
        return $this;
    }

    public function notContainsValue($value): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue) && in_array($value, $this->currentValue, true)) {
            $this->addError('notContainsValue', "{$this->currentField} must not contain value " . print_r($value, true));
        }
        return $this;
    }

    public function hasMethod(string $methodName): self {
        if ($this->skipValidation) return $this;
        
        if (is_object($this->currentValue) && !method_exists($this->currentValue, $methodName)) {
            $this->addError('hasMethod', "{$this->currentField} must have method '{$methodName}'");
        }
        return $this;
    }

    public function hasProperty(string $propertyName): self {
        if ($this->skipValidation) return $this;
        
        if (is_object($this->currentValue) && !property_exists($this->currentValue, $propertyName)) {
            $this->addError('hasProperty', "{$this->currentField} must have property '{$propertyName}'");
        }
        return $this;
    }

    public function isDate(string $format = 'Y-m-d'): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            $date = DateTime::createFromFormat($format, $this->currentValue);
            if (!$date || $date->format($format) !== $this->currentValue) {
                $this->addError('isDate', "{$this->currentField} must be a valid date in format {$format}");
            }
        }
        return $this;
    }

    // Enhanced Array Validation
    public function eachElement(callable $validator): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue)) {
            foreach ($this->currentValue as $index => $element) {
                $elementValidator = new self($element, $this->strictMode);
                $elementValidator->currentField = "{$this->currentField}[{$index}]";
                call_user_func($validator, $elementValidator);
                
                if (!$elementValidator->isValid()) {
                    $this->errors = array_merge($this->errors, $elementValidator->getErrors());
                    $this->isValid = false;
                }
            }
        }
        return $this;
    }

    public function validKeys(array $allowedKeys): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue)) {
            $extraKeys = array_diff(array_keys($this->currentValue), $allowedKeys);
            if (!empty($extraKeys)) {
                $this->addError('validKeys', "{$this->currentField} contains invalid keys: " . implode(', ', $extraKeys));
            }
        }
        return $this;
    }

    // Common Validators
    public function isPhone(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            $pattern = '/^[\+]?[1-9][\d]{0,15}$/';
            if (!preg_match($pattern, preg_replace('/[\s\-\(\)]/', '', $this->currentValue))) {
                $this->addError('isPhone', "{$this->currentField} must be a valid phone number");
            }
        }
        return $this;
    }

    public function isUuid(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
            if (!preg_match($pattern, $this->currentValue)) {
                $this->addError('isUuid', "{$this->currentField} must be a valid UUID");
            }
        }
        return $this;
    }

    public function isJson(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            json_decode($this->currentValue);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('isJson', "{$this->currentField} must be valid JSON");
            }
        }
        return $this;
    }

    public function isCreditCard(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            $number = preg_replace('/\D/', '', $this->currentValue);
            if (!$this->luhnCheck($number)) {
                $this->addError('isCreditCard', "{$this->currentField} must be a valid credit card number");
            }
        }
        return $this;
    }

    public function isIpAddress(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_IP)) {
            $this->addError('isIpAddress', "{$this->currentField} must be a valid IP address");
        }
        return $this;
    }

    public function isAlpha(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && !ctype_alpha($this->currentValue)) {
            $this->addError('isAlpha', "{$this->currentField} must contain only letters");
        }
        return $this;
    }

    public function isAlphaNumeric(): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue) && !ctype_alnum($this->currentValue)) {
            $this->addError('isAlphaNumeric', "{$this->currentField} must contain only letters and numbers");
        }
        return $this;
    }

    // Nested Validation
    public function nested(callable $validator): self {
        if ($this->skipValidation) return $this;
        
        if (is_array($this->currentValue) || is_object($this->currentValue)) {
            $nestedValidator = new self($this->currentValue, $this->strictMode);
            $nestedValidator->currentField = $this->currentField;
            call_user_func($validator, $nestedValidator);
            
            if (!$nestedValidator->isValid()) {
                $this->errors = array_merge($this->errors, $nestedValidator->getErrors());
                $this->isValid = false;
            }
        }
        return $this;
    }

    // Validation Rules
    public function rules(array $rules): self {
        foreach ($rules as $rule) {
            if (is_callable($rule)) {
                call_user_func($rule, $this);
            }
        }
        return $this;
    }

    // Date Enhancements
    public function isAfter(string $date): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            try {
                $currentDate = new DateTime($this->currentValue);
                $compareDate = new DateTime($date);
                
                if ($currentDate <= $compareDate) {
                    $this->addError('isAfter', "{$this->currentField} must be after {$date}");
                }
            } catch (Exception $e) {
                $this->addError('isAfter', "{$this->currentField} must be a valid date");
            }
        }
        return $this;
    }

    public function isBefore(string $date): self {
        if ($this->skipValidation) return $this;
        
        if (is_string($this->currentValue)) {
            try {
                $currentDate = new DateTime($this->currentValue);
                $compareDate = new DateTime($date);
                
                if ($currentDate >= $compareDate) {
                    $this->addError('isBefore', "{$this->currentField} must be before {$date}");
                }
            } catch (Exception $e) {
                $this->addError('isBefore', "{$this->currentField} must be a valid date");
            }
        }
        return $this;
    }

    // Utility Methods
    public function stopOnFirstFailure(): self {
        if ($this->hasErrors()) {
            $this->skipValidation = true;
        }
        return $this;
    }

    public function reset(): self {
        $this->currentValue = $this->originalValue;
        $this->currentField = 'value';
        $this->skipValidation = false;
        $this->errors = []; // Clear all errors on reset
        $this->isValid = true; // Reset validation status
        return $this;
    }

    // Helper Methods
    private function luhnCheck(string $number): bool {
        $sum = 0;
        $alternate = false;
        
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = intval($number[$i]);
            
            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }
            
            $sum += $digit;
            $alternate = !$alternate;
        }
        
        return $sum % 10 === 0;
    }

    public function custom(callable $callback, string $message): self {
        if ($this->skipValidation) return $this;
        
        if (!call_user_func($callback, $this->currentValue)) {
            $this->addError('custom', "{$this->currentField} {$message}");
        }
        return $this;
    }

    private function addError(string $rule, string $message): void {
        // Use custom message if available
        if (isset($this->customErrorMessages[$rule])) {
            $message = str_replace('{field}', $this->currentField, $this->customErrorMessages[$rule]);
        }
        
        $this->errors[] = $message;
        $this->isValid = false;
        
        // In strict mode, stop validation on first error
        if ($this->strictMode) {
            $this->skipValidation = true;
        }
    }
}