<?php

/**
* @package      CSRF Protection
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Csrf {
    private $registry;
    private $sessionKey = '_csrf_tokens';
    private $tokenLength = 32;
    private $maxTokens = 10; // Limit stored tokens to prevent memory issues
    
    public function __construct($registry) {
        $this->registry = $registry;
        $this->initializeSession();
    }
    
    /**
     * Initialize CSRF session storage
     */
    private function initializeSession() {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }
    
    /**
     * Generate a new CSRF token
     * @param string $action Optional action identifier for token scoping
     * @return string The generated token
     */
    public function generateToken($action = 'default') {
        $token = $this->createSecureToken();
        $timestamp = time();
        
        // Store token with timestamp and action
        $_SESSION[$this->sessionKey][$token] = [
            'action' => $action,
            'timestamp' => $timestamp,
            'used' => false
        ];
        
        // Debug: Log token storage
        error_log("CSRF DEBUG: Generated token {$token} for action {$action}");
        error_log("CSRF DEBUG: Session tokens after generation: " . print_r($_SESSION[$this->sessionKey], true));
        
        // Temporarily disable cleanup for debugging
        // $this->cleanupTokens();
        
        return $token;
    }
    
    /**
     * Validate a CSRF token
     * @param string $token The token to validate
     * @param string $action Optional action to validate against
     * @param int $maxAge Maximum age in seconds (default 3600 = 1 hour)
     * @param bool $singleUse Whether token should be invalidated after use
     * @return bool True if token is valid
     */
    public function validateToken($token, $action = 'default', $maxAge = 3600, $singleUse = true) {
        // Check if CSRF protection is enabled
        if (!$this->isEnabled()) {
            return true; // Skip validation if disabled
        }
        
        if (empty($token) || !isset($_SESSION[$this->sessionKey][$token])) {
            // Debug: Log validation failure
            error_log("CSRF DEBUG: Validation failed for token {$token}");
            error_log("CSRF DEBUG: Available tokens: " . print_r(array_keys($_SESSION[$this->sessionKey] ?? []), true));
            return false;
        }
        
        $tokenData = $_SESSION[$this->sessionKey][$token];
        
        // Check if token was already used (for single-use tokens)
        if ($singleUse && $tokenData['used']) {
            return false;
        }
        
        // Check action match
        if ($tokenData['action'] !== $action) {
            return false;
        }
        
        // Check token age
        if ((time() - $tokenData['timestamp']) > $maxAge) {
            unset($_SESSION[$this->sessionKey][$token]);
            return false;
        }
        
        // Mark token as used if single-use
        if ($singleUse) {
            $_SESSION[$this->sessionKey][$token]['used'] = true;
        }
        
        return true;
    }
    
    /**
     * Get CSRF token from request (POST, GET, or headers)
     * @return string|null The token from request
     */
    public function getTokenFromRequest() {
        // Check POST data directly (most common)
        if (!empty($_POST['_csrf_token'])) {
            return $_POST['_csrf_token'];
        }
        
        // Check GET data (less secure, but sometimes needed)
        if (!empty($_GET['_csrf_token'])) {
            return $_GET['_csrf_token'];
        }
        
        // Check HTTP headers for AJAX requests
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (!empty($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }
        
        // Alternative header check (case-insensitive)
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-csrf-token' && !empty($value)) {
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * Validate CSRF token from current request
     * @param string $action Optional action to validate against
     * @return bool True if request has valid CSRF token
     */
    public function validateRequest($action = 'default') {
        $token = $this->getTokenFromRequest();
        return $this->validateToken($token, $action);
    }
    
    /**
     * Generate HTML input field for CSRF token
     * @param string $action Optional action for token scoping
     * @return string HTML input field
     */
    public function getTokenField($action = 'default') {
        $token = $this->generateToken($action);
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Get token for JavaScript/AJAX use
     * @param string $action Optional action for token scoping
     * @return string The token value
     */
    public function getTokenForAjax($action = 'default') {
        return $this->generateToken($action);
    }
    
    /**
     * Create a cryptographically secure token
     * @return string The generated token
     */
    private function createSecureToken() {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($this->tokenLength / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $token = openssl_random_pseudo_bytes($this->tokenLength / 2, $strong);
            if ($strong) {
                return bin2hex($token);
            }
        }
        
        // Fallback to less secure method if secure functions unavailable
        return hash('sha256', uniqid(mt_rand(), true));
    }
    
    /**
     * Clean up old and used tokens
     */
    private function cleanupTokens() {
        if (count($_SESSION[$this->sessionKey]) <= $this->maxTokens) {
            return;
        }
        
        $currentTime = time();
        $cleaned = [];
        
        // Remove expired tokens (older than 1 hour)
        foreach ($_SESSION[$this->sessionKey] as $token => $data) {
            if (($currentTime - $data['timestamp']) < 3600 && !$data['used']) {
                $cleaned[$token] = $data;
            }
        }
        
        // If still too many tokens, keep only the most recent ones
        if (count($cleaned) > $this->maxTokens) {
            uasort($cleaned, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            $cleaned = array_slice($cleaned, 0, $this->maxTokens, true);
        }
        
        $_SESSION[$this->sessionKey] = $cleaned;
    }
    
    /**
     * Check if CSRF protection is enabled
     * @return bool True if enabled
     */
    public function isEnabled() {
        return defined('CONFIG_CSRF_PROTECTION') ? CONFIG_CSRF_PROTECTION : true;
    }
    
    /**
     * Clear all CSRF tokens from session
     */
    public function clearTokens() {
        $_SESSION[$this->sessionKey] = [];
    }
    
    /**
     * Get token statistics for debugging
     * @return array Token statistics
     */
    public function getTokenStats() {
        $tokens = $_SESSION[$this->sessionKey] ?? [];
        $active = 0;
        $used = 0;
        $expired = 0;
        $currentTime = time();
        
        foreach ($tokens as $data) {
            if ($data['used']) {
                $used++;
            } elseif (($currentTime - $data['timestamp']) > 3600) {
                $expired++;
            } else {
                $active++;
            }
        }
        
        return [
            'total' => count($tokens),
            'active' => $active,
            'used' => $used,
            'expired' => $expired
        ];
    }
}