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
			$controller = new $class($this->registry);
			return $controller;
		} 
		else {
			exit('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
	}

	public function model(string $route) {
		
		// Sanitize the call
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);

		$model = 'model_' . str_replace('/', '_', $route);
		
		if (!$this->registry->has($model)) {

			$file = CONFIG_DIR_MODEL . $route . '.php';

			if (is_file($file)) {
				include_once($file);

				$class = str_replace(' ', '', ucwords(str_replace('_', ' ', $model)));

				if (class_exists($class)) {
					$proxy = new Proxy();
	
					foreach (get_class_methods($class) as $method) {

						$reflection = new \ReflectionMethod($class, $method);
	
						if ((substr($method, 0, 2) != '__') && $reflection->isPublic()) {

							$proxy->{$method} = function(&...$args) use ($route, $method) {
								
								$route = $route . '|' . $method;
								$trigger = $route;
	
								$this->event->trigger('before:model/' . $trigger . '', [&$route, &$args]); // Trigger the pre events

								$class = substr($route, 0, strrpos($route, '|'));
								$method = substr($route, strrpos($route, '|') + 1);

								if (is_file((CONFIG_DIR_MODEL . $class . '.php'))) {
									include_once((CONFIG_DIR_MODEL . $class . '.php'));
								}

								$newmodel = 'callback_' . str_replace('/', '_', $class);
	
								if (!$this->registry->has($newmodel)) {
									$class = str_replace(' ', '', ucwords(str_replace('_', ' ', 'model_' . str_replace('/', '_', $class))));
									$load_model = new $class($this->registry);
									$this->registry->set($newmodel, ($load_model)); // Store object
								}
								else {
									$load_model = $this->registry->get($newmodel); // use it from registry
								}

								$callable = [$load_model, $method];

								if (is_callable($callable)) {
									$output = $callable(...$args);
								}
								else {
									throw new \Exception('Error: Could not call model/' . $route . '!');
								}
	
								$this->event->trigger('after:model/' . $trigger . '', [&$route, &$args, &$output]);
	
								return $output;
							};
						}
					}
	
					$this->registry->set($model, $proxy);
				} else {
					throw new \Exception('Error: Could not load model ' . $class . '!');
				}

			}

		}
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