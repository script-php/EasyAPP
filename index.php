<?php

/**
* @package      EasyAPP
* @version      v1.2.1
* @author       YoYoDeveloper / Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

define('PATH', __DIR__ . DIRECTORY_SEPARATOR);

chdir(PATH);

require PATH . 'Config/Config.php'; // framework config
require PATH . 'Config/Paths.php'; // framework paths

$paths = new Config\Paths(PATH);

include rtrim($paths->system_dir, '\\/ ') . DIRECTORY_SEPARATOR . 'Autoloader.php';

$loader = new App\Autoloader();

$loader->load([
    'namespace' => 'System',
    'directory' => $paths->system_dir,
    'recursive' => true
]);
// print_r($paths->app_controller);
// $loader->load([
//     'namespace' => 'App\Controller',
//     'directory' => $paths->app_controller,
//     'recursive' => true
// ]);

// $loader->load([
//     'namespace' => 'App\Model',
//     'directory' => $paths->app_model,
//     'recursive' => true
// ]);

require $paths->app_dir . '/config.php'; // app config
require $paths->app_dir . '/helper.php'; // custom functions

include $paths->system_dir . '/Controller.php';

$registry = new System\Framework\Registry();
$database   = new System\Framework\DB(); // database connection

$registry->set('db', $database->connect(DB_HOSTNAME,DB_DATABASE,DB_USERNAME,DB_PASSWORD,DB_PORT));
$registry->set('hooks', new System\Framework\Hook());
$registry->set('show', new System\Framework\Show());
$registry->set('util', new System\Framework\Util());
$registry->set('mail', new System\Framework\Mail());
$registry->set('path', $paths);
$registry->set('load', new System\Framework\Load($registry));

$router = new System\Framework\SimpleRouter($registry);
$router->loadPage();