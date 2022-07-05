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

include rtrim($paths->dir_system, '\\/ ') . DIRECTORY_SEPARATOR . 'Autoloader.php';

$loader = new App\Autoloader();

$loader->load([
    'namespace' => 'System\Framework',
    'directory' => $paths->dir_framework,
    'recursive' => true
]);

$loader->load([
    'namespace' => 'System\Library',
    'directory' => $paths->dir_library,
    'recursive' => true
]);

require $paths->dir_app . '/config.php'; // app config
require $paths->dir_app . '/helper.php'; // custom functions

include $paths->dir_system . '/Controller.php';

$registry = new System\Framework\Registry();
$database   = new System\Framework\DB(); // database connection

$registry->set('db', $database->connect(DB_HOSTNAME,DB_DATABASE,DB_USERNAME,DB_PASSWORD,DB_PORT));
$registry->set('path', $paths);
$registry->set('hooks', new System\Framework\Hook());
$registry->set('util', new System\Framework\Util());
$registry->set('mail', new System\Framework\Mail());
$registry->set('load', new System\Framework\Load($registry));
$registry->set('language', new System\Framework\Language('ro-ro'));

$router = new System\Framework\SimpleRouter($registry);
$router->loadPage();