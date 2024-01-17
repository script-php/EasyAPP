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
$config['db_hostname'] = ''; 
$config['db_database'] = '';
$config['db_username'] = '';
$config['db_password'] = '';
$config['db_port'] = '';
$config['db_options'] = [];
$config['db_encoding'] = '';

// Framework config
$config['query'] = 'route';
$config['dir_app'] = 'app/';
$config['dir_controller'] = 'controller/';
$config['dir_model'] = 'model/';
$config['dir_view'] = 'view/';
$config['dir_language'] = 'language/';

// System
$config['dir_system'] = 'system/';
$config['dir_framework'] = 'Framework/';
$config['dir_library'] = 'Library/';
$config['dir_storage'] = 'storage/';
$config['dir_assets'] = 'assets/';

//output compression
$config['compression'] = 0;

$config['debug'] = false;

$config['default_language'] = 'en-gb';

// rewrite URL on incoming requests
$config['rewrite_url'] = [];

// rewrite the URLs displayed in the pages
$config['system_rewrite_url'] = [];