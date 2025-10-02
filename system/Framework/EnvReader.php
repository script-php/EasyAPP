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

        $this->variables[$key] = $value;
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
     * Set environment variables using putenv() and $_ENV
     */
    private function setEnvironmentVariables() {
        foreach ($this->variables as $key => $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
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