<?php

date_default_timezone_set('Europe/Bucharest');

$config['base_url'] = 'http://localhost/EasyAPP/';
$config['url'] = 'http://localhost/EasyAPP/tests/'; // the route url of your project, maybe it will help you 

$config['action_router']        = 'home'; // the name of controller file that will be shown when will not be selected a route.
$config['action_error']         = 'not_found';// the name of controller file that will be shown when will be accessed a wrong or a non-existent route.

$config['db_hostname'] = 'localhost'; 
$config['db_database'] = 'radio';
$config['db_username'] = 'root';
$config['db_password'] = '';
$config['db_port'] = '3306';

$config['domain'] = 'localhost'; // used for login
$config['session_name'] = 'adm-ck'; // used for login
$config['session_time'] = 31556926;

$config['pre_action'] = [
    'startup/events',
];

$config['debug'] = true;