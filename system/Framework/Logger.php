<?php

/**
* @package      Enhanced Logger System
* @author       EasyAPP Framework
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Logger {
    private static $instance = null;
    private $logFile;
    private $logLevel;
    private $levels = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7
    ];
    
    public function __construct($logFile = null, $logLevel = 'debug') {
        $this->logFile = $logFile ?: PATH . CONFIG_LOG_FILE;
        $this->logLevel = $logLevel;
        $this->ensureLogDirectory();
    }

    public static function getInstance($logFile = null, $logLevel = 'debug') {
        if (self::$instance === null) {
            self::$instance = new self($logFile, $logLevel);
        }
        return self::$instance;
    }
    
    private function ensureLogDirectory() {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    public function log($level, $message, $context = []) {
        if (!isset($this->levels[$level]) || !isset($this->levels[$this->logLevel])) {
            return false;
        }
        
        if ($this->levels[$level] < $this->levels[$this->logLevel]) {
            return false;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextString}" . PHP_EOL;
        
        return file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    public function emergency($message, $context = []) {
        return $this->log('emergency', $message, $context);
    }
    
    public function alert($message, $context = []) {
        return $this->log('alert', $message, $context);
    }
    
    public function critical($message, $context = []) {
        return $this->log('critical', $message, $context);
    }
    
    public function error($message, $context = []) {
        return $this->log('error', $message, $context);
    }
    
    public function warning($message, $context = []) {
        return $this->log('warning', $message, $context);
    }
    
    public function notice($message, $context = []) {
        return $this->log('notice', $message, $context);
    }
    
    public function info($message, $context = []) {
        return $this->log('info', $message, $context);
    }
    
    public function debug($message, $context = []) {
        return $this->log('debug', $message, $context);
    }
    
    public function exception(\Exception $exception, $context = []) {
        $message = sprintf(
            'Exception: %s in %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        return $this->error($message, $context);
    }
    
    public function setLogLevel($level) {
        if (isset($this->levels[$level])) {
            $this->logLevel = $level;
        }
    }
    
    public function getLogFile() {
        return $this->logFile;
    }
    
    public function tail($lines = 50) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $file = new \SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $start = max(0, $totalLines - $lines);
        $result = [];
        
        for ($i = $start; $i < $totalLines; $i++) {
            $file->seek($i);
            $result[] = $file->current();
        }
        
        return $result;
    }
}