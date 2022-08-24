<?php

//Your app config come here
$config->base_url = 'http://localhost/muzica/'; // the route url of your project, maybe it will help you 
$config->url = 'http://localhost/muzica/admin/'; // the route url of your project, maybe it will help you 
$config->main_controller = 'my_home_page/home'; // the name of controller file that will be shown when will not be selected a route.
$config->error_controller = 'my_error_page/error'; // the name of controller file that will be shown when will be accessed a wrong or a non-existent route.

$config->db_driver = 'PDO'; // database driver used (Just PDO for now)
$config->db_hostname = 'localhost'; 
$config->db_database = '';
$config->db_username = 'root';
$config->db_password = '';
$config->db_port = '3306';