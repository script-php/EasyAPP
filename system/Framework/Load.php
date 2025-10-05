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
    private $includedFiles = [];
	

    /**
    * Constructor
    * @param Registry $registry The registry instance
    */
    public function __construct($registry) {
		$this->registry = $registry;
	}


    /** Get a value from the registry
    * Get a value from the registry
    * @param string $key The key to get
    * @return mixed The value from the registry
    */
	public function __get(string $key) {
		return $this->registry->get($key);
	}


    /** Set a value in the registry
    * Set a value in the registry
    * @param string $key The key to set
    * @param mixed $value The value to set
    * @return void
    */
	public function __set(string $key, $value) {
		$this->registry->set($key, $value);
	}

    /** 
    * Load a controller by its route
    * @param string $route The route string, e.g., 'home' or 'user/profile'
    * @param bool $useProxy Whether to use a proxy for the controller
    * @return mixed The controller instance or its proxy
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \Exception If the controller file or class does not exist
    */
	public function controller($route, $useProxy = true) {
        // Input validation
        if (empty($route) || !is_string($route)) {
            throw new \InvalidArgumentException('Route must be a non-empty string');
        }
        
        // Sanitize route using existing pattern (from line 282)
        $sanitizedRoute = preg_replace('/[^a-zA-Z0-9_\/.]/', '', (string)$route);
        $cacheKey = 'controller:' . $sanitizedRoute;
        
        // Return cached instance if available
        if (isset($this->classCache[$cacheKey])) {
            return $this->classCache[$cacheKey];
        }
        
        // Build file path
        $filename = CONFIG_DIR_CONTROLLER . $sanitizedRoute . '.php';
        
        // Validate file existence
        if (!file_exists($filename)) {
            throw new \Exception("Controller file not found: {$filename}");
        }
        
        // Additional security: ensure file is within controller directory
        $realControllerDir = realpath(CONFIG_DIR_CONTROLLER);
        $realFilePath = realpath($filename);
        if ($realFilePath === false || strpos($realFilePath, $realControllerDir) !== 0) {
            throw new \Exception("Invalid controller path: {$filename}");
        }
        
        // Load controller file with caching to avoid multiple inclusions
        $this->requireOnceCached($filename);
        
        // Generate class name with improved logic
        $className = $this->generateClassName($sanitizedRoute, 'Controller');
        
        // Validate class existence
        if (!class_exists($className)) {
            throw new \Exception("Controller class '{$className}' not found in file: {$filename}");
        }
        
        // Create controller instance
        $registry = $this->registry;
        $controller = new $className($registry);
        
        // Trigger controller loaded event
        $dataEvent = [
            'route' => $sanitizedRoute,
            'controller' => $controller,
            'registry' => $registry
        ];
        $registry->get('events')->trigger("controller.loaded", $dataEvent);
        
        // Create proxy or return direct instance
        if ($useProxy) {
            $proxy = $this->registry->proxy->createControllerProxy($controller);
            $this->classCache[$cacheKey] = $proxy;
            return $proxy;
        } else {
            $this->classCache[$cacheKey] = $controller;
            return $controller;
        }
    }
    
    /**
    * Generate class name from route with given prefix
    * @param string $route The sanitized route
    * @param string $prefix The class prefix (e.g., 'Controller', 'Model')
    * @return string The generated class name
    */
    private function generateClassName($route, $prefix) {
        // Convert route to class name: 'user/profile' -> 'ControllerUserProfile'
        $parts = explode('/', str_replace('_', '/', $route));
        $generatedName = !empty($prefix) ? $prefix : '';
        foreach ($parts as $part) {
            $generatedName .= ucfirst(strtolower($part));
        }
        return $generatedName;
    }



    /**
    * Require once with caching to avoid multiple inclusions
    * @param string $filename The file to include
    */
    private function requireOnceCached($filename) {
        if (!isset($this->includedFiles[$filename])) {
            require_once $filename;
            $this->includedFiles[$filename] = true;
        }
    }


    /**
    * Run the controller with the given route and arguments
    * @param string $route The route to run, e.g., 'controller/method'
    * @param array $args The arguments to pass to the controller method
    * @param bool $useProxy Whether to use a proxy for the controller
    * @return mixed The result of the controller method
    */
    function runController($route, array $args = [], $useProxy = true) {

        $data = $this->route($route);

        $controller = $this->controller($data['route'], $useProxy);

        $result = call_user_func_array([$controller, $data['method']], $args);
		return $result;

    }

    /**
    * Load a model by its route
    * @param string $route The route of the model, e.g., 'user' or 'common/home'
    * @param bool $useProxy Whether to use a proxy for the model
    * @return mixed The model instance or its proxy
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \Exception If the model file or class does not exist
    */
	public function model($route, $useProxy = true) {
        // Input validation
        if (empty($route) || !is_string($route)) {
            throw new \InvalidArgumentException('Route must be a non-empty string');
        }
        
        // Sanitize route using existing pattern (same as controller method)
        $sanitizedRoute = preg_replace('/[^a-zA-Z0-9_\/.]/', '', (string)$route);
        $cacheKey = 'model:' . $sanitizedRoute;
        
        // Return cached instance if available
        if (isset($this->classCache[$cacheKey])) {
            return $this->classCache[$cacheKey];
        }

        // Build file path
        $filename = CONFIG_DIR_MODEL . $sanitizedRoute . '.php';
        
        // Validate file existence
        if (!file_exists($filename)) {
            throw new \Exception("Model file not found: {$filename}");
        }
        
        // Additional security: ensure file is within model directory
        $realModelDir = realpath(CONFIG_DIR_MODEL);
        $realFilePath = realpath($filename);
        if ($realFilePath === false || strpos($realFilePath, $realModelDir) !== 0) {
            throw new \Exception("Invalid model path: {$filename}");
        }
        
        // Load model file with caching to avoid multiple inclusions
        $this->requireOnceCached($filename);
        
        // Generate class name
        $className = $this->generateClassName($sanitizedRoute, 'Model');
        
        // Validate class existence
        if (!class_exists($className)) {
            throw new \Exception("Model class '{$className}' not found in file: {$filename}");
        }
        
        // Create model instance
        $model = new $className($this->registry);
        
        // Trigger model loaded event
        $dataEvent = [
            'route' => $sanitizedRoute,
            'model' => $model,
            'registry' => $this->registry
        ];
        $this->registry->get('events')->trigger("model.loaded", $dataEvent);

        // Create proxy or return direct instance
        if ($useProxy) {
            $proxy = $this->registry->proxy->createModelProxy($model);
            $this->classCache[$cacheKey] = $proxy;
            return $proxy;
        } else {
            $this->classCache[$cacheKey] = $model;
            return $model;
        }
    }


    /**
    * Load and execute a service method by its route
    * @param string $route The route of the service, e.g., 'service_name' or 'service_name|method_name'
    * @param mixed ...$args Additional arguments to pass to the service method
    * @return mixed The result of the service method execution
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \Exception If the service file or class does not exist
    * @throws \System\Framework\Exceptions\MethodNotFound If the method does not exist or has insufficient parameters
    */
	public function service($route, ...$args) {
        // Input validation
        if (empty($route) || !is_string($route)) {
            throw new \InvalidArgumentException('Route must be a non-empty string');
        }

        // Sanitize route and parse route|method syntax
		$sanitizedQuery = preg_replace('/[^a-zA-Z0-9_\/\|-]/', '', (string)$route);
		list($sanitizedRoute, $method) = array_pad(explode('|', $sanitizedQuery), 2, 'index');

        // Security: Prevent magic method calls
		if (substr($method, 0, 2) === '__') {
			throw new \Exception('Error: Calls to magic methods are not allowed!');
		}

        // Build file path
        $filename = CONFIG_DIR_SERVICE . $sanitizedRoute . '.php';
        
        // Validate file existence
        if (!file_exists($filename)) {
            throw new \Exception("Service file not found: {$filename}");
        }
        
        // Additional security: ensure file is within service directory
        $realServiceDir = realpath(CONFIG_DIR_SERVICE);
        $realFilePath = realpath($filename);
        if ($realFilePath === false || strpos($realFilePath, $realServiceDir) !== 0) {
            throw new \Exception("Invalid service path: {$filename}");
        }
        
        // Load service file with caching to avoid multiple inclusions
        $this->requireOnceCached($filename);
        
        // Generate class name
        $className = $this->generateClassName($sanitizedRoute, 'Service');
        
        // Validate class existence
        if (!class_exists($className)) {
            throw new \Exception("Service class '{$className}' not found in file: {$filename}");
        }
        
        // Create service instance
        $service = new $className($this->registry, ...$args);

        // Validate method existence and parameters
		if (!method_exists($service, $method)) {
			throw new \System\Framework\Exceptions\MethodNotFound("Error: Method '{$method}' not found in service '{$sanitizedRoute}'!");
		}
        
        $reflection = new \ReflectionMethod($service, $method);
        if ($reflection->getNumberOfRequiredParameters() > count($args)) {
			throw new \System\Framework\Exceptions\MethodNotFound("Error: Method '{$sanitizedRoute}|{$method}' requires more parameters than provided!");
		}
		
        // Execute service method and return result
		return call_user_func_array([$service, $method], $args);
    }


    /**
    * Load a library by its route
    * @param string $route The route of the library, e.g., 'library_name' or 'category/library_name'
    * @param mixed ...$args Additional arguments to pass to the library constructor
    * @return mixed The library instance from registry
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \System\Framework\Exceptions\LibraryNotFound If the library file or class does not exist
    */
	public function library(string $route, ...$args) {
        // Input validation
        if (empty($route)) {
            throw new \InvalidArgumentException('Library route must be a non-empty string');
        }
        
        // Sanitize route using existing pattern
		$sanitizedRoute = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);
		$libraryKey = 'library_' . str_replace('/', '_', $sanitizedRoute);
	
        // Return cached library if already loaded
		if ($this->registry->has($libraryKey)) {
            return $this->registry->get($libraryKey);
        }
        
        // Build file path
        $filename = CONFIG_DIR_LIBRARY . $sanitizedRoute . '.php';
        
        // Validate file existence
        if (!is_file($filename)) {
            throw new \System\Framework\Exceptions\LibraryNotFound("Library file not found: {$filename}");
        }
        
        // Additional security: ensure file is within library directory
        $realLibraryDir = realpath(CONFIG_DIR_LIBRARY);
        $realFilePath = realpath($filename);
        if ($realFilePath === false || strpos($realFilePath, $realLibraryDir) !== 0) {
            throw new \System\Framework\Exceptions\LibraryNotFound("Invalid library path: {$filename}");
        }
        
        // Load library file with caching to avoid multiple inclusions
        $this->requireOnceCached($filename);
        
        // Generate class name using our shared helper
        $className = $this->generateClassName($libraryKey, '');
        
        // Validate class existence
        if (!class_exists($className)) {
            throw new \System\Framework\Exceptions\LibraryNotFound("Library class '{$className}' not found in file: {$filename}");
        }
        
        // Create library instance
        $libraryInstance = new $className($this->registry, ...$args);
        
        // Store in registry for future use
        $this->registry->set($libraryKey, $libraryInstance);
        
        // Return the library instance
        return $libraryInstance;
	}


    /**
    * Load a language file by its route
    * @param string $route The route of the language file, e.g., 'language_name'
    * @return array The loaded language data
    */
    public function language(string $route) {
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		$trigger = $route;
		$this->event->trigger('before:language/' . $trigger, [&$route]);
		$language =$this->registry->get('language')->load($route);
		$this->event->trigger('after:language/' . $trigger, [&$route, &$language]);
		return $language;
	}

    /**
    * Load and render a view file by its route
    * @param string $route The route of the view file, e.g., 'home/index.html' or 'user/profile.html'
    * @param array $data The data to pass to the view template
    * @return string The rendered view output
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \System\Framework\Exceptions\ViewNotFound If the view file does not exist
    */
    public function view(string $route, array $data = []) {
        // Input validation
        if (empty($route)) {
            throw new \InvalidArgumentException('Route must be a non-empty string');
        }
        
        // Validate data parameter type safety
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Data parameter must be an array');
        }
        
        // Sanitize route - allow dots for file extensions
		$sanitizedRoute = preg_replace('/[^a-zA-Z0-9_\/.]/', '', (string)$route);
        $trigger = $sanitizedRoute;
        
        // Build secure file path
		$filePath = CONFIG_DIR_VIEW . $sanitizedRoute;
        
        // Validate file existence
        if (!file_exists($filePath)) {
			throw new \System\Framework\Exceptions\ViewNotFound("View template not found: {$sanitizedRoute}");
		}
        
        // Additional security: ensure file is within view directory
        $realViewDir = realpath(CONFIG_DIR_VIEW);
        $realFilePath = realpath($filePath);
        if ($realFilePath === false || strpos($realFilePath, $realViewDir) !== 0) {
            throw new \System\Framework\Exceptions\ViewNotFound("Invalid view path: {$sanitizedRoute}");
        }
        
        // Trigger before:view event
        $eventOutput = $this->event->trigger('before:view/' . $trigger, [&$filePath, &$data]);
        
        // If event provided output, use it instead of rendering
        if (!empty($eventOutput)) {
            $this->event->trigger('after:view/' . $trigger, [&$filePath, &$data, &$eventOutput]);
            return $eventOutput;
        }
        
        // Render template with secure variable extraction
        $output = $this->renderTemplate($filePath, $data);
        
        // Trigger after:view event
        $this->event->trigger('after:view/' . $trigger, [&$filePath, &$data, &$output]);
        
        return $output;
	}
    
    /**
     * Securely render template file with data
     * @param string $templatePath Absolute path to template file
     * @param array $templateData Data to extract for template
     * @return string Rendered template output
     */
    private function renderTemplate($templatePath, $templateData) {
        // Start output buffering
        ob_start();
        
        try {
            // Secure variable extraction - prevent overwriting important variables
            $reservedVars = ['templatePath', 'templateData', 'output', 'this', 'registry'];
            
            // Filter out reserved variable names to prevent conflicts
            $safeData = [];
            foreach ($templateData as $key => $value) {
                if (!in_array($key, $reservedVars) && is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                    $safeData[$key] = $value;
                }
            }
            
            // Extract variables safely
            extract($safeData, EXTR_SKIP);
            
            // Include template file
            include $templatePath;
            
            // Get rendered output
            $output = ob_get_contents();
            
        } catch (\Throwable $e) {
            // Clean output buffer on error
            ob_end_clean();
            throw new \System\Framework\Exceptions\ViewNotFound("Error rendering template: {$e->getMessage()}");
        }
        
        // Clean and return output
        ob_end_clean();
        return $output;
    }


    /**
    * Send a JSON response
    * @param mixed $response The response data
    * @param bool $header Whether to send the JSON header
    * @return void|string
    */
    public function json($response, $header=true) {
		$json = json_encode($response);
		if($header) {
			header('Content-type: text/json;charset=UTF-8');
			echo $json;
		}
		else {
			return $json;
		}
	}


    /**
    * Load a configuration file
    * @param string $file The path to the configuration file
    * @return $this
    */
    public function text(string $text,array $params=null) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{{'.strtoupper($param).'}}',$value,$text);
			}
		}
		return $text;
	}


    /**
    * Parse a route string into its components
    * @param string $route The route string to parse
    * @return array An associative array containing the route components
    */
	function route($route) {
        $data = [];
        $query = preg_replace('/[^a-zA-Z0-9_\/\|-]/', '', (string)$route);
        $query_exploded = explode('|', $query);
        $data['route'] = !empty($query_exploded[0]) ? $query_exploded[0] : '';
        $data['method'] = !empty($query_exploded[1]) ? $query_exploded[1] : 'index';
        return $data;
    }

}