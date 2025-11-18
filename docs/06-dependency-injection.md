# Dependency Injection

EasyAPP uses a Registry-based dependency injection system to manage dependencies and provide access to framework services.

---

## Table of Contents

1. [Overview](#overview)
2. [Registry Pattern](#registry-pattern)
3. [Service Registration](#service-registration)
4. [Accessing Services](#accessing-services)
5. [Dependency Injection in Components](#dependency-injection-in-components)
6. [Custom Services](#custom-services)
7. [Service Providers](#service-providers)
8. [Best Practices](#best-practices)

---

## Overview

Dependency Injection (DI) is a design pattern that allows objects to receive their dependencies from external sources rather than creating them internally.

### Benefits

- **Loose Coupling** - Components don't depend on concrete implementations
- **Testability** - Easy to mock dependencies in tests
- **Flexibility** - Easy to swap implementations
- **Reusability** - Services can be shared across components

### DI in EasyAPP

EasyAPP implements DI through the Registry pattern, where all framework services are registered in a central registry and injected into components.

```php
// Framework registers services
$registry->set('db', new Db());
$registry->set('cache', new Cache());

// Components receive registry
class Controller {
    public function __construct($registry) {
        $this->registry = $registry;
    }
}

// Components access services
$this->db->query(...);
$this->cache->get(...);
```

---

## Registry Pattern

### Registry Implementation

**File:** `system/Framework/Registry.php`

```php
<?php

namespace System\Framework;

class Registry {
    
    private $data = [];
    
    /**
     * Register a service
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }
    
    /**
     * Get a service
     */
    public function get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
    /**
     * Check if service exists
     */
    public function has($key) {
        return isset($this->data[$key]);
    }
    
    /**
     * Remove a service
     */
    public function remove($key) {
        unset($this->data[$key]);
    }
    
    /**
     * Get all services
     */
    public function all() {
        return $this->data;
    }
}
```

### Registry Usage

```php
// Create registry
$registry = new System\Framework\Registry();

// Register services
$registry->set('db', new Db());
$registry->set('cache', new Cache());
$registry->set('logger', new Logger());

// Access services
$db = $registry->get('db');
$cache = $registry->get('cache');

// Check if service exists
if ($registry->has('logger')) {
    $logger = $registry->get('logger');
}
```

---

## Service Registration

### Core Services

EasyAPP registers core services during bootstrap:

**File:** `system/Framework.php`

```php
class Framework {
    
    private $registry;
    
    public function start() {
        // Create registry
        $this->registry = new Registry();
        
        // Register core services
        $this->registerServices();
    }
    
    private function registerServices() {
        // Database
        $this->registry->set('db', new Db([
            'hostname' => CONFIG_DB_HOSTNAME,
            'username' => CONFIG_DB_USERNAME,
            'password' => CONFIG_DB_PASSWORD,
            'database' => CONFIG_DB_DATABASE,
            'port' => CONFIG_DB_PORT,
            'prefix' => CONFIG_DB_PREFIX ?? '',
        ]));
        
        // Request
        $this->registry->set('request', new Request());
        
        // Response
        $this->registry->set('response', new Response());
        
        // Router
        $this->registry->set('router', new Router());
        
        // Cache
        $this->registry->set('cache', new Cache([
            'driver' => CONFIG_CACHE_DRIVER ?? 'file',
            'path' => DIR_ROOT . 'storage/cache/'
        ]));
        
        // Logger
        $this->registry->set('logger', new Logger([
            'path' => DIR_ROOT . 'storage/logs/',
            'level' => CONFIG_LOG_LEVEL ?? 'error'
        ]));
        
        // Mail
        $this->registry->set('mail', new Mail([
            'driver' => CONFIG_MAIL_DRIVER ?? 'mail',
            'from' => CONFIG_MAIL_FROM ?? 'noreply@example.com',
            'from_name' => CONFIG_MAIL_FROM_NAME ?? 'Application'
        ]));
        
        // Language
        $this->registry->set('language', new Language(CONFIG_LANGUAGE ?? 'en-gb'));
        
        // Event system
        $this->registry->set('event', new Event());
        
        // CSRF protection
        if (defined('CONFIG_CSRF_ENABLED') && CONFIG_CSRF_ENABLED) {
            $this->registry->set('csrf', new Csrf());
        }
        
        // Validator
        $this->registry->set('validator', new Validator());
        
        // URL helper
        $this->registry->set('url', new Url());
        
        // File helper
        $this->registry->set('file', new File());
        
        // Image helper
        $this->registry->set('image', new Image());
        
        // Load helper (must be last, needs registry)
        $this->registry->set('load', new Load($this->registry));
    }
}
```

### Available Core Services

| Service | Class | Purpose |
|---------|-------|---------|
| `db` | `System\Framework\Db` | Database operations |
| `request` | `System\Framework\Request` | HTTP request data |
| `response` | `System\Framework\Response` | HTTP response |
| `router` | `System\Framework\Router` | URL routing |
| `cache` | `System\Framework\Cache` | Caching layer |
| `logger` | `System\Framework\Logger` | PSR-3 logging |
| `mail` | `System\Framework\Mail` | Email sending |
| `language` | `System\Framework\Language` | Translations |
| `event` | `System\Framework\Event` | Event system |
| `csrf` | `System\Framework\Csrf` | CSRF protection |
| `validator` | `System\Framework\Validator` | Validation |
| `url` | `System\Framework\Url` | URL generation |
| `file` | `System\Framework\File` | File operations |
| `image` | `System\Framework\Image` | Image processing |
| `load` | `System\Framework\Load` | Resource loader |

---

## Accessing Services

### Magic Method Access

All base classes (Controller, Model, Service, Library) provide magic method access:

**File:** `system/Controller.php`

```php
<?php

class Controller {
    
    protected $registry;
    
    public function __construct($registry) {
        $this->registry = $registry;
    }
    
    /**
     * Magic method to access registry services
     */
    public function __get($name) {
        return $this->registry->get($name);
    }
    
    /**
     * Magic method to set registry services
     */
    public function __set($name, $value) {
        $this->registry->set($name, $value);
    }
}
```

### Usage in Controllers

```php
class ControllerUser extends Controller {
    
    public function index() {
        // Access database
        $users = $this->db->query("SELECT * FROM users")->rows;
        
        // Access request data
        $page = $this->request->get['page'] ?? 1;
        $postData = $this->request->post;
        
        // Access cache
        $cachedUsers = $this->cache->get('users');
        
        // Access logger
        $this->logger->info('Users list accessed');
        
        // Access language
        $this->load->language('user');
        $heading = $this->language->get('heading_title');
        
        // Load view
        $output = $this->load->view('user/list.html', $data);
        
        // Set response
        $this->response->setOutput($output);
    }
}
```

### Usage in Models

```php
class ModelUser extends Model {
    
    public function getAll() {
        // Access database
        $sql = "SELECT * FROM users";
        return $this->db->query($sql)->rows;
    }
    
    public function getCached() {
        $cacheKey = 'users_all';
        
        // Access cache
        $users = $this->cache->get($cacheKey);
        
        if ($users === null) {
            $users = $this->getAll();
            $this->cache->set($cacheKey, $users, 3600);
        }
        
        return $users;
    }
    
    public function getUserWithProfile($userId) {
        // Models can load other models
        $profileModel = $this->load->model('profile');
        
        return [
            'user' => $this->getById($userId),
            'profile' => $profileModel->getByUserId($userId)
        ];
    }
}
```

### Usage in Services

```php
class ServiceEmailService extends Service {
    
    public function sendWelcome($user) {
        // Load language
        $this->load->language('email/welcome');
        
        $subject = $this->language->get('subject');
        $body = sprintf(
            $this->language->get('body'),
            $user['name']
        );
        
        // Send email using mail service
        $this->mail->send($user['email'], $subject, $body);
        
        // Log
        $this->logger->info('Welcome email sent', [
            'email' => $user['email']
        ]);
    }
}
```

### Usage in Libraries

```php
class LibraryUpload extends Library {
    
    public function upload($file, $path) {
        // Process upload...
        
        // Log
        $this->logger->info('File uploaded', [
            'filename' => $file['name'],
            'path' => $path
        ]);
        
        // Cache invalidation
        $this->cache->delete('uploads_list');
        
        return $filename;
    }
}
```

---

## Dependency Injection in Components

### Constructor Injection

All components receive the registry through their constructor:

```php
class ControllerUser extends Controller {
    
    private $userService;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Load dependencies in constructor
        $this->load->service('UserService');
        $this->userService = $this->UserService;
    }
    
    public function create() {
        // Use injected dependency
        $userId = $this->userService->register($this->request->post);
        $this->response->redirect('/user/view?id=' . $userId);
    }
}
```

### Property Injection

Dependencies can be injected as properties:

```php
class ServiceOrderService extends Service {
    
    private $paymentService;
    private $emailService;
    private $inventoryService;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Inject dependencies
        $this->load->service('PaymentService');
        $this->load->service('EmailService');
        $this->load->service('InventoryService');
        
        $this->paymentService = $this->PaymentService;
        $this->emailService = $this->EmailService;
        $this->inventoryService = $this->InventoryService;
    }
    
    public function processOrder($orderData) {
        // Use injected services
        $this->inventoryService->reserve($orderData['items']);
        $payment = $this->paymentService->process($orderData);
        $this->emailService->sendConfirmation($orderData);
    }
}
```

### Lazy Loading

Services and models are loaded only when accessed:

```php
class ControllerProduct extends Controller {
    
    public function index() {
        // Model not loaded yet
        
        if ($this->needsProducts()) {
            // Model loaded here, only when needed
            // Both styles work:
            
            // Style 1: Capture instance
            $productModel = $this->load->model('product');
            $products = $productModel->getAll();
            
            // Style 2: Magic access (auto-registered with model_ prefix)
            $this->load->model('product');
            $products = $this->model_product->getAll();
        }
    }
}
```

### Model Loading Patterns

Models can be accessed in two ways after loading:

```php
// Pattern 1: Explicit (Recommended for clarity)
$userModel = $this->load->model('user');
$user = $userModel->find(1);

// Pattern 2: Magic Access (Auto-registered in registry with model_ prefix)
$this->load->model('user');
$user = $this->model_user->find(1);

// Pattern 3: Method Chaining (Immediate use)
$user = $this->load->model('user')->find(1);

// Subdirectories: slashes become underscores, model_ prefix added
$this->load->model('common/helper');
$data = $this->model_common_helper->someMethod();
```

---

## Custom Services

### Registering Custom Services

You can register your own services in the registry:

**File:** `app/config.php`

```php
<?php

// After framework initialization, register custom services
if (isset($framework)) {
    $registry = $framework->getRegistry();
    
    // Register custom analytics service
    $registry->set('analytics', new App\Service\Analytics([
        'api_key' => CONFIG_ANALYTICS_API_KEY,
        'enabled' => CONFIG_ANALYTICS_ENABLED
    ]));
    
    // Register payment gateway
    $registry->set('payment', new App\Library\PaymentGateway([
        'api_key' => CONFIG_PAYMENT_API_KEY,
        'mode' => CONFIG_APP_ENV === 'production' ? 'live' : 'sandbox'
    ]));
}
```

### Using Custom Services

```php
class ControllerUser extends Controller {
    
    public function register() {
        // Use custom analytics service
        $this->analytics->trackEvent('user_registration', [
            'email' => $this->request->post['email']
        ]);
        
        // Register user...
    }
}
```

---

## Service Providers

### Creating Service Providers

Organize service registration with providers:

**File:** `app/Providers/DatabaseServiceProvider.php`

```php
<?php

namespace App\Providers;

class DatabaseServiceProvider {
    
    public static function register($registry) {
        // Register primary database
        $registry->set('db', new \System\Framework\Db([
            'hostname' => CONFIG_DB_HOSTNAME,
            'username' => CONFIG_DB_USERNAME,
            'password' => CONFIG_DB_PASSWORD,
            'database' => CONFIG_DB_DATABASE,
        ]));
        
        // Register analytics database
        $registry->set('db_analytics', new \System\Framework\Db([
            'hostname' => CONFIG_DB_ANALYTICS_HOSTNAME,
            'username' => CONFIG_DB_ANALYTICS_USERNAME,
            'password' => CONFIG_DB_ANALYTICS_PASSWORD,
            'database' => CONFIG_DB_ANALYTICS_DATABASE,
        ]));
    }
}
```

**File:** `app/Providers/CacheServiceProvider.php`

```php
<?php

namespace App\Providers;

class CacheServiceProvider {
    
    public static function register($registry) {
        $driver = CONFIG_CACHE_DRIVER ?? 'file';
        
        switch ($driver) {
            case 'redis':
                $cache = new \System\Framework\Cache\RedisCache([
                    'host' => CONFIG_REDIS_HOST,
                    'port' => CONFIG_REDIS_PORT,
                ]);
                break;
            
            case 'memcache':
                $cache = new \System\Framework\Cache\MemcacheCache([
                    'host' => CONFIG_MEMCACHE_HOST,
                    'port' => CONFIG_MEMCACHE_PORT,
                ]);
                break;
            
            default:
                $cache = new \System\Framework\Cache\FileCache([
                    'path' => DIR_ROOT . 'storage/cache/'
                ]);
        }
        
        $registry->set('cache', $cache);
    }
}
```

### Loading Service Providers

**File:** `system/Framework.php`

```php
private function registerServices() {
    // Register core services
    // ...
    
    // Load service providers
    $this->loadServiceProviders();
}

private function loadServiceProviders() {
    $providers = [
        'App\Providers\DatabaseServiceProvider',
        'App\Providers\CacheServiceProvider',
        'App\Providers\MailServiceProvider',
    ];
    
    foreach ($providers as $provider) {
        if (class_exists($provider)) {
            $provider::register($this->registry);
        }
    }
}
```

---

## Best Practices

### 1. Use Constructor Injection for Required Dependencies

```php
// Good: Dependencies injected in constructor
class ServiceOrderService extends Service {
    
    private $paymentService;
    private $emailService;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        $this->load->service('PaymentService');
        $this->load->service('EmailService');
        
        $this->paymentService = $this->PaymentService;
        $this->emailService = $this->EmailService;
    }
}

// Bad: Loading dependencies in methods
class ServiceOrderService extends Service {
    
    public function processOrder() {
        $this->load->service('PaymentService'); // Loaded every time
        $this->PaymentService->process();
    }
}
```

### 2. Avoid Direct Registry Access

```php
// Good: Use magic methods
$users = $this->db->query("SELECT * FROM users")->rows;
$this->cache->set('users', $users);

// Avoid: Direct registry access
$users = $this->registry->get('db')->query("SELECT * FROM users")->rows;
$this->registry->get('cache')->set('users', $users);
```

### 3. Type Hint Dependencies

```php
class ServiceEmailService extends Service {
    
    private $mailer;
    private $logger;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        $this->mailer = $registry->get('mail');
        $this->logger = $registry->get('logger');
        
        // Type hint for IDE support
        if (!$this->mailer instanceof \System\Framework\Mail) {
            throw new \Exception('Mail service not properly configured');
        }
    }
}
```

### 4. Use Service Providers for Complex Setup

```php
// Good: Organized in service provider
class MailServiceProvider {
    public static function register($registry) {
        $config = [
            'driver' => CONFIG_MAIL_DRIVER,
            'host' => CONFIG_SMTP_HOST,
            'port' => CONFIG_SMTP_PORT,
            // ... more config
        ];
        
        $registry->set('mail', new Mail($config));
    }
}

// Avoid: Complex setup in main bootstrap
$registry->set('mail', new Mail([
    'driver' => CONFIG_MAIL_DRIVER,
    'host' => CONFIG_SMTP_HOST,
    // ... many lines of config
]));
```

### 5. Lazy Load Optional Dependencies

```php
class ControllerProduct extends Controller {
    
    public function index() {
        // Load only when needed
        if ($this->shouldTrackAnalytics()) {
            $this->load->service('AnalyticsService');
            $this->AnalyticsService->track('product_view');
        }
    }
}
```

### 6. Mock Dependencies in Tests

```php
class UserServiceTest extends TestCase {
    
    public function testRegister() {
        // Create mock registry
        $registry = new Registry();
        
        // Mock dependencies
        $dbMock = $this->createMock(Db::class);
        $dbMock->method('query')->willReturn(true);
        
        $mailMock = $this->createMock(Mail::class);
        $mailMock->expects($this->once())->method('send');
        
        // Register mocks
        $registry->set('db', $dbMock);
        $registry->set('mail', $mailMock);
        
        // Test service
        $service = new ServiceUserService($registry);
        $result = $service->register(['email' => 'test@example.com']);
        
        $this->assertTrue($result);
    }
}
```

### 7. Document Service Dependencies

```php
/**
 * User Service
 * 
 * Dependencies:
 * @property \System\Framework\Db $db
 * @property \System\Framework\Cache $cache
 * @property \System\Framework\Logger $logger
 * @property ServiceEmailService $EmailService
 */
class ServiceUserService extends Service {
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        $this->load->service('EmailService');
    }
}
```

---

## Singleton Services

### Implementing Singletons

Some services should be singletons:

```php
class Analytics {
    
    private static $instance = null;
    
    private function __construct() {
        // Private constructor
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function track($event, $data) {
        // Track analytics
    }
}

// Register singleton
$registry->set('analytics', Analytics::getInstance());
```

---

## Related Documentation

- **[Architecture](04-architecture.md)** - Application architecture
- **[Request Lifecycle](05-request-lifecycle.md)** - How DI fits in request flow
- **[Controllers](07-controllers.md)** - Using DI in controllers
- **[Services](11-services.md)** - Creating services with DI
- **[Testing](28-testing.md)** - Mocking dependencies

---

**Previous:** [Request Lifecycle](05-request-lifecycle.md)  
**Next:** [Controllers](07-controllers.md)
