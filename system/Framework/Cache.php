<?php

/**
* @package      Cache System
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
* 
* Cache System - File-based caching with TTL support
* 
* Configuration (in .env or config.php):
* - CACHE_ENABLED: Enable/disable caching globally (default: false)
* - CACHE_DRIVER: Cache storage driver (default: 'file')
* - CACHE_TTL: Default cache time-to-live in seconds (default: 3600)
* 
* Usage Examples:
* 
* 1. Basic cache operations:
*    $cache = Cache::getInstance();
*    $cache->set('key', 'value', 3600);
*    $value = $cache->get('key');
*    $cache->delete('key');
*    $cache->clear(); // Clear all cache
* 
* 2. Cache with callback (remember pattern):
*    $users = $cache->remember('users_list', function() {
*        return User::all();
*    }, 3600);
* 
* 3. ORM Query Caching (automatically uses these config values):
*    User::where('active', 1)->cache()->get(); // Uses config settings
*    User::where('status', 'active')->cache(7200)->get(); // Custom TTL
*    User::cacheKey('active_users')->cache()->get(); // Custom key
*    User::noCache()->get(); // Skip cache for this query
* 
* Cache is automatically cleared on:
* - Model save() operations
* - Model update() operations  
* - Model delete() operations
* 
* Storage:
* - Files stored in: storage/cache/
* - Format: [md5_hash].cache
* - Serialized data with expiration timestamp
*/

namespace System\Framework;

class Cache {
    private static $instance = null;
    private $driver;
    private $ttl;
    private $cacheDir;
    
    public function __construct($driver = 'file', $ttl = 3600) {
        $this->driver = $driver;
        $this->ttl = $ttl;
        $this->cacheDir = PATH . 'storage/cache/';
        $this->ensureCacheDirectory();
    }
    
    public static function getInstance($driver = 'file', $ttl = 3600) {
        if (self::$instance === null) {
            self::$instance = new self($driver, $ttl);
        }
        return self::$instance;
    }
    
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key, $default = null) {
        switch ($this->driver) {
            case 'file':
                return $this->getFromFile($key, $default);
            default:
                return $default;
        }
    }
    
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?: $this->ttl;
        
        switch ($this->driver) {
            case 'file':
                return $this->setToFile($key, $value, $ttl);
            default:
                return false;
        }
    }
    
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    public function delete($key) {
        switch ($this->driver) {
            case 'file':
                return $this->deleteFromFile($key);
            default:
                return false;
        }
    }
    
    public function clear() {
        switch ($this->driver) {
            case 'file':
                return $this->clearFileCache();
            default:
                return false;
        }
    }
    
    private function getFromFile($key, $default) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        if (!$data || $data['expires'] < time()) {
            unlink($filename);
            return $default;
        }
        
        return $data['value'];
    }
    
    private function setToFile($key, $value, $ttl) {
        $filename = $this->getCacheFilename($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($filename, serialize($data), LOCK_EX) !== false;
    }
    
    private function deleteFromFile($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    private function clearFileCache() {
        $files = glob($this->cacheDir . '*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    private function getCacheFilename($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
    
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}