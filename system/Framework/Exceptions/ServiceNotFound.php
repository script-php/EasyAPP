<?php

namespace System\Framework\Exceptions;

class ServiceNotFound extends FrameworkException {
    protected $statusCode = 500;
    protected $userMessage = 'A required service could not be found';
}