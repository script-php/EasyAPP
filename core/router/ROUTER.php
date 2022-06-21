<?php

/**
* @package      ROUTER
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
* 
* This class determines which class page and methods are executed based on the URL requested.
* URL request examples: 
* 1. index.php?route=home
* 2. index.php?route=home/method
*/

class ROUTER {

    public $route;
    public $home_page;
	public $error_page;

    public function __construct() {
        $this->route = (isset(APP::$route) && !empty(APP::$route)) ? APP::$route : 'route';
        $this->home_page = (isset(APP::$home_page) && !empty(APP::$home_page)) ? APP::$home_page : 'PageHome';
        $this->error_page = (isset(APP::$error_page) && !empty(APP::$error_page)) ? APP::$error_page : 'PageError';
    }

    public function LOAD() {
        $route = HTTP::GET($this->route);

        $class = $this->home_page;
        if($route != NULL) {
            $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route); // clean the route
            $route_exploded = explode('/', $route); // explode it
            $class = APP::File2Class($route_exploded[0], 'Page'); // get the class
        }
        $method = (!empty($route_exploded[1])) ? $route_exploded[1] : 'index'; // get the method

        if (substr($method, 0, 2) == '__') { $method = str_replace('__', '', $method); } // don't let magic methods to be called from url

        $not_found = true;
        if (class_exists($class)) {
            $page_class = new $class();
            if (is_callable([$page_class, $method])) {
                $not_found = false;
                call_user_func_array([$page_class, $method], []); # args
            }
        }
        if($not_found) {
            if (class_exists($this->error_page)) {
                $error_page = $this->error_page;
                $page_class = new $error_page();
                if (is_callable([$page_class, 'index'])) {
                    call_user_func_array([$page_class, 'index'], []); # args
                }
            }
            else {
                exit('Page not found!');
            }
        }
    }

}