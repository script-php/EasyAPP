<?php

/**
 * EnvReader
 * 
 * A simple PHP class to read and parse .env files, supporting comments,
 * quoted values, and variable substitution.
 **/

namespace System\Framework;

class EnvReader {
    private $filePath;
    private $variables = [];

    public function __construct($filePath = '.env') {
        $this->filePath = $filePath;
    }

    /**
     * Load and parse the .env file
     * 
     * @return bool True if file was loaded successfully
     * @throws Exception If file doesn't exist or can't be read
     */
    public function load() {
        if (!file_exists($this->filePath)) {
            throw new \Exception("Environment file not found: {$this->filePath}");
        }

        if (!is_readable($this->filePath)) {
            throw new \Exception("Environment file is not readable: {$this->filePath}");
        }

        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $this->parseLine($line);
        }

        $this->setEnvironmentVariables();
        
        return true;
    }

    /**
     * Remove inline comments from a line
     * 
     * @param string $line
     * @return string
     */
    private function removeInlineComments($line)
    {
        $inQuotes = false;
        $quoteChar = null;
        $result = '';
        
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            
            // Handle quotes
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $result .= $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                $inQuotes = false;
                $quoteChar = null;
                $result .= $char;
            }
            // If we're not in quotes and encounter a comment character, stop parsing
            elseif (!$inQuotes && $char === '#') {
                break;
            }
            // Otherwise, add the character to the result
            else {
                $result .= $char;
            }
        }
        
        return $result;
    }

    /**
     * Parse a single line from the .env file
     * 
     * @param string $line
     */
    private function parseLine($line) {
        // Skip empty lines
        $line = trim($line);
        if (empty($line)) {
            return;
        }

        // Skip lines that are purely comments
        if ($line[0] === '#') {
            return;
        }

        // Remove inline comments (everything after # that's not inside quotes)
        $line = $this->removeInlineComments($line);

        // Find the first = sign
        $separatorPos = strpos($line, '=');
        if ($separatorPos === false) {
            return; // Skip malformed lines
        }

        $key = trim(substr($line, 0, $separatorPos));
        $value = trim(substr($line, $separatorPos + 1));

        // Skip if key is empty
        if (empty($key)) {
            return;
        }

        // Remove quotes if present
        $value = $this->removeQuotes($value);

        // Handle variable substitution
        $value = $this->substituteVariables($value);

        // Handle array parsing
        $parsedValue = $this->parseValue($value);

        // Handle indexed array format (KEY_0, KEY_1, etc.)
        if (preg_match('/^(.+)_(\d+)$/', $key, $matches)) {
            $baseKey = $matches[1];
            $index = (int)$matches[2];
            
            if (!isset($this->variables[$baseKey])) {
                $this->variables[$baseKey] = [];
            }
            
            if (!is_array($this->variables[$baseKey])) {
                $this->variables[$baseKey] = [];
            }
            
            $this->variables[$baseKey][$index] = $parsedValue;
            ksort($this->variables[$baseKey]); // Keep array sorted by index
        } else {
            $this->variables[$key] = $parsedValue;
        }
    }

    /**
     * Remove surrounding quotes from value
     * 
     * @param string $value
     * @return string
     */
    private function removeQuotes($value) {
        $length = strlen($value);
        
        if ($length >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[$length - 1];
            
            if (($firstChar === '"' && $lastChar === '"') || 
                ($firstChar === "'" && $lastChar === "'")) {
                return substr($value, 1, -1);
            }
        }
        
        return $value;
    }

    /**
     * Substitute variables in the format ${VAR_NAME} or $VAR_NAME
     * 
     * @param string $value
     * @return string
     */
    private function substituteVariables($value) {
        // Handle ${VAR_NAME} format
        $value = preg_replace_callback('/\$\{([A-Za-z_][A-Za-z0-9_]*)\}/', function($matches) {
            $varName = $matches[1];
            return $this->variables[$varName] ?? getenv($varName) ?: $matches[0];
        }, $value);

        // Handle $VAR_NAME format (word boundary required)
        $value = preg_replace_callback('/\$([A-Za-z_][A-Za-z0-9_]*)\b/', function($matches) {
            $varName = $matches[1];
            return $this->variables[$varName] ?? getenv($varName) ?: $matches[0];
        }, $value);

        return $value;
    }

    /**
     * Parse value and detect arrays/objects
     * 
     * @param string $value
     * @return mixed
     */
    private function parseValue($value) {
        // Handle empty values
        if ($value === '') {
            return '';
        }

        // Handle boolean values
        $lowerValue = strtolower($value);
        if ($lowerValue === 'true') {
            return true;
        }
        if ($lowerValue === 'false') {
            return false;
        }
        if ($lowerValue === 'null') {
            return null;
        }

        // Handle numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // Try to parse as JSON (arrays and objects)
        if ($this->looksLikeJson($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Handle comma-separated values as arrays
        if (strpos($value, ',') !== false) {
            // Only treat as array if it has multiple values and no spaces around commas suggest it's intentional
            $parts = array_map('trim', explode(',', $value));
            if (count($parts) > 1) {
                // Parse each part recursively (for nested types)
                return array_map([$this, 'parseValue'], $parts);
            }
        }

        // Return as string if no special parsing applies
        return $value;
    }

    /**
     * Check if value looks like JSON
     * 
     * @param string $value
     * @return bool
     */
    private function looksLikeJson($value) {
        $firstChar = $value[0] ?? '';
        $lastChar = substr($value, -1);
        
        // Check for array format [...]
        if ($firstChar === '[' && $lastChar === ']') {
            return true;
        }
        
        // Check for object format {...}
        if ($firstChar === '{' && $lastChar === '}') {
            return true;
        }
        
        return false;
    }

    /**
     * Set environment variables using putenv() and $_ENV
     */
    private function setEnvironmentVariables() {
        foreach ($this->variables as $key => $value) {
            // Store the original value in $_ENV and $_SERVER (supports arrays)
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            
            // For putenv(), only set string values (arrays can't be environment variables)
            if (is_scalar($value) || is_null($value)) {
                putenv("$key=" . (string)$value);
            } elseif (is_array($value)) {
                // For arrays, store as JSON string in putenv for compatibility
                putenv("$key=" . json_encode($value));
            }
        }
    }

    /**
     * Get a specific environment variable
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return $this->variables[$key] ?? $default;
    }

    /**
     * Get an environment variable as an array
     * 
     * @param string $key
     * @param array $default
     * @return array
     */
    public function getArray($key, $default = []) {
        $value = $this->get($key, $default);
        
        if (is_array($value)) {
            return $value;
        }
        
        // If it's a string, try to parse as comma-separated
        if (is_string($value) && !empty($value)) {
            return array_map('trim', explode(',', $value));
        }
        
        return $default;
    }

    /**
     * Get an environment variable as boolean
     * 
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getBool($key, $default = false) {
        $value = $this->get($key, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lowerValue = strtolower($value);
            return in_array($lowerValue, ['true', '1', 'yes', 'on']);
        }
        
        return (bool)$value;
    }

    /**
     * Get an environment variable as integer
     * 
     * @param string $key
     * @param int $default
     * @return int
     */
    public function getInt($key, $default = 0) {
        $value = $this->get($key, $default);
        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * Get all loaded variables
     * 
     * @return array
     */
    public function all() {
        return $this->variables;
    }

    /**
     * Check if a variable exists
     * 
     * @param string $key
     * @return bool
     */
    public function has($key) {
        return isset($this->variables[$key]);
    }

    /**
     * Static method to quickly load an .env file
     * 
     * @param string $filePath
     * @return EnvReader
     */
    public static function loadFile($filePath = '.env') {
        $reader = new self($filePath);
        $reader->load();
        return $reader;
    }
}