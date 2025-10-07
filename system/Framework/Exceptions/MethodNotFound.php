<?php

namespace System\Framework\Exceptions;

class MethodNotFound extends FrameworkException {
    protected $statusCode = 405;
    protected $userMessage = 'The requested method is not allowed';
}