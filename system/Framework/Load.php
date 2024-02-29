<?php

/**
* @package      Load
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

	public function model($route) {
		$route = preg_replace('/[^a-zA-Z0-9_|\/]/', '', (string)$route);

		if (!$this->registry->has('model_' . str_replace('/', '_', $route))) {
			$file  = CONFIG_DIR_MODEL . $route . '.php';
			$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
			if (is_file($file)) {
				include_once($file);
				$proxy = new Proxy();
				foreach (get_class_methods($class) as $method) {
					$proxy->{$method} = $this->callback($this->registry, $route . '|' . $method);
					break;
				}
				$this->registry->set('model_' . str_replace('/', '_', (string)$route), $proxy);
			} else {
				exit('Error: Could not load model ' . $route . '!');
			}
		}

	}
	// used for model load
	protected function callback($registry, $route) {
		return function($args) use($registry, $route) {
			static $model;
			$route = preg_replace('/[^a-zA-Z0-9_|\/]/', '', (string)$route);
			$trigger = $route;
			$before = $registry->get('event')->trigger('before:model/' . $trigger . '', array(&$route, &$args));
			if (!empty($before)) {
				$output = $before;
			} else {
				$class_path = substr($route, 0, strrpos($route, '|'));
				if (!isset($model[$class_path])) {
					$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', substr($route, 0, strrpos($route, '|')));
					$model[$class_path] = new $class($registry);
				}
				$method = array($model[$class_path], (substr($route, strrpos($route, '|') + 1)));
				if (is_callable($method)) {
					$output = call_user_func_array($method, $args);
				} else {
					exit('Error: Could not call model/' . $route . '!');
				}					
			}
			$result = $registry->get('event')->trigger('after:model/' . $trigger . '', array(&$route, &$args, &$output));
			if (!empty($result)) {
				$output = $result;
			}
			return $output;
		};
	}
	

    public function language(string $route) {
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		$trigger = $route;
		$this->event->trigger('before:language/' . $trigger, [&$route]);
		$language =$this->registry->get('language')->load($route);
		$this->event->trigger('after:language/' . $trigger, [&$route, &$language]);
		return $language;
	}


    public function view(string $route, array $data = []) {
		$route = preg_replace('/[^a-zA-Z0-9_\/.]/', '', (string)$route); // Sanitize the call
        $trigger = $route;
		$route = CONFIG_DIR_VIEW . $route;
        if(file_exists($route)) {
			$output = $this->event->trigger('before:view/' . $trigger, [&$route, &$data]);
			if(empty($output)) {
				ob_start();
				extract($data);
				include $route;
				$output = ob_get_contents();
				ob_end_clean();
			}
			$this->event->trigger('after:view/' . $trigger, [&$route, &$data, &$output]);
			return $output;
		}
		else {
			exit('Error: Could not load template ' . $route . '!');
		}
	}

    public function json($response, $header=true) {
		$json = json_encode($Response);
		if($header) {
			header('Content-type: text/json;charset=UTF-8');
			echo $json;
		}
		else {
			return $json;
		}
	}

    public function text(string $text,array $params=null) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{{'.strtoupper($param).'}}',$value,$text);
			}
		}
		return $text;
	}

}