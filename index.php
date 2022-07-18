<?php

/**
* @package      EasyAPP
* @version      v1.2.5
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

define('PATH', __DIR__ . DIRECTORY_SEPARATOR);

chdir(PATH);



require PATH . 'System/Config.php'; // framework config

$config = new System\Config(PATH);

if($config->debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

include rtrim($config->dir_system, '\\/ ') . DIRECTORY_SEPARATOR . 'Autoloader.php';

$loader = new System\Autoloader();

$loader->load([
    'namespace' => 'System\Framework',
    'directory' => $config->dir_framework,
    'recursive' => true
]);

$loader->load([
    'namespace' => 'System\Library',
    'directory' => $config->dir_library,
    'recursive' => true
]);

include $config->dir_system . 'Controller.php';
include $config->dir_system . 'Model.php';

require $config->dir_app . 'config.php'; // app config
require $config->dir_app . 'helper.php'; // custom functions

$registry = new System\Framework\Registry();

$registry->set('config', $config);
$registry->set('db', new System\Framework\DB($config->db_hostname,$config->db_database,$config->db_username,$config->db_password,$config->db_port)); // database connection
$registry->set('hooks', new System\Framework\Hook($registry));
$registry->set('util', new System\Framework\Util($registry));
$registry->set('mail', new System\Framework\Mail($registry));
$registry->set('load', new System\Framework\Load($registry));

$language = new System\Framework\Language($registry);
$language->directory('ro-ro');
$registry->set('language', $language);
$registry->set('request', new System\Framework\Request($registry));

$router = new System\Framework\SimpleRouter($registry);
$router->loadPage();