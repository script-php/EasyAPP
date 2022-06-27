<?php

/**
* @package      EasyAPP
* @version      v1.2.0
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

$loader->load([
    'namespace' => 'App\Pages',
    'directory' => $paths->app_pages,
    'recursive' => true
]);

require $paths->app_dir . '/config.php'; // app config
require $paths->app_dir . '/helper.php'; // custom functions

$db = new System\Database\DB(); // database connection
$hooks = new System\Hooks\Hook(); // hooks system

$hooks->register('start'); // register a new hook
$hooks->set('start', 'System\Router\SimpleRouter/load'); // attach a simple router to the hook
$hooks->run('start'); // execute the hook