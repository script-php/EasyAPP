<?php

/**
* @package      EasyAPP Framework
* @version      v1.5.1
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

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
$config['dir_framework'] = $config['dir_system'] . $config['dir_framework'];
$config['dir_library'] = $config['dir_system'] . $config['dir_library'];
$config['dir_assets'] = PATH . $config['dir_assets'];
$config['dir_storage'] = PATH . $config['dir_storage'];

if(is_file($config['dir_app'] . 'config.php')) {
    include $config['dir_app'] . 'config.php'; // app config
}
if (is_file(PATH . 'config.php')) {
    include 'config.php';   
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
$request = $registry->get('request');

$errorConfig = [
    System\Framework\Exceptions\RouteNotFoundException::class => [404, "The requested page was not found."],
    System\Framework\Exceptions\MethodNotFoundException::class => [405, "The requested method is not allowed."],
    System\Framework\Exceptions\MagicMethodCallException::class => [500, "An internal server error occurred."],
    System\Framework\Exceptions\ControllerNotFoundException::class => [404, "The requested page was not found."],
    System\Framework\Exceptions\ModelNotFoundException::class => [500, "An internal server error occurred."],
    System\Framework\Exceptions\LibraryNotFoundException::class => [500, "An internal server error occurred."],
    System\Framework\Exceptions\ViewNotFoundException::class => [500, "An internal server error occurred."],
    
    System\Framework\Exceptions\DatabaseConfigurationException::class => [500, "Database configuration error."],
    System\Framework\Exceptions\PDOExtensionNotFoundException::class => [500, "PDO extension not found."],
    System\Framework\Exceptions\DatabaseConnectionException::class => [500, "Database connection error."],
    System\Framework\Exceptions\DatabaseQueryException::class => [500, "Database query error."],

    \Exception::class => [500, "An internal server error occurred."],
];

try {
    if (!empty(CONFIG_PRE_ACTION)) {
        foreach (CONFIG_PRE_ACTION as $action) {
            (new System\Framework\Action($action))->execute($registry);
        }
    }
    ($registry->get('load'))->controller((isset($request->get['rewrite']) && empty($request->get['route'])) ? CONFIG_ACTION_ERROR : ((isset($request->get['route']) && !empty($request->get['route']) ? $request->get['route'] : CONFIG_ACTION_ROUTER)));
} 
catch (\Exception $e) {

    $exceptionClass = get_class($e);
    if (isset($errorConfig[$exceptionClass])) {
        list($statusCode, $errorMessage) = $errorConfig[$exceptionClass];
        http_response_code($statusCode);
        $data['errorMessage'] = $errorMessage;
    } else {
        http_response_code(500);
        $data['errorMessage'] = "An unexpected error occurred.";
    }

    if(CONFIG_DEBUG) {
        pre($e->getMessage());
        pre($e);
    }
    else {
        $registry->get('load')->get_controller(CONFIG_ACTION_ERROR)->index($data);
    }

}

$registry->get('response')->output();