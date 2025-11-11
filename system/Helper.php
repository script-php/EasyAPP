<?php

/**
 * This file can contain additional functions that
 * you'd like to use throughout your entire application.
 * 
 * We will leve here some functions you can use for debugging your app.
 */


/**
 * Pretty print for arrays and objects
 * 
 * @param mixed $var The variable to print
 * @param bool $exit Whether to exit after printing
 */
function pre($var, $exit = false) {
	echo "<pre style='color:white;background:black;padding:15px'>".print_r($var, true)."</pre>\n";
	if(!empty($exit)) exit();
}

function localhost(string $localhost = 'localhost') {
	if($_SERVER['SERVER_NAME'] == $localhost) {
		return true;
	}
	return false;
}

/**
 * Display a simple error page without using the controller system
 * 
 * @param int $statusCode HTTP status code
 * @param string $message Error message
 */
function displayErrorPage($statusCode, $message) {
	$platform = CONFIG_PLATFORM;
	$version = CONFIG_VERSION;
    $errorTemplate = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error {$statusCode}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; text-align: center;background: linear-gradient(to bottom, #f9f9f9, #eaeaea);min-height: 100vh;display: flex;justify-content: center;align-items: center; }
            .error-container { max-width: 600px;padding: 30px;border: 1px solid #ddd;border-radius: 8px;background: white;box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
            h1 {color: #4a6fc7;margin-bottom: 20px;}p {line-height: 1.6;margin-bottom: 15px;color: #555;}
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Error {$statusCode}</h1>
            <p>{$message}</p>
            <p><small>{$platform} v{$version}</small></p>
        </div>
    </body>
    </html>
    HTML;
    
    echo $errorTemplate;
}

/**
 * Display a default welcome page for the framework
 */
function defaultPage() {

    $platform = CONFIG_PLATFORM;
	$version = CONFIG_VERSION;
    $template = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to {$platform}</title>
        <style>
            body {font-family: Arial, sans-serif;margin: 0;padding: 20px;text-align: center;background: linear-gradient(to bottom, #f9f9f9, #eaeaea);min-height: 100vh;display: flex;justify-content: center;align-items: center;}.container {max-width: 600px;padding: 30px;border: 1px solid #ddd;border-radius: 8px;background: white;box-shadow: 0 5px 15px rgba(0,0,0,0.05);}h1 {color: #4a6fc7;margin-bottom: 20px;}p {line-height: 1.6;margin-bottom: 15px;color: #555;}.next-steps {background: #f0f4ff;padding: 20px;border-radius: 6px;margin: 25px 0;text-align: left;}.next-steps h2 {color: #4a6fc7;margin-top: 0;font-size: 1.2rem;}ul {padding-left: 20px;margin: 15px 0;}li {margin-bottom: 8px;}.version {color: #888;font-size: 0.9rem;margin-top: 25px;}
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Welcome to {$platform}</h1>
            <p>Your framework has been successfully installed and is ready for development.</p>
            <p>This is the default page that will be displayed until you create your own content.</p>
            <div class="next-steps">
                <h2>Next Steps:</h2>
                <ul>
                    <li>Read the documentation</li>
                    <li>Create your first route</li>
                    <li>Build your application</li>
                    <li>Deploy to production</li>
                </ul>
            </div>
            <p>Start building your application by creating pages in your project directory.</p>
            <p class="version">{$platform} v{$version}</p>
        </div>
    </body>
    </html>
    HTML;

    echo $template;
}

/**
 * Get environment variable with support for default values and type casting
 * 
 * @param string $key The environment variable key
 * @param mixed $default The default value if the environment variable is not set
 * @return mixed The value of the environment variable or the default value
 */
function env($key, $default = null) {
    // First check $_ENV (supports arrays and original types)
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    
    // Fallback to getenv() for backward compatibility
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Try to parse JSON (in case it was an array stored as JSON string)
    if (is_string($value) && ($value[0] === '[' || $value[0] === '{')) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    
    // Convert string booleans to actual booleans
    $lowerValue = strtolower($value);
    if ($lowerValue === 'true') return true;
    if ($lowerValue === 'false') return false;
    if ($lowerValue === 'null') return null;
    
    // Convert numeric strings to numbers
    if (is_numeric($value)) {
        return strpos($value, '.') !== false ? (float)$value : (int)$value;
    }
    
    return $value;
}

/**
 * Generate CSRF token field for forms
 * @param string $action Optional action for token scoping
 * @return string HTML input field for CSRF protection
 */
function csrf_field($action = 'default') {
    global $registry;
    
    if (!defined('CONFIG_CSRF_PROTECTION') || !CONFIG_CSRF_PROTECTION) {
        return '';
    }
    
    if (isset($registry) && $registry->has('csrf')) {
        $csrf = $registry->get('csrf');
        return $csrf->getTokenField($action);
    }
    
    return '';
}

/**
 * Generate CSRF token value for AJAX requests
 * @param string $action Optional action for token scoping
 * @return string CSRF token value
 */
function csrf_token($action = 'default') {
    global $registry;
    
    if (!defined('CONFIG_CSRF_PROTECTION') || !CONFIG_CSRF_PROTECTION) {
        return '';
    }
    
    if (isset($registry) && $registry->has('csrf')) {
        $csrf = $registry->get('csrf');
        return $csrf->generateToken($action);
    }
    
    return '';
}

/**
 * Validate CSRF token from current request
 * @param string $action Optional action to validate against
 * @return bool True if CSRF validation passes
 */
function csrf_check($action = 'default') {
    global $registry;
    
    if (!defined('CONFIG_CSRF_PROTECTION') || !CONFIG_CSRF_PROTECTION) {
        return true;
    }
    
    if (isset($registry) && $registry->has('csrf')) {
        $csrf = $registry->get('csrf');
        return $csrf->validateRequest($action);
    }
    
    return false;
}

/**
 * Get database connection from registry
 * @return System\Framework\Db|null Database connection or null if not available
 */
function db() {
    $registry = System\Framework\Registry::getInstance();
    
    if ($registry->has('db')) {
        return $registry->get('db');
    }
    
    return null;
}