<?php

/**
* @package      EasyAPP Framework
* @version      v1.6.0
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

// Register custom autoloader for framework classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'System\\') === 0) {
        $file = PATH . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

session_start();

if (is_file(PATH . 'system/Vendor/autoload.php')) {
    require 'system/Vendor/autoload.php';   
}

if (is_file(PATH . '.env')) {
    $env = new System\Framework\EnvReader('.env');
    $env->load();
}

// config
include PATH . 'system/Default.php'; // Framework default config.
include PATH . 'system/Helper.php'; // Helper functions - some functions that will help you to develope your app fast.

if(defined('DIR_APP')) {
    $config['dir_app'] = DIR_APP;
}

$config['dir_controller'] = $config['dir_app'] . $config['dir_controller'];
$config['dir_model'] = $config['dir_app'] . $config['dir_model'];
$config['dir_language'] = $config['dir_app'] . $config['dir_language'];
$config['dir_view'] = $config['dir_app'] . $config['dir_view'];
$config['dir_service'] = $config['dir_app'] . $config['dir_service'];

$config['dir_framework'] = $config['dir_system'] . $config['dir_framework'];
$config['dir_library'] = $config['dir_app'] . $config['dir_library'];
$config['dir_assets'] = PATH . $config['dir_assets'];
$config['dir_storage'] = PATH . $config['dir_storage'];
$config['compression'] = !empty(env('COMPRESSION')) ? env('COMPRESSION') : 0; // 0 = no compression, 1-9 = gzip compression levels
$config['timezone'] = !empty(env('TIMEZONE')) ? env('TIMEZONE') : 'UTC'; // default timezone
$config['debug'] = !empty(env('DEBUG')) ? env('DEBUG') : false; // show errors
$config['environment'] = !empty(env('ENVIRONMENT')) ? env('ENVIRONMENT') : 'prod'; // dev or prod
$config['default_language'] = !empty(env('DEFAULT_LANGUAGE')) ? env('DEFAULT_LANGUAGE') : 'en-gb'; // default language code

$config['db_driver'] = !empty(env('DB_DRIVER')) ? env('DB_DRIVER') : 'mysql'; // mysql or sqlsrv
$config['db_hostname'] = !empty(env('DB_HOSTNAME')) ? env('DB_HOSTNAME') : 'localhost';
$config['db_database'] = !empty(env('DB_DATABASE')) ? env('DB_DATABASE') : 'test';
$config['db_username'] = !empty(env('DB_USERNAME')) ? env('DB_USERNAME') : 'root';
$config['db_password'] = !empty(env('DB_PASSWORD')) ? env('DB_PASSWORD') : '';
$config['db_port'] = !empty(env('DB_PORT')) ? env('DB_PORT') : '3306';

// Security settings
$csrf_env = env('CSRF_PROTECTION');
if ($csrf_env !== null) {
    $config['csrf_protection'] = filter_var($csrf_env, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (strtolower($csrf_env) === 'true');
} else {
    $config['csrf_protection'] = false; // Default disabled
}

if (is_file(PATH . 'config.php')) {
    include 'config.php';   
}
if(is_file($config['dir_app'] . 'config.php')) {
    include $config['dir_app'] . 'config.php'; // app config
}
if(is_file($config['dir_app'] . 'helper.php')) {
    include $config['dir_app'] . 'helper.php'; // custom functions
}

foreach($config as $key => $value) {
    define("CONFIG_" . strtoupper($key), $value);
}

if($config['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

include $config['dir_system'] . 'Controller.php';
include $config['dir_system'] . 'Model.php';
include $config['dir_system'] . 'Library.php';
include $config['dir_system'] . 'Service.php';


// Initialize the framework registry (used by both CLI and web)
function initializeFramework() {
    global $config;
    
    $registry = new System\Framework\Registry();

    $registry->set('appName', CONFIG_PLATFORM);
    $registry->set('version', CONFIG_VERSION);
    $registry->set('environment', CONFIG_ENVIRONMENT);
    $registry->set('debug', CONFIG_DEBUG);
    $registry->set('default_language', defined('CONFIG_DEFAULT_LANGUAGE') ? CONFIG_DEFAULT_LANGUAGE : 'en-gb');
    $registry->set('timezone', defined('CONFIG_TIMEZONE') ? CONFIG_TIMEZONE : 'UTC');
    $registry->set('appPath', __DIR__);

    $registry->set('proxy', new System\Framework\Proxy($registry));

    $loader = new System\Framework\Load($registry);
    $registry->set('load', $loader);

    // Initialize CSRF protection if enabled
    if (defined('CONFIG_CSRF_PROTECTION') && CONFIG_CSRF_PROTECTION) {
        $csrf = new System\Framework\Csrf($registry);
        $registry->set('csrf', $csrf);
    }

    return $registry;
}

// Bootstrap web application (only called from index.php)
function bootstrap() {
    global $config;
    
    // Start output buffering for framework-level compression
    if (CONFIG_COMPRESSION > 0) {
        ob_start();
    }
    
    try {
        $registry = initializeFramework();
        $request = $registry->get('request');

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
        
        // Add catch-all for unexpected exceptions
        $errorConfig[\Exception::class] = [500, "An internal server error occurred."];

        $registry->set('router', new System\Framework\Router($registry));
        $router = $registry->get('router');
        if(is_file($config['dir_app'] . 'router.php')) {
            include $config['dir_app'] . 'router.php'; // app config
        }

        $registry->set('db', new System\Framework\Db($config['db_driver'],$config['db_hostname'],$config['db_database'],$config['db_username'],$config['db_password'],$config['db_port'], '', ''));

        if (!empty(CONFIG_SERVICES)) {
            foreach (CONFIG_SERVICES as $action) {
                try {
                    ($registry->get('load'))->service($action);
                } catch (\Exception $e) {
                    error_log("Failed to load service: " . $action); // Log service loading failure but don't stop execution
                }
            }
        }

        if(isset($request->get['rewrite']) || (!isset($request->get['rewrite']) && !isset($request->get['route']))) {
            $router->dispatch();
        }
        else {
            if (isset($request->get['route']) && !isset($request->get['rewrite'])) {
                $route = $request->get('route');
                if(empty(CONFIG_ACTION_ROUTER) && empty(CONFIG_ACTION_ERROR)) {
                    if(!empty($route)) {
                        $registry->get('load')->runController($route); // run requested page
                    }
                    else {
                        defaultPage(); // run default page
                        exit(); // just to be sure
                    }
                }
                else {
                    $route = $request->get('route');
                    
                    $route = !empty($route) ? $route : CONFIG_ACTION_ROUTER;
                    // pre($route,1);
                    $registry->get('load')->runController($route); // run requested page or default page
                }
            }
            else {
                defaultPage(); // run default page
            }
        }
        
        // Handle framework-level compression
        $response = $registry->get('response');
        
        if (CONFIG_COMPRESSION > 0) {
            // Get all captured output (echo, pre, runController, etc.)
            $directOutput = ob_get_clean();
            
            // Get response system output (setOutput content)
            $responseOutput = $response->getOutput();
            
            // Combine all output streams
            $allOutput = $directOutput;
            if ($responseOutput) {
                $allOutput .= $responseOutput;
            }
            
            // Compress the complete output
            $compressedOutput = $response->compressOutput($allOutput, CONFIG_COMPRESSION);
            
            // Send all headers (including Content-Encoding from compression)
            if (!headers_sent()) {
                foreach ($response->getHeaders() as $header) {
                    header($header, true);
                }
            }
            
            // Send the compressed content
            echo $compressedOutput;
        } else {
            // No compression - send output normally
            $response->output();
        }

    } 
    catch (\Exception $e) {

        $exceptionClass = get_class($e);
        
        if (isset($errorConfig[$exceptionClass])) {
            list($statusCode, $errorMessage) = $errorConfig[$exceptionClass];
        } else {
            $statusCode = 500;
            $errorMessage = "An unexpected error occurred.";
        }
        
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Log error
        $debug = new System\Framework\DebugError();
        $debug->log($e);
        
        // Display error
        if (defined('CONFIG_DEBUG') && CONFIG_DEBUG) {
            $debug->display($e);
        } else {
            displayErrorPage($statusCode, $errorMessage);
        }

    }
}