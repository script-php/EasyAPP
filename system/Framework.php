<?php

/**
* @package      EasyAPP Framework
* @version      v1.7.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

/**
 * This file initializes the EasyAPP Framework environment.
 * It sets up error handling, autoloading, configuration, and bootstraps the application.
 * It is included at the start of both web and CLI entry points.
 * Do not modify this file directly. Instead, customize your application via the config files and environment variables.
 */

session_start();

/**
 * Enable Composer autoloader for framework classes and third-party packages
 * Make sure to run `composer install` to generate the autoload files.
 */
if (is_file(PATH . 'system/Vendor/autoload.php')) {
    require 'system/Vendor/autoload.php';   
}

/**
 * Helper functions - some functions that will help you to develope your app fast.
 */
include PATH . 'system/Helper.php';

/**
 * Environment variables - load from .env file if it exists
 */
if (is_file(PATH . '.env')) {
    $env = new System\Framework\EnvReader('.env');
    $env->load();
}

/**
 * config - Initialize modern configuration system
 */
$configManager = System\Framework\Config::getInstance();

/**
 * Load additional config files
 */
if (is_file(PATH . 'config.php')) {
    $configManager->load(PATH . 'config.php');   
}
if (is_file(PATH . 'app/config.php')) {
    $configManager->load(PATH . 'app/config.php'); // app config
}

/**
 * Get final configuration array for backward compatibility
 */
$config = $configManager->all();

/** 
 * Enable error display in debug mode 
 */
