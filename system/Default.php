<?php

// App config
$config['url'] = NULL;
$config['base_url'] = NULL;
$config['session'] = NULL;
$config['pre_action'] = [];

$config['action_router'] = 'pages/dashboard';
$config['action_error'] = 'errors/error';

// db
$config['db_driver'] = 'PDO'; // database driver used (Just PDO for now)
$config['db_hostname'] = 'localhost'; 
$config['db_database'] = 'database';
$config['db_username'] = 'root';
$config['db_password'] = '';
$config['db_port'] = '3306';

// Framework config
$config['query'] = 'route';
$config['dir_app'] = 'app/';
$config['dir_controller'] = $config['dir_app'] . 'controller/';
$config['dir_model'] = $config['dir_app'] . 'model/';
$config['dir_view'] = $config['dir_app'] . 'view/';
$config['dir_language'] = $config['dir_app'] . 'language/';

// System
$config['dir_system'] = 'system/';
$config['dir_framework'] = 'Framework/';
$config['dir_library'] = 'Library/';
$config['dir_storage'] = 'storage/';

$config['debug'] = false;