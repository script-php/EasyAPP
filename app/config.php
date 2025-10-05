<?php

/**
 * Application Configuration
 */
$config['action_router'] = 'home'; // controller file for default page.
$config['action_error'] = 'not_found'; // controller file for handling errors.

$config['default_language'] = 'en-gb';

/**
 * Services
 * Array of services to load at startup.
 * Format: 'serviceName|methodName' or just 'serviceName' to call index method.
 * These services will be loaded before any controller is executed.
 */
$config['services'] = [
    //'something|index',    // Loads SomethingService->index() method
];