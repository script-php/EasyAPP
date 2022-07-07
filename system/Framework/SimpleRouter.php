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
* 2. index.php?route=home|method
*/

namespace System\Framework;

class SimpleRouter {

    public $registry;
    public $query;
    public $dir_controller;
    public $main_controller;
	public $error_controller;
    public $request;
    public $util;

    public function __construct($registry) {
        $this->request = $registry->get('request');
        $this->util = $registry->get('util');
        $this->registry = $registry;
        $this->query = (defined('APP_QUERY')) ? APP_QUERY : '';
        $this->dir_controller = (defined('DIR_CONTROLLER')) ? DIR_CONTROLLER : '';
        $this->main_controller = (defined('MAIN_CONTROLLER')) ? MAIN_CONTROLLER : '';
        $this->error_controller = (defined('ERROR_CONTROLLER')) ? ERROR_CONTROLLER : '';
    }

    // TODO: remake this shit using autoloader & namespace.
    public function loadPage() {

        $query = !empty($this->request->get[$this->query]) ? $this->request->get[$this->query] : NULL;

        $query = preg_replace('/[^a-zA-Z0-9_\/|]/', '', (string)$query);
        $query_exploded = explode('|', $query); // explode it 
        $route = (!empty($query_exploded[0]) && $query != NULL) ? $query_exploded[0] : $this->main_controller;
        $method = !empty($query_exploded[1]) ? $query_exploded[1] : 'index';
        $file = $this->dir_controller . '/' . $route . '.php';
        $not_found = true;
        if(is_file($file)) {
            include_once($file);
            $class_name = 'Controller' . $this->util->file2Class(str_replace('/', '_', $route));
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
            $file = $this->dir_controller . '/' . $route . '.php';
            if(is_file($file)) {
                include_once($file);
                $class_name = 'Controller' . $this->util->file2Class(str_replace('/', '_', $route));
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