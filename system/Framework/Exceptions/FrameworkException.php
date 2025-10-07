<?php

namespace System\Framework\Exceptions;

abstract class FrameworkException extends \Exception {
    protected $statusCode = 500;
    protected $userMessage = 'An error occurred';
    
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getUserMessage() {
        return $this->userMessage;
    }
    
    public function getContext() {
        return [
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }
}