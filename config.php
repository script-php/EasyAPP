<?php
date_default_timezone_set('Europe/Bucharest');

$config['debug'] = !empty(env('DEBUG')) ? env('DEBUG') : false; // show errors
$config['environment'] = !empty(env('ENVIRONMENT')) ? env('ENVIRONMENT') : 'prod';

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

$config['servers_upload'] = [
    'local' => [
        'url' => !empty(env('SERVERS_UPLOAD_LOCAL_URL')) ? env('SERVERS_UPLOAD_LOCAL_URL') : 'http://'.$_SERVER['SERVER_NAME'].'/storage/uploads/',
    ]
];