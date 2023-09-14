<?php

/**
* @package      EasyAPP Framework
* @version      v1.2.7
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

include $config['dir_app'] . 'config.php'; // app config
include $config['dir_app'] . 'helper.php'; // custom functions

foreach($config as $key => $value) {
    define("CONFIG_" . strtoupper($key), $value);
}

if($config['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (is_file(PATH . 'vendor/autoload.php')) {
    require 'vendor/autoload.php';
    include $config['dir_system'] . 'Controller.php';
    include $config['dir_system'] . 'Model.php';
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

$registry->set('db', new System\Framework\DB(CONFIG_DB_HOSTNAME,CONFIG_DB_DATABASE,CONFIG_DB_USERNAME,CONFIG_DB_PASSWORD,CONFIG_DB_PORT)); // database connection
$registry->set('util', new System\Framework\Util($registry));
$registry->set('mail', new System\Framework\Mail($registry));
$registry->set('load', new System\Framework\Load($registry));
$registry->set('url', new System\Framework\Url($registry));
$registry->set('event', new System\Framework\Event($registry));

$response = new System\Framework\Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$response->setCompression(CONFIG_COMPRESSION);
$registry->set('response', $response);

$request = new System\Framework\Request($registry);
$registry->set('request', $request);

$language = new System\Framework\Language($registry);
$registry->set('language', $language);

$route = new System\Framework\Router($registry);
if (CONFIG_PRE_ACTION) {
	foreach (CONFIG_PRE_ACTION as $value) {
		$route->addPreAction(new System\Framework\Action($value));
	}
}

$route->dispatch(new System\Framework\Action(CONFIG_ACTION_ROUTER), new System\Framework\Action(CONFIG_ACTION_ERROR));

$request->sessions();

$response->output();