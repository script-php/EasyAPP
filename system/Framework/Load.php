<?php

/**
* @package      Load
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Load {

	public $load = false;
	public $registry;
	public $route;
	

    public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get(string $key) {
		return $this->registry->get($key);
	}

	public function __set(string $key, $value) {
		$this->registry->set($key, $value);
	}

	public function controller(string $route, ...$args) {

		$route = preg_replace('/[^a-zA-Z0-9_|\/]/', '', $route); // Sanitize the call

		$trigger = $route;

		$before = $this->event->trigger('before:controller/' . $trigger, [&$route, &$args]);
		
		$output = !empty($before) ? $before : (new Action($route))->execute($this->registry, $args);
		unset($before);

		$after = $this->event->trigger('after:controller/' . $trigger, [&$route, &$args, &$output]);
		$output = !empty($after) ? $after : (!empty($output) ? $output : '');
		unset($after);

		return $output;
	}

	public function get_controller(string $route, ...$args) {
		$route = preg_replace('/[^a-zA-Z0-9_|\/]/', '', $route); // Sanitize the call

        $file = CONFIG_DIR_APP . 'controller/' . $route . '.php';	
		
		if (is_file($file)) {
			include_once($file);
			$class = 'Controller' . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $route))));
			$controller = new $class($registry);
			return $controller;
		} 
		else {
			exit('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
	}

	// public function model(string $route) {

	// 	$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);
	// 	$model = 'model_' . str_replace('/', '_', $route);

	// 	if (!$this->registry->has($model)) {
	// 		$file = CONFIG_DIR_MODEL . $route . '.php';

	// 		if (is_file($file)) {
	// 			include_once($file);

	// 			$class = str_replace(' ', '', ucwords(str_replace('_', ' ', $model)));

	// 			if (class_exists($class)) {
	// 				$load_model = new $class($this->registry);
	// 				$this->registry->set($model, $load_model);
	// 			} else {
	// 				exit('Error: Could not load model ' . $class . '!');
	// 			}

	// 		}
			
	// 	}
	// }

	public function model($route) {
		// Sanitize the call
		$route = preg_replace('/[^a-zA-Z0-9_|\/]/', '', (string)$route);
		
		if (!$this->registry->has('model_' . str_replace('/', '_', $route))) {
			$file  = CONFIG_DIR_MODEL . $route . '.php';
			$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
			
			if (is_file($file)) {
				include_once($file);
	
				$proxy = new Proxy();
				
				// Overriding models is a little harder so we have to use PHP's magic methods
				// In future version we can use runkit
				foreach (get_class_methods($class) as $method) {
					// pre($method);
					$proxy->{$method} = $this->callback($this->registry, $route . '/' . $method);
					break;
				}
				
				$this->registry->set('model_' . str_replace('/', '_', (string)$route), $proxy);
			} else {
				exit('Error: Could not load model ' . $route . '!');
			}
		}
	}

    public function language($route) {
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route); // Sanitize the call
        $output = $this->registry->get('language')->load($route);	
		return $output;
	}


    public function view(string $route, array $data = []) {
		$route = preg_replace('/[^a-zA-Z0-9_\/.]/', '', (string)$route); // Sanitize the call
        $route = CONFIG_DIR_VIEW . $route;
        if(file_exists($route)) {
			ob_start();
			extract($data);
			include $route;
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}
		else {
			exit('Error: Could not load template ' . $route . '!');
		}
	}

    public function json($Response, $header=TRUE) {
		$json = json_encode($Response);
		if($header) {
			header('Content-type: text/json;charset=UTF-8');
			echo $json;
		}
		else {
			return $json;
		}
	}

    public function text(string $text,array $params=NULL) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{'.strtoupper($param).'}',$value,$text);
			}
		}
		return $text;
	}

	protected function callback($registry, $route) {
		return function($args) use($registry, $route) {
			static $model;
			
			$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);

			// Keep the original trigger
			$trigger = $route;
					
			// Trigger the pre events
			$result = $registry->get('event')->trigger('model/' . $trigger . '/before', array(&$route, &$args));
			
			if ($result && !$result instanceof Exception) {
				$output = $result;
			} else {
				$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', substr($route, 0, strrpos($route, '/')));
				
				// Store the model object
				$key = substr($route, 0, strrpos($route, '|'));
				
				if (!isset($model[$key])) {
					$model[$key] = new $class($registry);
				}
				
				$method = substr($route, strrpos($route, '|') + 1);
				
				$callable = array($model[$key], $method);
	
				if (is_callable($callable)) {
					$output = call_user_func_array($callable, $args);
				} else {
					throw new \Exception('Error: Could not call model/' . $route . '!');
				}					
			}
			
			// Trigger the post events
			$result = $registry->get('event')->trigger('model/' . $trigger . '/after', array(&$route, &$args, &$output));
			
			if ($result && !$result instanceof Exception) {
				$output = $result;
			}
						
			return $output;
		};
	}	

}