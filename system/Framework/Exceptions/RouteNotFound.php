<?php

namespace System\Framework\Exceptions;

class RouteNotFound extends FrameworkException {
    protected $statusCode = 404;
    protected $userMessage = 'The requested page was not found';
}