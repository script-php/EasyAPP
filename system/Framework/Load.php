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
    * Enhanced with comprehensive validation, error handling, and security checks
    * @param string $route The route to run, e.g., 'controller|method'
    * @param array $args The arguments to pass to the controller method
    * @param bool $useProxy Whether to use a proxy for the controller
    * @return mixed The result of the controller method
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \Exception If controller loading or method execution fails
    */
    public function runController($route, array $args = [], $useProxy = true) {
        // Input validation
        if (empty($route) || !is_string($route)) {
            throw new \InvalidArgumentException('Route must be a non-empty string');
        }
        
        if (!is_array($args)) {
            throw new \InvalidArgumentException('Arguments must be an array');
        }
        
        // Parse route with validation
        $routeData = $this->route($route);
        
        if (empty($routeData['route'])) {
            throw new \InvalidArgumentException('Invalid route format: route component cannot be empty');
        }
        
        // Security: validate method name
        $method = $routeData['method'];
        if (substr($method, 0, 2) === '__') {
            throw new \Exception('Error: Calls to magic methods are not allowed!');
        }
        
        try {
            // Load controller with enhanced error handling
            $controller = $this->controller($routeData['route'], $useProxy);
            
            // Get the actual controller object (in case it's wrapped in a proxy)
            $actualController = ($useProxy && method_exists($controller, 'getSubject')) 
                ? $controller->getSubject() 
                : $controller;
            
            // Validate method exists before calling
            if (!method_exists($actualController, $method)) {
                throw new \System\Framework\Exceptions\MethodNotFound("Method '{$method}' not found in controller '{$routeData['route']}'!");
            }
            
            // Validate method parameters using Reflection
            $reflection = new \ReflectionMethod($actualController, $method);
            $requiredParams = $reflection->getNumberOfRequiredParameters();
            
            if ($requiredParams > count($args)) {
                throw new \System\Framework\Exceptions\MethodNotFound("Method '{$routeData['route']}|{$method}' requires {$requiredParams} parameters, " . count($args) . " provided!");
            }
            
            // Trigger before controller execution event
            $eventData = [
                'route' => $routeData['route'],
                'method' => $method,
                'args' => $args,
                'controller' => $controller
            ];
            $this->event->trigger('before:controller.execute', $eventData);
            
            // Execute controller method directly (output will be captured by caller if needed)
            call_user_func_array([$controller, $method], $args);
            
            // Trigger after controller execution event
            $this->event->trigger('after:controller.execute', $eventData);
            
            return;
            
        } catch (\Throwable $e) {
            // Log controller execution error
            if (isset($this->registry) && $this->registry->has('logger')) {
                $this->registry->get('logger')->error('Controller execution failed', [
                    'route' => $route,
                    'method' => $method ?? 'unknown',
                    'error' => $e->getMessage(),
                    'args_count' => count($args)
                ]);
            }
            
            // Re-throw with context
            throw new \Exception("Controller execution failed for route '{$route}': " . $e->getMessage(), 0, $e);
        }
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

        // Generate registry key for magic access: 'user' becomes 'model_user', 'common/helper' becomes 'model_common_helper'
        $registryKey = 'model_' . str_replace('/', '_', $sanitizedRoute);

        // Create proxy or return direct instance
        if ($useProxy) {
            $proxy = $this->registry->proxy->createModelProxy($model);
            $this->classCache[$cacheKey] = $proxy;
            // Register in registry for magic access via $this->model_user or $this->model_common_helper
            $this->registry->set($registryKey, $proxy);
            return $proxy;
        } else {
            $this->classCache[$cacheKey] = $model;
            // Register in registry for magic access via $this->model_user or $this->model_common_helper
            $this->registry->set($registryKey, $model);
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
    * Load a language file by its route with event system integration
    * @param string $route The route of the language file, e.g., 'common' or 'user/profile'
    * @return array The loaded language data array
    * @throws \InvalidArgumentException If the route is invalid
    * @throws \Exception If language loading fails
    */
    public function language(string $route) {
        // Input validation
        if (empty($route)) {
            throw new \InvalidArgumentException('Language route must be a non-empty string');
        }
        
        // Sanitize route using existing pattern
		$sanitizedRoute = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        
        // Validate sanitized route is not empty after sanitization
        if (empty($sanitizedRoute)) {
            throw new \InvalidArgumentException('Language route contains only invalid characters');
        }
        
		$trigger = $sanitizedRoute;
        
        // Trigger before:language event
		$this->event->trigger('before:language/' . $trigger, [&$sanitizedRoute]);
        
        try {
            // Delegate to Language class for actual file loading
            $language = $this->registry->get('language')->load($sanitizedRoute);
            
            // Validate loaded language data
            if (!is_array($language)) {
                $language = [];
            }
            
        } catch (\Exception $e) {
            // Handle language loading errors gracefully
            throw new \Exception("Failed to load language file '{$sanitizedRoute}': " . $e->getMessage());
        }
        
        // Trigger after:language event
		$this->event->trigger('after:language/' . $trigger, [&$sanitizedRoute, &$language]);
        
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
    * Send a JSON response with enhanced error handling and security
    * @param mixed $response The response data to encode as JSON
    * @param bool $header Whether to send the JSON content-type header
    * @param int $options JSON encoding options (default: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    * @param int $depth Maximum encoding depth (default: 512)
    * @return void|string Returns JSON string if $header is false, otherwise outputs directly
    * @throws \InvalidArgumentException If JSON encoding fails
    */
    public function json($response, $header = true, $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, $depth = 512) {
        try {
            // Encode with proper options and error handling
            $json = json_encode($response, $options, $depth);
            
            // Check for JSON encoding errors
            if ($json === false) {
                $error = json_last_error_msg();
                throw new \InvalidArgumentException("JSON encoding failed: {$error}");
            }
            
            // Validate encoded JSON is not empty for non-null input
            if ($response !== null && $json === 'null') {
                throw new \InvalidArgumentException('JSON encoding produced null for non-null input');
            }
            
            if ($header) {
                // Security headers for JSON responses
                header('Content-Type: application/json; charset=UTF-8');
                header('X-Content-Type-Options: nosniff');
                
                // Optional: Add CORS headers if needed
                if (isset($_SERVER['HTTP_ORIGIN'])) {
                    // Note: In production, validate origin against whitelist
                    header('Access-Control-Allow-Origin: *');
                    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
                }
                
                // Output JSON response
                echo $json;
                
                // Trigger JSON response event
                if (isset($this->registry) && $this->registry->has('event')) {
                    $this->event->trigger('response.json.sent', [
                        'data' => $response,
                        'json' => $json,
                        'size' => strlen($json)
                    ]);
                }
                
                return;
            } else {
                // Return JSON string without headers
                return $json;
            }
            
        } catch (\Throwable $e) {
            // Log JSON encoding error
            if (isset($this->registry) && $this->registry->has('logger')) {
                $this->registry->get('logger')->error('JSON encoding failed', [
                    'error' => $e->getMessage(),
                    'data_type' => gettype($response),
                    'data_size' => is_string($response) ? strlen($response) : 'N/A'
                ]);
            }
            
            // Re-throw with context
            throw new \InvalidArgumentException('JSON response generation failed: ' . $e->getMessage(), 0, $e);
        }
    }


    /**
    * Process text template with parameter substitution and enhanced security
    * @param string $text The template text containing placeholders
    * @param array|null $params Associative array of parameters for substitution
    * @param array $options Processing options: 'case_sensitive', 'allow_html', 'strict_mode'
    * @return string The processed text with parameters substituted
    * @throws \InvalidArgumentException If input validation fails
    */
    public function text(string $text, ?array $params = null, array $options = []) {
        // Input validation
        if (empty($text)) {
            return $text; // Return empty string as-is
        }
        
        // Default options
        $defaultOptions = [
            'case_sensitive' => false,    // Convert parameter keys to uppercase by default
            'allow_html' => false,        // Escape HTML in values by default
            'strict_mode' => false,       // Don't throw on missing parameters
            'placeholder_pattern' => '{{%s}}' // Default placeholder pattern
        ];
        $options = array_merge($defaultOptions, $options);
        
        // If no parameters provided, return text as-is
        if ($params === null || empty($params)) {
            return $text;
        }
        
        // Validate parameters array
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Parameters must be an array or null');
        }
        
        $processedText = $text;
        $replacements = [];
        $missingParams = [];
        
        try {
            foreach ($params as $param => $value) {
                // Validate parameter key
                if (!is_string($param) && !is_numeric($param)) {
                    continue; // Skip non-string/numeric keys
                }
                
                // Convert parameter key based on case sensitivity option
                $paramKey = $options['case_sensitive'] ? $param : strtoupper($param);
                
                // Create placeholder
                $placeholder = sprintf($options['placeholder_pattern'], $paramKey);
                
                // Process parameter value
                $processedValue = $this->processParameterValue($value, $options);
                
                // Track replacement
                $replacements[$placeholder] = $processedValue;
                
                // Perform replacement
                $processedText = str_replace($placeholder, $processedValue, $processedText);
            }
            
            // Check for unreplaced placeholders in strict mode
            if ($options['strict_mode']) {
                $pattern = '/\{\{([A-Z0-9_]+)\}\}/';
                if (preg_match_all($pattern, $processedText, $matches)) {
                    $missingParams = array_unique($matches[1]);
                    if (!empty($missingParams)) {
                        throw new \InvalidArgumentException('Missing required parameters: ' . implode(', ', $missingParams));
                    }
                }
            }
            
            // Trigger text processing event
            if (isset($this->registry) && $this->registry->has('event')) {
                $this->event->trigger('text.processed', [
                    'original' => $text,
                    'processed' => $processedText,
                    'replacements' => $replacements,
                    'options' => $options
                ]);
            }
            
            return $processedText;
            
        } catch (\Throwable $e) {
            // Log text processing error
            if (isset($this->registry) && $this->registry->has('logger')) {
                $this->registry->get('logger')->error('Text template processing failed', [
                    'error' => $e->getMessage(),
                    'text_length' => strlen($text),
                    'params_count' => count($params),
                    'options' => $options
                ]);
            }
            
            // Re-throw with context
            throw new \InvalidArgumentException('Text template processing failed: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Process parameter value based on options
     * @param mixed $value The parameter value
     * @param array $options Processing options
     * @return string Processed value
     */
    private function processParameterValue($value, array $options): string {
        // Convert value to string
        if (is_array($value) || is_object($value)) {
            $stringValue = json_encode($value);
        } elseif (is_bool($value)) {
            $stringValue = $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            $stringValue = '';
        } else {
            $stringValue = (string)$value;
        }
        
        // Apply HTML escaping if required
        if (!$options['allow_html']) {
            $stringValue = htmlspecialchars($stringValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $stringValue;
    }


    /**
    * Parse a route string into its components with enhanced validation and security
    * @param string $route The route string to parse (e.g., 'controller', 'controller|method', 'folder/controller|method')
    * @return array Associative array with 'route', 'method', and 'parts' keys
    * @throws \InvalidArgumentException If the route format is invalid
    */
    public function route($route) {
        // Input validation
        if (!is_string($route)) {
            throw new \InvalidArgumentException('Route must be a string');
        }
        
        if (empty(trim($route))) {
            throw new \InvalidArgumentException('Route cannot be empty');
        }
        
        // Initialize result array
        $data = [
            'route' => '',
            'method' => 'index',
            'parts' => [],
            'original' => $route
        ];
        
        try {
            // Sanitize input - allow alphanumeric, underscore, slash, pipe, dash
            $sanitizedRoute = preg_replace('/[^a-zA-Z0-9_\/\|\-]/', '', trim($route));
            
            // Validate that sanitization didn't remove everything
            if (empty($sanitizedRoute)) {
                throw new \InvalidArgumentException('Route contains only invalid characters');
            }
            
            // Split by pipe to separate route from method
            $routeParts = explode('|', $sanitizedRoute);
            
            // Validate route part
            $routePart = trim($routeParts[0]);
            if (empty($routePart)) {
                throw new \InvalidArgumentException('Route component cannot be empty');
            }
            
            // Security: Check for path traversal attempts
            if (strpos($routePart, '..') !== false) {
                throw new \InvalidArgumentException('Path traversal attempts are not allowed in routes');
            }
            
            // Parse method part
            $methodPart = isset($routeParts[1]) ? trim($routeParts[1]) : 'index';
            
            // Validate method name
            if (!empty($methodPart)) {
                // Method must be valid PHP method name
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $methodPart)) {
                    throw new \InvalidArgumentException('Invalid method name format');
                }
                
                // Security: Prevent magic method calls
                if (substr($methodPart, 0, 2) === '__') {
                    throw new \InvalidArgumentException('Magic method calls are not allowed');
                }
            }
            
            // Build result
            $data['route'] = $routePart;
            $data['method'] = $methodPart ?: 'index';
            
            // Parse route parts for additional context
            $data['parts'] = array_filter(explode('/', $routePart), function($part) {
                return !empty(trim($part));
            });
            
            // Validate parts count (reasonable limit)
            if (count($data['parts']) > 10) {
                throw new \InvalidArgumentException('Route depth exceeds maximum allowed levels');
            }
            
            // Add additional metadata
            $data['controller'] = end($data['parts']) ?: $routePart;
            $data['namespace'] = count($data['parts']) > 1 ? implode('\\', array_slice($data['parts'], 0, -1)) : '';
            $data['depth'] = count($data['parts']);
            
            // Trigger route parsing event
            if (isset($this->registry) && $this->registry->has('event')) {
                $this->event->trigger('route.parsed', [
                    'original' => $route,
                    'parsed' => $data
                ]);
            }
            
            return $data;
            
        } catch (\Throwable $e) {
            // Log route parsing error
            if (isset($this->registry) && $this->registry->has('logger')) {
                $this->registry->get('logger')->error('Route parsing failed', [
                    'route' => $route,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Re-throw with context
            throw new \InvalidArgumentException('Route parsing failed for "' . $route . '": ' . $e->getMessage(), 0, $e);
        }
    }

}