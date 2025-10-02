<?php

/**
* @package      Validator
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Validator {
    private $originalValue;
    private $currentValue;
    private $errors = [];
    private $isValid = true;
    private $currentField = 'value';

    public function __construct($value) {
        $this->originalValue = $value;  // original value
        $this->currentValue = $value;
    }

    public static function validate($value): self {
        return new self($value);
    }

    public function field(string $name): self
    {
        $this->currentValue = $this->originalValue;
        
        if (is_array($this->currentValue) && array_key_exists($name, $this->currentValue)) {
            $this->currentValue = $this->currentValue[$name];
        } elseif (is_object($this->currentValue) && property_exists($this->currentValue, $name)) {
            $this->currentValue = $this->currentValue->$name;
        } else {
            $this->addError("Field '{$name}' does not exist");
            $this->currentValue = null;
        }
        $this->currentField = $name;
        return $this;
    }

    public function getErrors(): array {
        return $this->errors;
    }
    
    public function getValue() {
        return $this->currentValue;
    }

    public function isValid(): bool {
        return $this->isValid;
    }

    public function notEmpty(): self {
        if (empty($this->currentValue)) {
            $this->addError("{$this->currentField} must not be empty");
        }
        return $this;
    }

    public function isNull(): self {
        if ($this->currentValue !== null) {
            $this->addError("{$this->currentField} must be null");
        }
        return $this;
    }

    public function notNull(): self {
        if ($this->currentValue === null) {
            $this->addError("{$this->currentField} must not be null");
        }
        return $this;
    }

    public function equals($compareTo): self {
        if ($this->currentValue != $compareTo) {
            $this->addError("{$this->currentField} must be equal to " . print_r($compareTo, true));
        }
        return $this;
    }

    public function notEquals($compareTo): self {
        if ($this->currentValue == $compareTo) {
            $this->addError("{$this->currentField} must not be equal to " . print_r($compareTo, true));
        }
        return $this;
    }

    public function isString(): self {
        if (!is_string($this->currentValue)) {
            $this->addError("{$this->currentField} must be a string");
        }
        return $this;
    }

    public function isInt(): self {
        if (!is_int($this->currentValue)) {
            $this->addError("{$this->currentField} must be an integer");
        }
        return $this;
    }

    public function isFloat(): self {
        if (!is_float($this->currentValue)) {
            $this->addError("{$this->currentField} must be a float");
        }
        return $this;
    }

    public function isNumeric(): self {
        if (!is_numeric($this->currentValue)) {
            $this->addError("{$this->currentField} must be numeric");
        }
        return $this;
    }

    public function isBool(): self {
        if (!is_bool($this->currentValue)) {
            $this->addError("{$this->currentField} must be a boolean");
        }
        return $this;
    }

    public function isArray(): self {
        if (!is_array($this->currentValue)) {
            $this->addError("{$this->currentField} must be an array");
        }
        return $this;
    }

    public function isObject(): self {
        if (!is_object($this->currentValue)) {
            $this->addError("{$this->currentField} must be an object");
        }
        return $this;
    }

    public function isInstanceOf(string $className): self {
        if (!($this->currentValue instanceof $className)) {
            $this->addError("{$this->currentField} must be an instance of {$className}");
        }
        return $this;
    }

    public function minLength(int $min): self {
        if (is_string($this->currentValue) && strlen($this->currentValue) < $min) {
            $this->addError("{$this->currentField} must be at least {$min} characters long");
        }
        return $this;
    }

    public function maxLength(int $max): self {
        if (is_string($this->currentValue) && strlen($this->currentValue) > $max) {
            $this->addError("{$this->currentField} must be no more than {$max} characters long");
        }
        return $this;
    }

    public function lengthBetween(int $min, int $max): self {
        if (is_string($this->currentValue)) {
            $length = strlen($this->currentValue);
            if ($length < $min || $length > $max) {
                $this->addError("{$this->currentField} must be between {$min} and {$max} characters long");
            }
        }
        return $this;
    }

    public function contains(string $substring): self {
        if (is_string($this->currentValue) && strpos($this->currentValue, $substring) === false) {
            $this->addError("{$this->currentField} must contain '{$substring}'");
        }
        return $this;
    }

    public function notContains(string $substring): self {
        if (is_string($this->currentValue) && strpos($this->currentValue, $substring) !== false) {
            $this->addError("{$this->currentField} must not contain '{$substring}'");
        }
        return $this;
    }

    public function matchesRegex(string $pattern): self {
        if (is_string($this->currentValue) && !preg_match($pattern, $this->currentValue)) {
            $this->addError("{$this->currentField} must match pattern {$pattern}");
        }
        return $this;
    }

    public function isEmail(): self {
        if (is_string($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_EMAIL)) {
            $this->addError("{$this->currentField} must be a valid email address");
        }
        return $this;
    }

    public function isUrl(): self {
        if (is_string($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_URL)) {
            $this->addError("{$this->currentField} must be a valid URL");
        }
        return $this;
    }

    public function minValue($min): self {
        if (is_numeric($this->currentValue) && $this->currentValue < $min) {
            $this->addError("{$this->currentField} must be at least {$min}");
        }
        return $this;
    }

    public function maxValue($max): self {
        if (is_numeric($this->currentValue) && $this->currentValue > $max) {
            $this->addError("{$this->currentField} must be no more than {$max}");
        }
        return $this;
    }

    public function between($min, $max): self {
        if (is_numeric($this->currentValue)) {
            if ($this->currentValue < $min || $this->currentValue > $max) {
                $this->addError("{$this->currentField} must be between {$min} and {$max}");
            }
        }
        return $this;
    }

    public function minCount(int $min): self {
        if (is_array($this->currentValue) && count($this->currentValue) < $min) {
            $this->addError("{$this->currentField} must contain at least {$min} items");
        }
        return $this;
    }

    public function maxCount(int $max): self {
        if (is_array($this->currentValue) && count($this->currentValue) > $max) {
            $this->addError("{$this->currentField} must contain no more than {$max} items");
        }
        return $this;
    }

    public function countBetween(int $min, int $max): self {
        if (is_array($this->currentValue)) {
            $count = count($this->currentValue);
            if ($count < $min || $count > $max) {
                $this->addError("{$this->currentField} must contain between {$min} and {$max} items");
            }
        }
        return $this;
    }

    public function containsKey($key): self {
        if ((is_array($this->currentValue) && !array_key_exists($key, $this->currentValue)) || 
            (is_object($this->currentValue) && !property_exists($this->currentValue, $key))) {
            $this->addError("{$this->currentField} must contain key '{$key}'");
        }
        return $this;
    }

    public function notContainsKey($key): self {
        if ((is_array($this->currentValue) && array_key_exists($key, $this->currentValue))) {
            $this->addError("{$this->currentField} must not contain key '{$key}'");
        }
        return $this;
    }

    public function containsValue($value): self {
        if (is_array($this->currentValue) && !in_array($value, $this->currentValue, true)) {
            $this->addError("{$this->currentField} must contain value " . print_r($value, true));
        }
        return $this;
    }

    public function notContainsValue($value): self {
        if (is_array($this->currentValue) && in_array($value, $this->currentValue, true)) {
            $this->addError("{$this->currentField} must not contain value " . print_r($value, true));
        }
        return $this;
    }

    public function hasMethod(string $methodName): self {
        if (is_object($this->currentValue) && !method_exists($this->currentValue, $methodName)) {
            $this->addError("{$this->currentField} must have method '{$methodName}'");
        }
        return $this;
    }

    public function hasProperty(string $propertyName): self {
        if (is_object($this->currentValue) && !property_exists($this->currentValue, $propertyName)) {
            $this->addError("{$this->currentField} must have property '{$propertyName}'");
        }
        return $this;
    }

    public function isDate(string $format = 'Y-m-d'): self {
        if (is_string($this->currentValue)) {
            $date = DateTime::createFromFormat($format, $this->currentValue);
            if (!$date || $date->format($format) !== $this->currentValue) {
                $this->addError("{$this->currentField} must be a valid date in format {$format}");
            }
        }
        return $this;
    }

    public function custom(callable $callback, string $message): self {
        if (!call_user_func($callback, $this->currentValue)) {
            $this->addError("{$this->currentField} {$message}");
        }
        return $this;
    }

    private function addError(string $message): void {
        $this->errors[] = $message;
        $this->isValid = false;
    }
}