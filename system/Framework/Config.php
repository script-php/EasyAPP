<?php

/**
* @package      Enhanced Config System
* @author       EasyAPP Framework
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Config {
    private static $instance = null;
    private $config = [];
    private $loaded = [];
    
    public function __construct() {
        $this->loadDefaults();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function loadDefaults() {

        // Framework defaults
        $this->config = [
            // Platform
            'platform' => 'EasyAPP',
            'version' => '1.7.0',
            'app_env' => env('APP_ENV', 'dev'), // 
            
            // App config
            'url' => env('APP_URL', null),
            'base_url' => env('BASE_URL', null),
            'services' => env('SERVICES', []), // Array of services to load
            
            'app_home' => env('APP_HOME', 'home'),
            'app_error' => env('APP_ERROR', 'not_found'),
            
            // Database
            'db_driver' => env('DB_DRIVER', 'mysql'),
            'db_hostname' => env('DB_HOSTNAME', ''),
            'db_database' => env('DB_DATABASE', ''),
            'db_username' => env('DB_USERNAME', ''),
            'db_password' => env('DB_PASSWORD', ''),
            'db_port' => env('DB_PORT', '3306'),
            'db_options' => [],
            'db_encoding' => env('DB_ENCODING', 'utf8mb4'),
            'db_prefix' => env('DB_PREFIX', ''),
            
            // App settings
            'domain' => env('DOMAIN', 'localhost'),

            // Session
            'session_name' => env('SESSION_NAME', 'session'), // session cookie name
            'session_driver' => env('SESSION_DRIVER', 'file'), // file, database, redis, etc.
            'session_lifetime' => env('SESSION_LIFETIME', 7200), // in seconds
            'session_secure' => env('SESSION_SECURE', false), // true if using HTTPS
            'session_httponly' => env('SESSION_HTTPONLY', true), // prevent JavaScript access
            
            // Framework directories
            'query' => 'route',
            'dir_app' => 'app/',
            'dir_controller' => 'controller/',
            'dir_model' => 'model/',
            'dir_event' => 'event/',
            'dir_service' => 'service/',
            'dir_view' => 'view/',
            'dir_language' => 'language/',
            
            // System directories
            'dir_system' => 'system/',
            'dir_framework' => 'Framework/',
            'dir_library' => 'Library/',
            'dir_storage' => 'storage/',
            'dir_assets' => 'assets/',
            
            // Performance
            'compression' => env('COMPRESSION', 0),
            'cache_enabled' => env('CACHE_ENABLED', false),
            'cache_driver' => env('CACHE_DRIVER', 'file'),
            'cache_ttl' => env('CACHE_TTL', 3600),
            
            // Debug & Security
            'debug' => env('DEBUG', false),
            'dev_db_schema' => env('DEV_DB_SCHEMA', false),
            'csrf_protection' => env('CSRF_PROTECTION', false),
            'input_sanitization' => env('INPUT_SANITIZATION', true),
            
            // Localization
            'default_language' => env('DEFAULT_LANGUAGE', 'en-gb'),
            'timezone' => env('TIMEZONE', 'UTC'),
            
            // Logging
            'log_level' => env('LOG_LEVEL', 'error'),
            'log_file' => env('LOG_FILE', 'storage/logs/error.log'),

        ];
        
    }
    
    public function load($file) {
        if (in_array($file, $this->loaded)) {
            return $this;
        }
        
        if (file_exists($file)) {
            $config = [];
            include $file;
            $this->config = array_merge($this->config, $config);
            $this->loaded[] = $file;
        }
        
        return $this;
    }
    
    public function get($key = null, $default = null) {
        if ($key === null) {
            return $this->config;
        }
        
        if (strpos($key, '.') !== false) {
            return $this->getDotNotation($key, $default);
        }
        
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    public function set($key, $value) {
        if (strpos($key, '.') !== false) {
            $this->setDotNotation($key, $value);
        } else {
            $this->config[$key] = $value;
        }
        
        return $this;
    }
    
    public function has($key) {
        if (strpos($key, '.') !== false) {
            return $this->getDotNotation($key) !== null;
        }
        
        return isset($this->config[$key]);
    }
    
    private function getDotNotation($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    private function setDotNotation($key, $value) {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    public function validate() {
        $errors = [];
        
        // Validate required configuration
        $required = ['platform', 'version', 'app_env'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $errors[] = "Configuration '{$key}' is required";
            }
        }
        
        // Validate directory structure
        $dirs = ['dir_app', 'dir_system', 'dir_storage'];
        foreach ($dirs as $dir) {
            if (!is_dir(PATH . $this->config[$dir])) {
                $errors[] = "Directory '{$this->config[$dir]}' does not exist";
            }
        }
        
        // Validate environment
        if (!in_array($this->config['app_env'], ['dev', 'testing', 'production'])) {
            $errors[] = "Invalid environment. Must be 'dev', 'testing', or 'production'";
        }
        
        return $errors;
    }
    
    public function createConstants($config) {
        foreach ($config as $key => $value) {
            if (is_scalar($value)) {
                define("CONFIG_" . strtoupper($key), $value);
            }
        }
    }
    
    public function all() {
        return $this->config;
    }
}
