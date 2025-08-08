<?php
date_default_timezone_set('Europe/Bucharest');

$config['base_url'] = 'http://'.$_SERVER['SERVER_NAME'].'/tests/'; 
$config['url'] = 'http://'.$_SERVER['SERVER_NAME'].'/tests/';
    
$config['db_hostname'] = ''; 
$config['db_database'] = '';
$config['db_username'] = '';
$config['db_password'] = '';

$config['domain'] = $_SERVER['SERVER_NAME']; // used for login
$config['session_name'] = 'adm-ck'; // used for login
$config['session_time'] = 31556926;

$config['servers_upload'] = [
    'local' => [
        'url' => 'http://'.$_SERVER['SERVER_NAME'].'/storage/uploads/',
    ]
];

$config['debug'] = true;

