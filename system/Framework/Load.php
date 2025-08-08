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
	private $classCache = [];
	

    public function __construct($registry) {
		$this->registry = $registry;
	}


	public function __get(string $key) {
		return $this->registry->get($key);
	}


	public function __set(string $key, $value) {
		$this->registry->set($key, $value);
	}


	public function controller($route, $useProxy = true) {
        $cacheKey = 'controller:' . $route;
        if (isset($this->classCache[$cacheKey])) {
            return $this->classCache[$cacheKey];
        }
        
        $filename = CONFIG_DIR_CONTROLLER . $route . '.php';
	
        if (!file_exists($filename)) {
            throw new \Exception("Controller file not found: {$filename}");
        }
        
        require_once $filename;
                
        $className = "Controller" . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $route))));
        
        if (!class_exists($className)) {
            throw new \Exception("Controller class {$className} not found in {$filename}");
        }
        
        $registry = $this->registry;
        $controller = new $className($registry);
		
        
        $dataEvent = [
            'route' => $route,
            'controller' => $controller,
            'registry' => $registry
        ];

        $registry->get('events')->trigger("controller.loaded", $dataEvent);
        
        if ($useProxy) {
            $proxy = $this->registry->proxy->createControllerProxy($controller);
            $this->classCache[$cacheKey] = $proxy;
            return $proxy;
        } else {
            $this->classCache[$cacheKey] = $controller;
            return $controller;
        }
    }


	function runController($route, array $args = [], $useProxy = true) {

        $data = $this->route($route);

        $className = 'Controller' . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $data['route']))));

        $controller = $this->controller($data['route'], $useProxy);

        $result = call_user_func_array([$controller, $data['method']], $args);
		return $result;

    }


	public function model($route, $useProxy = true) {
        $cacheKey = 'model:' . $route;
        if (isset($this->classCache[$cacheKey])) {
            return $this->classCache[$cacheKey];
        }

        $filename = CONFIG_DIR_MODEL . $route . '.php';
        
        if (!file_exists($filename)) {
            throw new \Exception("Model file not found: {$filename}");
        }
        
        require_once $filename;
        
        $className = "Model" . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $route))));
        
        if (!class_exists($className)) {
            throw new \Exception("Model class {$className} not found in {$filename}");
        }
        
        $model = new $className($this->registry);

		
        
        $dataEvent = [
            'route' => $route,
            'model' => $model,
            'registry' => $this->registry
        ];
        $this->registry->get('events')->trigger("model.loaded", $dataEvent);

        if ($useProxy) {
            $proxy = $this->registry->proxy->createModelProxy($model);
            $this->classCache[$cacheKey] = $proxy;

			// pre($proxy);
            return $proxy;
        } else {
            $this->classCache[$cacheKey] = $model;
            return $model;
        }

    }


	public function service($route, ...$args) {

		$query = preg_replace('/[^a-zA-Z0-9_\/\|-]/', '', (string)$route);
		
		list($route, $method) = array_pad(explode('|', $query), 2, 'index');

		if (substr($method, 0, 2) === '__') {
			throw new \Exception('Error: Calls to magic methods are not allowed!');
		}

        $filename = CONFIG_DIR_SERVICE . $route . '.php';
        
        if (!file_exists($filename)) {
            throw new \Exception("Service file not found: {$filename}");
        }
        
        require_once $filename;
        
        $className = "Service" . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $route))));
        
        if (!class_exists($className)) {
            throw new \Exception("Service class {$className} not found in {$filename}");
        }
        
        $service = new $className($this->registry, ...$args);

		if (!method_exists($service, $method) || 
			(new \ReflectionMethod($service, $method))->getNumberOfRequiredParameters() > count($args)) {
			throw new System\Framework\Exceptions\MethodNotFound('Error: Could not call preaction method ' . $route . '|' . $method . '!');
		}
		
		return call_user_func_array([$service, $method], $args);

    }


	public function library(string $route, ...$args) {
	
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);
		$library = 'library_' . str_replace('/', '_', $route);
	
		if (!$this->registry->has($library)) {
			$file = CONFIG_DIR_LIBRARY . $route . '.php';
	
			if (is_file($file)) {
				include_once($file);
	
				$class = str_replace(' ', '', ucwords(str_replace('_', ' ', $library)));
	
				if (class_exists($class)) {
					$load_library = new $class($this->registry, ...$args);
					$this->registry->set($library, $load_library);
				} else {
					throw new System\Framework\Exceptions\LibraryNotFound('Error: Could not load library ' . $class . '!');
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
			throw new System\Framework\Exceptions\ViewNotFound('Error: Could not load template ' . $route . '!');
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

	function route($route) {
        $data = [];
        $query = preg_replace('/[^a-zA-Z0-9_\/\|-]/', '', (string)$route);
        $query_exploded = explode('|', $query);
        $data['route'] = !empty($query_exploded[0]) ? $query_exploded[0] : '';
        $data['method'] = !empty($query_exploded[1]) ? $query_exploded[1] : 'index';
        return $data;
    }

}