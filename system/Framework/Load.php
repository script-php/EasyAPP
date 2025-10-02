<?php

/**
* @package      Load
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

use System\Framework\Exceptions\ViewNotFound;
use System\Framework\Exceptions\RouteNotFound;
use System\Framework\Exceptions\MethodNotFound;
use System\Framework\Exceptions\MagicMethodCall;
use System\Framework\Exceptions\LibraryNotFound;

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


    // Set a value in the registry
    // @param string $key The key to set
    // @param mixed $value The value to set
    // @return void    
	public function __set(string $key, $value) {
		$this->registry->set($key, $value);
	}

    // Load the route and method from the given string
    // @param string $route The route string, e.g., 'controller/method'
    // @return array An associative array with 'route' and 'method' keys
    // @throws System\Framework\Exceptions\RouteNotFound If the route or method is not found
    // @throws System\Framework\Exceptions\MethodNotFound If the method does not exist or has too few parameters 
    // @throws System\Framework\Exceptions\MagicMethodCall If a magic method is called
    // @throws \Exception If the controller file or class does not exist
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


    // Run the controller with the given route and arguments
    // @param string $route The route to run, e.g., 'controller/method'
    // @param array $args The arguments to pass to the controller method
	// @param bool $useProxy Whether to use a proxy for the controller
    // @return mixed The result of the controller method
    function runController($route, array $args = [], $useProxy = true) {

        $data = $this->route($route);

        $className = 'Controller' . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $data['route']))));

        $controller = $this->controller($data['route'], $useProxy);

        $result = call_user_func_array([$controller, $data['method']], $args);
		return $result;

    }

    // Load a model by its route
    // @param string $route The route of the model, e.g., 'model_name'
    // @param bool $useProxy Whether to use a proxy for the model
    // @return mixed The model instance or its proxy
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


    // Load a service by its route
    // @param string $route The route of the service, e.g., 'service_name'
    // @param mixed ...$args Additional arguments to pass to the service constructor
    // @return mixed The service instance
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


    // Load a library by its route
    // @param string $route The route of the library, e.g., 'library_name'
    // @param mixed ...$args Additional arguments to pass to the library constructor
    // @return void
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


    // Load a language file by its route
    // @param string $route The route of the language file, e.g., 'language_name'
    // @return array The loaded language data
    public function language(string $route) {
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		$trigger = $route;
		$this->event->trigger('before:language/' . $trigger, [&$route]);
		$language =$this->registry->get('language')->load($route);
		$this->event->trigger('after:language/' . $trigger, [&$route, &$language]);
		return $language;
	}

    // Load a view file by its route
    // @param string $route The route of the view file, e.g., 'view_name'
    // @param array $data The data to pass to the view
    // @return string The rendered view output
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
			throw new ViewNotFound('Error: Could not load template ' . $route . '!');
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