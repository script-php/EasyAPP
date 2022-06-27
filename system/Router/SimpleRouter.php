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

namespace System\Router;

use \System\Request;
use \App\Pages;

class SimpleRouter {

    public $query;
    public $home_page;
	public $error_page;

    public function __construct() {
        $this->query = (defined('APP_QUERY')) ? APP_QUERY : '';
        $this->home_page = (defined('APP_HOME_PAGE')) ? APP_HOME_PAGE : '';
        $this->error_page = (defined('APP_ERROR_PAGE')) ? APP_ERROR_PAGE : '';
    }

    public function load() {
        
        $query = Request\Http::GET($this->query);

        $class = $this->home_page;
        if($query != NULL) {
            $query = preg_replace('/[^a-zA-Z0-9_\/]/', '', $query); // clean the route
            $query_exploded = explode('/', $query); // explode it
            $class = \APP::File2Class($query_exploded[0], 'Page'); // get the class
        }
        $method = (!empty($query_exploded[1])) ? $query_exploded[1] : 'index'; // get the method

        if (substr($method, 0, 2) == '__') { $method = str_replace('__', '', $method); } // don't let magic methods to be called from url
        $class = "App\\Pages\\".$class;
        $not_found = true;
        if (class_exists($class)) {
            $page_class = new $class();
            if (is_callable([$page_class, $method])) {
                $not_found = false;
                call_user_func_array([$page_class, $method], []); # args
            }
        }
        if($not_found) {
            $error_page = "App\\Pages\\".$this->error_page;
            if (class_exists($error_page)) {
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