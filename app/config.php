<?php

//Your app config come here
$config['url'] = 'http://localhost/EasyAPP/'; // the route url of your project, maybe it will help you
$config['action_router'] = 'common/home'; // the name of controller file that will be shown when will not be selected a route.
$config['action_error'] = 'error/not_found'; // the name of controller file that will be shown when will be accessed a wrong or a non-existent route.

$config['db_driver'] = 'PDO'; // database driver used (Just PDO for now)
$config['db_hostname'] = 'localhost'; 
$config['db_database'] = 'muzica';
$config['db_username'] = 'root';
$config['db_password'] = '';
$config['db_port'] = '3306';

$config['default_language'] = 'ro-ro';
$config['debug'] = true;


