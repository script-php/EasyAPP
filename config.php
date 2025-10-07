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


$_SERVER['SERVER_NAME'] = !empty(env('DOMAIN')) ? env('DOMAIN') : 'localhost'; // TODO: change this with your domain

$config['base_url'] = !empty(env('BASE_URL')) ? env('BASE_URL') : 'http://'.$_SERVER['SERVER_NAME'].'/'; // base url of the app, with trailing slash
$config['url'] = !empty(env('URL')) ? env('URL') : 'http://'.$_SERVER['SERVER_NAME'].'/'; // full url of the app, with trailing slash

$config['domain'] = !empty(env('DOMAIN')) ? env('DOMAIN') : 'localhost'; // used for cookie domain
$config['session_name'] = !empty(env('SESSION_NAME')) ? env('SESSION_NAME') : 'session'; // used for login
$config['session_time'] = !empty(env('SESSION_TIME')) ? env('SESSION_TIME') : 31556926;
$config['services'] = !empty(env('SERVICES')) ? env('SERVICES') : []; // list of services to load (already parsed as array)
