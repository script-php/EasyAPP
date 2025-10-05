<?php

/**
* @package      EasyAPP Framework
* @version      v1.6.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

// This file is part of the EasyAPP Framework.
// EasyAPP Framework is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// EasyAPP Framework is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with EasyAPP Framework.  If not, see <http://www.gnu.org/licenses/>.

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
    $registry->set('appPath', __DIR__);

    $registry->set('proxy', new System\Framework\Proxy($registry));

    $loader = new System\Framework\Load($registry);
    $registry->set('load', $loader);

    return $registry;
}

// Bootstrap web application (only called from index.php)
function bootstrap() {
    global $config;
    
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
        $registry->get('response')->output(); // send output to browser

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