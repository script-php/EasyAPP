<?php

// Platform
$config['platform'] = 'EasyAPP';
$config['version'] = '1.7.0';
$config['environment'] = 'dev';

// App config
$config['url'] = NULL;
$config['base_url'] = NULL;
$config['session'] = NULL;
$config['services'] = [];

$config['action_router'] = '';
$config['action_error'] = '';

// db
$config['db_driver'] = 'mysql'; // database driver used (Just PDO for now)
$config['db_hostname'] = ''; 
$config['db_database'] = '';
$config['db_username'] = '';
$config['db_password'] = '';
$config['db_port'] = '3306'; // default mysql port
$config['db_options'] = [];
$config['db_encoding'] = '';
$config['db_prefix'] = '';

// Framework config
$config['query'] = 'route';
$config['dir_app'] = 'app/';
$config['dir_controller'] = 'controller/';
$config['dir_model'] = 'model/';
$config['dir_event'] = 'event/';
$config['dir_service'] = 'service/';
$config['dir_view'] = 'view/';
$config['dir_language'] = 'language/';

// System
$config['dir_system'] = 'system/';
$config['dir_framework'] = 'Framework/';
$config['dir_library'] = 'library/';
$config['dir_storage'] = 'storage/';
$config['dir_assets'] = 'assets/';

//output compression
$config['compression'] = 0;

$config['debug'] = false;
$config['dev_db_schema'] = false;

$config['default_language'] = 'en-gb';