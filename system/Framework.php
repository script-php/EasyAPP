<?php

/**
* @package      EasyAPP Framework
* @version      v1.5.1
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

use System\Framework\Exceptions;

session_start();

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
$config['dir_library'] = $config['dir_system'] . $config['dir_library'];
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

if (is_file(PATH . 'system/Vendor/autoload.php')) {
    require 'system/Vendor/autoload.php';   
}
else {
    include rtrim($config['dir_system'], '\\/ ') . DIRECTORY_SEPARATOR . 'Autoloader.php';

    $loader = new System\Autoloader();
    
    $loader->load([
        'namespace' => 'System\Framework',
        'directory' => CONFIG_DIR_FRAMEWORK,
        'recursive' => true
    ]);
    
    $loader->load([
        'namespace' => 'System\Library',
        'directory' => $config['dir_library'],
        'recursive' => true
    ]);
}

$registry = new System\Framework\Registry();

$registry->set('appName', 'Framework');
$registry->set('version', '1.0.0');
$registry->set('environment', 'development');
$registry->set('debug', true);
$registry->set('appPath', __DIR__);

$registry->set('proxy', new System\Framework\Proxy($registry));

$loader = new System\Framework\Load($registry);

$registry->set('load', $loader);

$request = $registry->get('request');

$errorConfig = [
    RouteNotFound::class => [404, "The requested page was not found."],
    MethodNotFound::class => [405, "The requested method is not allowed."],
    MagicMethodCall::class => [500, "An internal server error occurred."],
    ControllerNotFound::class => [404, "The requested page was not found."],
    ModelNotFound::class => [500, "An internal server error occurred."],
    LibraryNotFound::class => [500, "An internal server error occurred."],
    ViewNotFound::class => [500, "An internal server error occurred."],
    ServiceNotFound::class => [500, "An internal server error occurred."],
    
    DatabaseConfiguration::class => [500, "Database configuration error."],
    PDOExtensionNotFound::class => [500, "PDO extension not found."],
    DatabaseConnection::class => [500, "Database connection error."],
    DatabaseQuery::class => [500, "Database query error."],

    \Exception::class => [500, "An internal server error occurred."],
];

try {
    if (!empty(CONFIG_SERVICES)) {
        foreach (CONFIG_SERVICES as $action) {
            ($registry->get('load'))->service($action);
        }
    }

    if(isset($request->get['rewrite'])) {

        $registry->set('router', new System\Framework\Router($registry));

        $router = $registry->get('router');

        if(is_file($config['dir_app'] . 'router.php')) {
            include $config['dir_app'] . 'router.php'; // app config
        }

        if(is_file(PATH . 'router.php')) {
            include 'router.php'; // app config
        }

        $router->dispatch();
    }
    else {
        // Determine and execute route
        if (empty($request->get['route'])) {
            $route = CONFIG_ACTION_ROUTER;
        } elseif (isset($request->get['route']) && !empty($request->get['route'])) {
            $route = $request->get['route'];
        } else {
            $route = CONFIG_ACTION_ERROR;
        }

        $registry->get('load')->runController($route);
    }
    
    $registry->get('response')->output();
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