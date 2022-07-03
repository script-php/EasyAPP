<?php

/**
* @package      SimpleRouter
* @version      1.0.1
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
* 
* This class determines which class page and methods are executed based on the URL requested.
* URL request examples: 
* 1. index.php?route=home
* 2. index.php?route=home/method
*/

namespace System\Framework;

// use \App\Controller;

class SimpleRouter {

    public $registry;
    public $query;
    public $app_controller;
    public $main_controller;
	public $error_controller;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->query = (defined('APP_QUERY')) ? APP_QUERY : '';
        $this->app_controller = (defined('APP_CONTROLLER')) ? APP_CONTROLLER : '';
        $this->main_controller = (defined('APP_MAIN_CONTROLLER')) ? APP_MAIN_CONTROLLER : '';
        $this->error_controller = (defined('APP_ERROR_CONTROLLER')) ? APP_ERROR_CONTROLLER : '';
    }

    // TODO: remake this shit using autoloader & namespace.
    public function loadPage() {
        $http = new Http();
        $util = new Util();

        // load page
        $query = $http->get($this->query);
        $query = preg_replace('/[^a-zA-Z0-9_\/|]/', '', (string)$query);
        $query_exploded = explode('|', $query); // explode it 
        $route = (!empty($query_exploded[0]) && $query != NULL) ? $query_exploded[0] : $this->main_controller;
        $method = !empty($query_exploded[1]) ? $query_exploded[1] : 'index';
        $file = $this->app_controller . '/' . $route . '.php';

        $not_found = true;
        if(is_file($file)) {
            include_once($file);
            $route_exploded = explode('/', $route);
            $class = $route_exploded[count($route_exploded)-1];
            $class_name = 'Controller' . $util->file2Class($class);
            if (class_exists($class_name)) {
                $page_class = new $class_name($this->registry);
                if (is_callable([$page_class, $method])) {
                    $not_found = false;
                    call_user_func_array([$page_class, $method], []); # args
                }
            }
        }
        if($not_found) {
            // error page
            $query = $this->error_controller;
            $query = preg_replace('/[^a-zA-Z0-9_\/|]/', '', (string)$query);
            $query_exploded = explode('|', $query); // explode it 
            $route = (!empty($query_exploded[0]) && $query != NULL) ? $query_exploded[0] : $this->main_controller;
            $method = !empty($query_exploded[1]) ? $query_exploded[1] : 'index';
            $file = $this->app_controller . '/' . $route . '.php';
            if(is_file($file)) {
                include_once($file);
                $route_exploded = explode('/', $route);
                $class = $route_exploded[count($route_exploded)-1];
                $class_name = 'Controller' . $util->file2Class($class);
                if (class_exists($class_name)) {
                    $page_class = new $class_name($this->registry);
                    if (is_callable([$page_class, $method])) {
                        call_user_func_array([$page_class, $method], []); # args
                    }
                }
            } else {
                exit('Page not found!');
            }
        }
	}

}