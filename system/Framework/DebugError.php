<?php

/**
* @package      DebugError
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class DebugError {
    private $logger;
    
    public function __construct() {
        $this->logger = Logger::getInstance();
    }

    public function display(\Throwable $e) {
        if (ob_get_level()) {
            ob_clean();
        }
        
        http_response_code($this->getStatusCode($e));
        
        echo $this->renderErrorPage($e);
    }
    
    private function renderErrorPage(\Throwable $e) {
        $statusCode = $this->getStatusCode($e);
        $message = $this->getUserMessage($e);
        
        if (defined('CONFIG_DEBUG') && CONFIG_DEBUG) {
            return $this->renderDebugPage($e);
        }
        
        return $this->renderProductionPage($statusCode, $message);
    }
    
    private function renderDebugPage(\Throwable $e) {
        ob_start();
        include PATH . 'system/Framework/Views/debug_error.php';
        return ob_get_clean();
    }
    
    private function renderProductionPage($statusCode, $message) {
        $platform = defined('CONFIG_PLATFORM') ? CONFIG_PLATFORM : 'EasyAPP';
        $version = defined('CONFIG_VERSION') ? CONFIG_VERSION : '1.7.0';
        
        return "<!DOCTYPE html>
<html>
<head>
    <title>Error {$statusCode}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; padding: 20px; background: #f8fafc; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .error-container { max-width: 500px; padding: 40px; border-radius: 12px; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #e53e3e; margin-bottom: 20px; font-size: 2rem; }
        p { line-height: 1.6; margin-bottom: 15px; color: #4a5568; }
        .error-code { font-size: 4rem; color: #cbd5e0; margin-bottom: 10px; }
        .footer { color: #a0aec0; font-size: 0.875rem; margin-top: 30px; }
    </style>
</head>
<body>
    <div class='error-container'>
        <div class='error-code'>{$statusCode}</div>
        <h1>Oops! Something went wrong</h1>
        <p>{$message}</p>
        <div class='footer'>{$platform} v{$version}</div>
    </div>
</body>
</html>";
    }
    
    public function log(\Throwable $e) {
        $this->logger->exception($e);
    }
    
    private function getStatusCode(\Throwable $e) {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }
        
        return 500;
    }
    
    private function getUserMessage(\Throwable $e) {
        if (method_exists($e, 'getUserMessage')) {
            return $e->getUserMessage();
        }
        
        return 'An unexpected error occurred. Please try again later.';
    }
}