if ($config['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

/**
 * Apply directory overrides if needed
 */
if (defined('DIR_APP')) {
    $config['dir_app'] = DIR_APP;
    $configManager->set('dir_app', DIR_APP);
}

/**
 * System directories
 * These are the full paths to important directories used by the framework.
 * Adjust these paths in config.php if your directory structure is different.
 */
$config['dir_framework'] = $config['dir_system'] . $config['dir_framework'];
$config['dir_assets'] = PATH . $config['dir_assets'];
$config['dir_storage'] = PATH . $config['dir_storage'];


/**
 * Framework directories
 * These are the full paths to important directories used by the framework.
 * Adjust these paths in config.php if your directory structure is different.
 */
$config['dir_controller'] = $config['dir_app'] . $config['dir_controller'];
$config['dir_model'] = $config['dir_app'] . $config['dir_model'];
$config['dir_language'] = $config['dir_app'] . $config['dir_language'];
$config['dir_view'] = $config['dir_app'] . $config['dir_view'];
$config['dir_service'] = $config['dir_app'] . $config['dir_service'];
$config['dir_library'] = $config['dir_app'] . $config['dir_library'];

/**
 * Include core framework classes
 */
include $config['dir_system'] . 'Controller.php';
include $config['dir_system'] . 'Model.php';
include $config['dir_system'] . 'Library.php';
include $config['dir_system'] . 'Service.php';

/**
 * Load app helper functions if they exist
 */
if (is_file($config['dir_app'] . 'helper.php')) {
    include $config['dir_app'] . 'helper.php'; // custom functions
}

/**
 * Set the default timezone for the application
 * This ensures that all date/time functions use the correct timezone.
 */
date_default_timezone_set($config['timezone']);

/**
 * Create configuration constants for backward compatibility
 * These constants are prefixed with CONFIG_ to avoid naming conflicts.
 */
$configManager->createConstants($config);

/**
 * Initialize the framework registry (used by both CLI and web)
 * The registry stores global objects and configuration accessible throughout the application.
 */
$registry = new System\Framework\Registry();
$registry->set('appName', $config['platform']);
$registry->set('version', $config['version']);
$registry->set('app_env', $config['app_env']);
$registry->set('debug', $config['debug']);
$registry->set('default_language', $config['default_language']);
$registry->set('timezone', $config['timezone']);
$registry->set('appPath', __DIR__);
$registry->set('config', $config);
$registry->set('proxy', new System\Framework\Proxy($registry));
$registry->set('load', new System\Framework\Load($registry));

/**
 * Initialize CSRF protection if enabled
 * Make sure to include CSRF tokens in your forms and validate them on submission.
 * Example form input:
 * <input type="hidden" name="csrf_token" value="<?php echo $csrf->getToken(); ?>">
 */
if ($config['csrf_protection']) {
    $csrf = new System\Framework\Csrf($registry);
    $registry->set('csrf', $csrf);
}

/**
 * Initialize the framework for web requests
 * If CLI_MODE is defined, we are in CLI context (easy).
 * This prevents conflicts between web and CLI initializations.
 */
if(defined('CLI_MODE') !== true) {
    
    /**
     * Start output buffering for framework-level compression
     * This captures all output (echo, print, etc.) for compression before sending to the client.
     * Make sure to call ob_end_flush() or ob_get_clean() later to send the output.
     */
    if ($config['compression'] > 0) {
        ob_start();
    }

    try {
        /**
         * Map specific exceptions to HTTP status codes and messages.
         * This allows for consistent error handling and user-friendly messages.
         */
        $errorConfig = [
            System\Framework\Exceptions\RouteNotFound::class => [404, "The requested page was not found."],
            System\Framework\Exceptions\MethodNotFound::class => [405, "The requested method is not allowed."],
            System\Framework\Exceptions\MagicMethodCall::class => [500, "An internal server error occurred."],
            System\Framework\Exceptions\ControllerNotFound::class => [404, "The requested page was not found."],
            System\Framework\Exceptions\ModelNotFound::class => [500, "An internal server error occurred."],
            System\Framework\Exceptions\LibraryNotFound::class => [500, "An internal server error occurred."],
            System\Framework\Exceptions\ViewNotFound::class => [500, "An internal server error occurred."],
            System\Framework\Exceptions\ServiceNotFound::class => [500, "An internal server error occurred."],
            
            System\Framework\Exceptions\DatabaseConfiguration::class => [500, "Database configuration error."],
            System\Framework\Exceptions\PDOExtensionNotFound::class => [500, "PDO extension not found."],
            System\Framework\Exceptions\DatabaseConnection::class => [500, "Database connection error."],
            System\Framework\Exceptions\DatabaseQuery::class => [500, "Database query error."]
        ];
        
        /**
         * Add catch-all for unexpected exceptions
         */
        $errorConfig[\Exception::class] = [500, "An internal server error occurred."];

        /**
         * Initialize and configure the router
         * The router handles incoming HTTP requests and maps them to the appropriate controllers/actions.
         * It supports custom routing rules defined in app/router.php.
         */
        $registry->set('router', new System\Framework\Router($registry));
        $router = $registry->get('router');
        if(is_file($config['dir_app'] . 'router.php')) {
            include $config['dir_app'] . 'router.php'; // app config
        }

        /**
         * Only initialize database if credentials are provided
         * This prevents unnecessary database connections for apps that don't use a database.
         * The database connection is established using the configuration parameters defined in config.php or environment variables.
         */
        if (!empty($config['db_hostname']) && !empty($config['db_database']) && !empty($config['db_username'])) {
            $registry->set('db', new System\Framework\Db($config['db_driver'], $config['db_hostname'], $config['db_database'], $config['db_username'], $config['db_password'], $config['db_port'], '', ''));
        }

        /**
         * Load configured services
         * Services are reusable components that provide specific functionality (e.g. authentication, caching, etc.).
         * They are defined in the config 'services' array and loaded automatically during initialization.
         * Example config:
         * 'services' => ['analytics|init', 'notifications|start']
         * This will load the 'analytics' service and call its 'init' method, then load the 'notifications' service and call its 'start' method.
         * Services are loaded in a try-catch block to prevent a single service failure from breaking the entire application.
         */
        if (!empty($config['services'])) {
            foreach ($config['services'] as $action) {
                try {
                    ($registry->get('load'))->service($action);
                } catch (\Exception $e) {
                    error_log("Failed to load service: " . $action); // Log service loading failure but don't stop execution
                }
            }
        }

        /**
         * Get the current request object
         * This object contains information about the current HTTP request.
         * It is used to retrieve request parameters, headers, and other relevant data.
         */
        $request = $registry->get('request');

        /**
         * Determine and dispatch the appropriate route
         * If 'route' parameter exists, use legacy routing
         * Otherwise, use the modern router with clean URLs
         */
        if(!isset($request->get['route'])) {

            /**
             * Use custom router for clean URLs
             */
            $router->dispatch();

        }
        else {
            /**
             * If no custom router, fall back to default routing behavior
             */
            if (isset($request->get['route']) && !isset($request->get['rewrite'])) {

                $route = $request->get('route');
                if(empty($config['app_home']) && empty($config['app_error'])) {
                    if(!empty($route)) {

                        /**
                         * Run the requested page
                         */
                        $registry->get('load')->runController($route);

                    }
                    else {

                        /**
                         * Run the default page
                         */
                        defaultPage();
                        exit(); // just to be sure

                    }
                }
                else {

                    /**
                     * Run the requested page or default page
                     */
                    $route = $request->get('route');
                    $route = !empty($route) ? $route : $config['app_home'];
                    /**
                     * Run the requested page or default page
                     */
                    $registry->get('load')->runController($route);

                }

            }
            else {
                /**
                 * Run the default page
                 */
                defaultPage();
            }
        }

        /**
         * Handle framework-level compression
         */
        $response = $registry->get('response');
        
        /**
         * If compression is enabled, capture all output (echo, pre, etc.) and compress it before sending to the client.
         * This ensures that all output, regardless of how it was generated, is compressed consistently.
         */
        if ($config['compression'] > 0) {

            /**
             * Get all captured output (echo, pre, runController, etc.)
             */
            $directOutput = ob_get_clean();

            /**
             * Get response system output (setOutput content)
             */
            $responseOutput = $response->getOutput();

            /**
             * Combine all output streams
             */
            $allOutput = $directOutput;
            if ($responseOutput) {
                $allOutput .= $responseOutput;
            }
            
            /**
             * Compress the complete output
             */
            $compressedOutput = $response->compressOutput($allOutput, $config['compression']);
            
            /**
             * Send all headers (including Content-Encoding from compression)
             */
            if (!headers_sent()) {
                foreach ($response->getHeaders() as $header) {
                    header($header, true);
                }
            }
            
            /**
             * Send the compressed content
             */
            echo $compressedOutput;

        } else {
            /**
             * If no compression, just flush any output buffer that may exist.
             * This ensures that any output captured by ob_start() at the beginning is sent to the client.
             */
            ob_end_flush();
            // No compression - send output normally
            $response->output();
        }

    } 
    catch (\Exception $e) {

        /**
         * Handle exceptions and display user-friendly error pages
         * Uses the $errorConfig mapping to determine the appropriate HTTP status code and message.
         * In debug mode, a detailed error page with stack trace is shown.
         * In production mode, a generic error page is displayed to avoid exposing sensitive information.
         */
        $exceptionClass = get_class($e);
        
        /**
         * Determine the appropriate status code and message based on the exception class.
         * If the exception class is not mapped, default to 500 Internal Server Error.
         */
        if (isset($errorConfig[$exceptionClass])) {
            list($statusCode, $errorMessage) = $errorConfig[$exceptionClass];
        } else {
            $statusCode = 500;
            $errorMessage = "An unexpected error occurred.";
        }

        /**
         * Set HTTP status code
         */
        http_response_code($statusCode);

        /**
         * Log error
         * The error is logged using the DebugError class to ensure consistent logging format.
         * This helps with debugging and monitoring application issues.
         */
        $debug = new System\Framework\DebugError();
        $debug->log($e);

        /**
         * Display error
         * Uses the $errorConfig mapping to determine the appropriate HTTP status code and message.
         * In debug mode, a detailed error page with stack trace is shown.
         * In production mode, a generic error page is displayed to avoid exposing sensitive information.
         */
        if (defined('CONFIG_DEBUG') && CONFIG_DEBUG) {
            $debug->display($e);
        } else {
            displayErrorPage($statusCode, $errorMessage);
        }

    }

}

/**
 * Initialize the framework for CLI requests
 * If CLI_MODE is defined, we are in CLI context (easy).
 * This prevents conflicts between web and CLI initializations.
 */
if(defined('CLI_MODE')) {
    include PATH . 'system/Cli.php';

   
}