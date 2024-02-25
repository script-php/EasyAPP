<?php
/**
* @package      Action
* @version      1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;
class Action {
	public $load = false;
	public $id;
	public $route;
	public $method = 'index';
	
	public function __construct($route) {
		$this->id = $route;
        $query = preg_replace('/[^a-zA-Z0-9_\/\|]/', '', (string)$route);
        $query_exploded = explode('|', $query); // explode it 
        $this->route = (!empty($query_exploded[0]) && $query != NULL) ? $query_exploded[0] : '';
        $this->method = !empty($query_exploded[1]) ? $query_exploded[1] : $this->method;
	}

	public function getId() {
		return $this->id;
	}
	
	public function execute($registry, array &$args = []) {
		
		if (substr($this->method, 0, 2) == '__') {
			exit('Error: Calls to magic methods are not allowed!');
		}

        $file  = CONFIG_DIR_APP . 'controller/' . $this->route . '.php';	
		
		if (is_file($file)) {
			include_once($file);

			$class = 'Controller' . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $this->route))));
			
			$reflection = new \ReflectionClass($class);
	
			if ($reflection->hasMethod($this->method) && $reflection->getMethod($this->method)->getNumberOfRequiredParameters() <= count($args)) {
				return call_user_func_array(array((new $class($registry)), $this->method), $args);
			} else {
				exit('Error: Could not call ' . $this->route . '/' . $this->method . '!');
			}
			
		} else {
			exit('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
		
	}

}
