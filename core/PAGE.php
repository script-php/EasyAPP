<?php

class PAGE {

    function __construct() {
        // do something? ...maybe later?
    }

    function LOAD() {
        $route = HTTP::GET(APP::$route);

        $class = APP::$home_page;
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
            if (class_exists(self::$error_page)) {
                $page_class = new self::$error_page();
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