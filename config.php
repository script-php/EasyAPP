<?php
date_default_timezone_set('Europe/Bucharest');

/**
 * Load environment variables from .env file
 * This framework uses a custom EnvReader class to load environment variables.
 * Make sure to create a .env file in the root directory of your project.
 * The .env file should contain key-value pairs in the format KEY=VALUE.
 * Example:
 *   DEBUG=true
 *   ENVIRONMENT=dev
 * We can use arrays by separating values with commas.
 * Example:
 *   SERVICES=service1,service2,service3
 *   or
 *   SERVICES=["service1","service2","service3"]
 *   or
 *   SERVICES={"service1","service2","service3"}
 * 
 * 
 * This config can be overridden in app/config.php file.
 * For having different settings for app and framework.
 */

$config['debug'] = !empty(env('DEBUG')) ? env('DEBUG') : false; // show errors
$config['environment'] = !empty(env('ENVIRONMENT')) ? env('ENVIRONMENT') : 'prod'; // dev or prod
$config['compression'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false ? 1 : 0; // output compression

$config['default_language'] = !empty(env('DEFAULT_LANGUAGE')) ? env('DEFAULT_LANGUAGE') : 'en-gb'; // default language code
$config['timezone'] = !empty(env('TIMEZONE')) ? env('TIMEZONE') : 'UTC'; // default timezone

$_SERVER['SERVER_NAME'] = !empty(env('DOMAIN')) ? env('DOMAIN') : 'localhost'; // TODO: change this with your domain

$config['base_url'] = !empty(env('BASE_URL')) ? env('BASE_URL') : 'http://'.$_SERVER['SERVER_NAME'].'/';
$config['url'] = !empty(env('URL')) ? env('URL') : 'http://'.$_SERVER['SERVER_NAME'].'/';

$config['db_driver'] = !empty(env('DB_DRIVER')) ? env('DB_DRIVER') : 'mysql'; // mysql or sqlsrv
$config['db_hostname'] = !empty(env('DB_HOSTNAME')) ? env('DB_HOSTNAME') : 'localhost';
$config['db_database'] = !empty(env('DB_DATABASE')) ? env('DB_DATABASE') : 'test';
$config['db_username'] = !empty(env('DB_USERNAME')) ? env('DB_USERNAME') : 'root';
$config['db_password'] = !empty(env('DB_PASSWORD')) ? env('DB_PASSWORD') : '';
$config['db_port'] = !empty(env('DB_PORT')) ? env('DB_PORT') : '3306';

$config['domain'] = !empty(env('DOMAIN')) ? env('DOMAIN') : 'localhost'; // used for login
$config['session_name'] = !empty(env('SESSION_NAME')) ? env('SESSION_NAME') : 'session'; // used for login
$config['session_time'] = !empty(env('SESSION_TIME')) ? env('SESSION_TIME') : 31556926;
$config['services'] = !empty(env('SERVICES')) ? env('SERVICES') : []; // list of services to load (already parsed as array)
