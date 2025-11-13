# Request Lifecycle

Understanding how EasyAPP processes HTTP requests is essential for effective development and debugging.

---

## Table of Contents

1. [Overview](#overview)
2. [Request Flow](#request-flow)
3. [Initialization Phase](#initialization-phase)
4. [Routing Phase](#routing-phase)
5. [Controller Execution](#controller-execution)
6. [Response Generation](#response-generation)
7. [Shutdown Phase](#shutdown-phase)
8. [Error Handling](#error-handling)

---

## Overview

The request lifecycle describes the complete journey of an HTTP request through the EasyAPP framework, from the initial entry point to the final response.

### High-Level Flow

```
HTTP Request → Entry Point → Bootstrap → Routing → Controller → Response → HTTP Response
```

### Timeline

```
1. index.php receives request
2. Load configuration
3. Initialize framework
4. Setup error handling
5. Start session
6. Parse route
7. Load controller
8. Execute action
9. Generate response
10. Send output
11. Cleanup
```

---

## Request Flow

### Complete Request Lifecycle

```
┌─────────────────────────────────────────────────────────────┐
│ 1. HTTP Request                                              │
│    GET /user/profile?id=123                                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Web Server (Apache/Nginx)                                 │
│    • Rewrite rules                                           │
│    • Route to index.php                                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. index.php (Entry Point)                                   │
│    require_once('config.php');                               │
│    require_once('system/Framework.php');                     │
│    $framework = new System\Framework();                      │
│    $framework->start();                                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Framework Bootstrap                                       │
│    • Load configuration                                      │
│    • Initialize autoloader                                   │
│    • Create registry                                         │
│    • Register core services                                  │
│    • Setup error handling                                    │
│    • Start session                                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Routing                                                   │
│    • Parse URL: /user/profile?id=123                         │
│    • Match route: user/profile                               │
│    • Extract parameters: {id: 123}                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. Load Controller                                           │
│    • File: app/controller/user.php                           │
│    • Class: ControllerUser                                   │
│    • Method: profile()                                       │
│    • Inject registry                                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 7. Execute Controller Action                                 │
│    • Load models, services, libraries                        │
│    • Process business logic                                  │
│    • Prepare data for view                                   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 8. Render View                                               │
│    • Load template                                           │
│    • Pass data to view                                       │
│    • Generate HTML                                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 9. Send Response                                             │
│    • Set headers                                             │
│    • Output content                                          │
│    • Flush buffers                                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│ 10. HTTP Response                                            │
│     HTML, JSON, or other content                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Initialization Phase

### 1. Entry Point

**File:** `index.php`

```php
<?php

// Define base path
define('DIR_ROOT', __DIR__ . '/');

// Load configuration
require_once(DIR_ROOT . 'config.php');

// Load framework
require_once(DIR_ROOT . 'system/Framework.php');

// Start application
$framework = new System\Framework();
$framework->start();
```

### 2. Configuration Loading

**File:** `config.php`

```php
<?php

// Application settings
define('CONFIG_APP_NAME', 'My Application');
define('CONFIG_APP_URL', 'http://localhost');

// Database settings
define('CONFIG_DB_HOSTNAME', 'localhost');
define('CONFIG_DB_USERNAME', 'root');
define('CONFIG_DB_PASSWORD', '');
define('CONFIG_DB_DATABASE', 'myapp');

// More configuration...
```

### 3. Framework Bootstrap

**File:** `system/Framework.php`

```php
class Framework {
    
    private $registry;
    
    public function start() {
        // 1. Initialize autoloader
        $this->initializeAutoloader();
        
        // 2. Setup error handling
        $this->setupErrorHandling();
        
        // 3. Create registry
        $this->registry = new Registry();
        
        // 4. Register core services
        $this->registerServices();
        
        // 5. Start session
        $this->startSession();
        
        // 6. Load application config
        $this->loadApplicationConfig();
        
        // 7. Process request
        $this->processRequest();
    }
    
    private function registerServices() {
        // Database
        $this->registry->set('db', new Db([
            'hostname' => CONFIG_DB_HOSTNAME,
            'username' => CONFIG_DB_USERNAME,
            'password' => CONFIG_DB_PASSWORD,
            'database' => CONFIG_DB_DATABASE,
        ]));
        
        // Request
        $this->registry->set('request', new Request());
        
        // Response
        $this->registry->set('response', new Response());
        
        // Router
        $this->registry->set('router', new Router());
        
        // Cache
        $this->registry->set('cache', new Cache());
        
        // Logger
        $this->registry->set('logger', new Logger());
        
        // Mail
        $this->registry->set('mail', new Mail());
        
        // Language
        $this->registry->set('language', new Language(CONFIG_LANGUAGE));
        
        // Event system
        $this->registry->set('event', new Event());
        
        // CSRF protection
        if (CONFIG_CSRF_ENABLED) {
            $this->registry->set('csrf', new Csrf());
        }
        
        // Load helper
        $this->registry->set('load', new Load($this->registry));
    }
}
```

---

## Routing Phase

### 1. Parse URL

The router extracts the route from the URL:

```
URL: http://example.com/user/profile?id=123

Parsed:
- Route: user/profile
- Parameters: {id: 123}
```

### 2. Match Route

**Custom routes (defined in `app/router.php`):**

```php
// Static route
$this->router->add('/', 'home/index');

// Route with parameter
$this->router->add('/user/{id:\d+}', 'user/view');

// Route with optional parameter
$this->router->add('/blog/{slug}', 'blog/post');
```

**Default routing:**

```
URL: /user/profile
└─> Controller: app/controller/user.php
    └─> Class: ControllerUser
        └─> Method: profile()

URL: /product/view?id=5
└─> Controller: app/controller/product.php
    └─> Class: ControllerProduct
        └─> Method: view()
```

### 3. Load Controller

**File:** `system/Framework.php`

```php
private function processRequest() {
    $router = $this->registry->get('router');
    
    // Get route
    $route = $router->getRoute();
    
    // Parse route
    list($controller, $action) = $this->parseRoute($route);
    
    // Build controller path
    $controllerFile = DIR_ROOT . 'app/controller/' . $controller . '.php';
    
    // Check if controller exists
    if (!file_exists($controllerFile)) {
        throw new ControllerNotFound("Controller not found: " . $controller);
    }
    
    // Load controller
    require_once($controllerFile);
    
    // Build controller class name
    $className = 'Controller' . str_replace('/', '', ucwords($controller, '/'));
    
    // Instantiate controller
    $controllerInstance = new $className($this->registry);
    
    // Check if action exists
    if (!method_exists($controllerInstance, $action)) {
        throw new MethodNotFound("Method not found: " . $action);
    }
    
    // Execute action
    $controllerInstance->$action();
}
```

---

## Controller Execution

### 1. Controller Initialization

```php
class ControllerUser extends Controller {
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Constructor logic
        // Load common resources
        $this->load->language('user');
    }
}
```

### 2. Action Execution

```php
public function profile() {
    // 1. Get request data
    $userId = $this->request->get['id'] ?? null;
    
    // 2. Load resources - model returns instance directly
    $userModel = $this->load->model('user');
    
    // 3. Process business logic
    if (!$userId) {
        $this->response->redirect('/');
        return;
    }
    
    // Use the model instance
    $user = $userModel->getById($userId);
    
    if (!$user) {
        $this->response->redirect('/error/404');
        return;
    }
    
    // 4. Prepare data for view
    $data = [];
    $data['user'] = $user;
    $data['title'] = $user['name'] . ' - Profile';
    
    // 5. Load and render view
    $output = $this->load->view('user/profile.html', $data);
    
    // 6. Set response
    $this->response->setOutput($output);
}
```

### 3. Resource Loading

During controller execution, various resources are loaded:

```php
// Load model - returns instance directly
$userModel = $this->load->model('user');
$users = $userModel->getAll();

// Load service - returns instance directly
$this->load->service('EmailService');
$this->EmailService->send($email, $subject, $body);

// Load library - returns instance directly
$uploadLib = $this->load->library('Upload');
$filename = $uploadLib->upload($_FILES['file']);

// Load view - returns rendered HTML
$html = $this->load->view('template.html', $data);

// Load language - returns language data array
$this->load->language('user');
$text = $this->language->get('text_welcome');
```

---

## Response Generation

### 1. View Rendering

**File:** `system/Framework/Load.php`

```php
public function view($template, $data = []) {
    // Extract data to variables
    extract($data);
    
    // Build template path
    $templatePath = DIR_ROOT . 'app/view/' . $template;
    
    // Check if template exists
    if (!file_exists($templatePath)) {
        throw new ViewNotFound("View not found: " . $template);
    }
    
    // Start output buffering
    ob_start();
    
    // Include template
    require($templatePath);
    
    // Get buffer contents
    $output = ob_get_contents();
    
    // Clean buffer
    ob_end_clean();
    
    return $output;
}
```

### 2. Response Output

**File:** `system/Framework/Response.php`

```php
class Response {
    
    private $headers = [];
    private $output = '';
    
    public function addHeader($header) {
        $this->headers[] = $header;
    }
    
    public function setOutput($output) {
        $this->output = $output;
    }
    
    public function output() {
        // Send headers
        foreach ($this->headers as $header) {
            header($header);
        }
        
        // Send output
        echo $this->output;
    }
    
    public function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    public function json($data) {
        $this->addHeader('Content-Type: application/json');
        $this->setOutput(json_encode($data));
        $this->output();
    }
}
```

### 3. Different Response Types

**HTML Response:**

```php
public function index() {
    $output = $this->load->view('home/index.html', $data);
    $this->response->setOutput($output);
}
```

**JSON Response:**

```php
public function api() {
    $data = [
        'status' => 'success',
        'data' => $this->load->model('user')->getAll()
    ];
    $this->response->json($data);
}
```

**Redirect:**

```php
public function save() {
    // Save data...
    $this->response->redirect('/user/profile');
}
```

**File Download:**

```php
public function download() {
    $file = 'storage/uploads/document.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="document.pdf"');
    header('Content-Length: ' . filesize($file));
    
    readfile($file);
    exit;
}
```

---

## Shutdown Phase

### 1. Output Buffering

```php
class Framework {
    
    public function start() {
        // Start output buffering
        ob_start();
        
        try {
            // Process request
            $this->processRequest();
            
            // Output response
            $this->registry->get('response')->output();
            
        } catch (Exception $e) {
            // Handle error
            $this->handleError($e);
        }
        
        // Flush output
        ob_end_flush();
    }
}
```

### 2. Cleanup

```php
// Close database connections
$this->db->close();

// Write session data
session_write_close();

// Clear temporary files
$this->cache->cleanupTemp();

// Log request completion
$this->logger->info('Request completed', [
    'route' => $route,
    'duration' => microtime(true) - $startTime
]);
```

---

## Error Handling

### 1. Exception Handling

```php
class Framework {
    
    private function setupErrorHandling() {
        // Set exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Set error handler
        set_error_handler([$this, 'handleError']);
        
        // Set shutdown handler
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    public function handleException($exception) {
        // Log exception
        $this->registry->get('logger')->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Display error page
        if (CONFIG_ERROR_DISPLAY) {
            $this->displayErrorPage($exception);
        } else {
            $this->display500Page();
        }
    }
}
```

### 2. Error Flow

```
Error Occurs
    │
    ▼
Exception Handler
    │
    ├─> Log Error
    │
    ├─> Development Mode?
    │   ├─> Yes: Show detailed error
    │   └─> No: Show generic error
    │
    └─> Send Error Response
```

### 3. Error Pages

**Development:**

```php
private function displayErrorPage($exception) {
    echo '<h1>Error: ' . $exception->getMessage() . '</h1>';
    echo '<pre>' . $exception->getTraceAsString() . '</pre>';
}
```

**Production:**

```php
private function display500Page() {
    http_response_code(500);
    require(DIR_ROOT . 'app/view/error/500.html');
}
```

---

## Request Lifecycle Hooks

### 1. Pre-Action Hook

Execute code before controller action:

```php
class Controller {
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->preAction();
    }
    
    protected function preAction() {
        // Override in child controllers
    }
}
```

### 2. Post-Action Hook

Execute code after controller action:

```php
class Framework {
    
    private function processRequest() {
        // Execute action
        $controllerInstance->$action();
        
        // Post-action hook
        if (method_exists($controllerInstance, 'postAction')) {
            $controllerInstance->postAction();
        }
    }
}
```

### 3. Event Hooks

```php
// Before request
$this->event->trigger('framework.before_request');

// After request
$this->event->trigger('framework.after_request');

// Before controller
$this->event->trigger('controller.before_action', ['controller' => $controller]);

// After controller
$this->event->trigger('controller.after_action', ['controller' => $controller]);
```

---

## Performance Considerations

### 1. Lazy Loading

Resources are loaded only when needed:

```php
// Model is not loaded until accessed
$this->load->model('user');
$users = $this->user->getAll(); // Loaded here
```

### 2. Output Buffering

Prevents premature output and allows modification:

```php
ob_start();
// Generate output
$output = ob_get_contents();
ob_end_clean();
```

### 3. Caching

Cache expensive operations:

```php
$cacheKey = 'users_list';
$users = $this->cache->get($cacheKey);

if ($users === null) {
    $users = $this->load->model('user')->getAll();
    $this->cache->set($cacheKey, $users, 3600);
}
```

---

## Debugging the Lifecycle

### 1. Enable Debug Mode

**File:** `config.php`

```php
define('CONFIG_DEBUG', true);
define('CONFIG_ERROR_DISPLAY', true);
```

### 2. Log Lifecycle Events

```php
$this->logger->debug('Controller loaded', ['controller' => $controller]);
$this->logger->debug('Action executed', ['action' => $action]);
$this->logger->debug('View rendered', ['template' => $template]);
```

### 3. Measure Performance

```php
$startTime = microtime(true);

// Process request
$this->processRequest();

$endTime = microtime(true);
$duration = $endTime - $startTime;

$this->logger->info('Request completed', [
    'duration' => $duration,
    'memory' => memory_get_peak_usage(true)
]);
```

---

## Related Documentation

- **[Architecture](04-architecture.md)** - Application architecture
- **[Routing](15-routing.md)** - URL routing system
- **[Controllers](07-controllers.md)** - Controller execution
- **[Error Handling](25-error-handling.md)** - Error handling

---

**Previous:** [Architecture](04-architecture.md)  
**Next:** [Dependency Injection](06-dependency-injection.md)
