# Architecture

EasyAPP follows the Model-View-Controller (MVC) architectural pattern with additional layers for enhanced flexibility and maintainability.

---

## Table of Contents

1. [Overview](#overview)
2. [MVC Pattern](#mvc-pattern)
3. [Application Layers](#application-layers)
4. [Component Communication](#component-communication)
5. [Registry Pattern](#registry-pattern)
6. [Event System](#event-system)
7. [Design Patterns](#design-patterns)
8. [Best Practices](#best-practices)

---

## Overview

EasyAPP architecture is designed to be:

- **Lightweight** - Minimal overhead and fast execution
- **Flexible** - Easy to extend and customize
- **Maintainable** - Clear separation of concerns
- **Testable** - Components are loosely coupled

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        HTTP Request                          │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                      index.php (Entry Point)                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │                     Framework                           │ │
│  │  • Load Configuration                                   │ │
│  │  • Initialize Registry                                  │ │
│  │  • Setup Error Handling                                 │ │
│  │  • Route Request                                        │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                         Router                               │
│  • Parse URL                                                 │
│  • Match Route                                               │
│  • Load Controller                                           │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                       Controller                             │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐            │
│  │   Request  │  │  Response  │  │   Load     │            │
│  │            │  │            │  │            │            │
│  └────────────┘  └────────────┘  └────────────┘            │
│         │                │                │                  │
│         ▼                ▼                ▼                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Business Logic                           │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────┬──────────────────────┬──────────────────────┘
               │                      │
               ▼                      ▼
┌──────────────────────┐   ┌──────────────────────┐
│      Services        │   │       Models         │
│  • EmailService      │   │  • User.php          │
│  • PaymentService    │   │  • Product.php       │
│  • UserService       │   │  • Order.php         │
└──────────┬───────────┘   └──────────┬───────────┘
           │                          │
           │                          ▼
           │               ┌──────────────────────┐
           │               │      Database        │
           │               │  ┌───────────────┐   │
           │               │  │      ORM      │   │
           │               │  └───────────────┘   │
           │               │  ┌───────────────┐   │
           │               │  │   Query API   │   │
           │               │  └───────────────┘   │
           │               └──────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────┐
│                          Libraries                            │
│  • Upload        • Pagination      • Pdf                      │
│  • Image         • Csv             • Cache                    │
└──────────────────────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────┐
│                            View                               │
│  • Load Template                                              │
│  • Render HTML                                                │
│  • Output Response                                            │
└──────────────────────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────┐
│                       HTTP Response                           │
└──────────────────────────────────────────────────────────────┘
```

---

## MVC Pattern

EasyAPP implements the classic MVC pattern with clear responsibilities for each layer.

### Model Layer

**Responsibility:** Data access, business logic, validation

**Location:** `app/model/`

```php
class ModelUser extends Model {
    
    // Data access
    public function getById($id) {
        return $this->db->query("SELECT * FROM users WHERE id = ?", [$id])->row;
    }
    
    // Business logic
    public function isEmailAvailable($email) {
        $result = $this->db->query("SELECT id FROM users WHERE email = ?", [$email]);
        return empty($result->row);
    }
    
    // Validation
    public function validate($data) {
        $errors = [];
        
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        }
        
        return $errors;
    }
}
```

### View Layer

**Responsibility:** Presentation, HTML rendering

**Location:** `app/view/`

```html
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
</head>
<body>
    <h1><?php echo $heading; ?></h1>
    
    <ul>
        <?php foreach ($users as $user): ?>
            <li><?php echo htmlspecialchars($user['name']); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
```

### Controller Layer

**Responsibility:** Request handling, coordination, response generation

**Location:** `app/controller/`

```php
class ControllerUser extends Controller {
    
    public function index() {
        // Load resources
        $userModel = $this->load->model('user');
        
        // Get data
        $data['users'] = $userModel->getAll();
        
        // Load view
        $output = $this->load->view('user/list.html', $data);
        
        // Send response
        $this->response->setOutput($output);
    }
}
```

---

## Application Layers

EasyAPP extends the basic MVC pattern with additional layers for better organization.

### 1. Presentation Layer (View)

**Purpose:** Display data to users

**Components:**
- HTML templates
- CSS stylesheets
- JavaScript

**Rules:**
- Minimal logic (loops, conditionals only)
- Always escape output
- No database queries

### 2. Controller Layer

**Purpose:** Handle HTTP requests and coordinate responses

**Components:**
- Request handling
- Input validation
- Response generation
- Resource loading

**Rules:**
- Keep thin (minimal business logic)
- Single responsibility
- No direct database queries

### 3. Service Layer

**Purpose:** Business logic and external integrations

**Components:**
- Complex business operations
- API integrations
- Email sending
- Payment processing

**Example:**

```php
class ServiceOrderService extends Service {
    
    public function processOrder($orderData) {
        // Validate inventory
        $this->validateInventory($orderData);
        
        // Process payment
        $payment = $this->processPayment($orderData);
        
        // Create order
        $orderId = $this->createOrder($orderData, $payment);
        
        // Send confirmation
        $this->sendConfirmation($orderId);
        
        return $orderId;
    }
}
```

### 4. Model Layer (Data Access)

**Purpose:** Database operations and data logic

**Components:**
- Database queries
- ORM models
- Data validation
- Relationships

**Traditional Model:**

```php
class ModelProduct extends Model {
    public function getAll() {
        return $this->db->query("SELECT * FROM products")->rows;
    }
}
```

**ORM Model:**

```php
class Product extends System\Framework\Orm {
    protected $table = 'products';
    protected $fillable = ['name', 'price', 'stock'];
    
    public function category() {
        return $this->belongsTo(Category::class);
    }
}
```

### 5. Library Layer

**Purpose:** Reusable utility components

**Components:**
- File upload
- Image processing
- PDF generation
- Pagination

**Example:**

```php
class LibraryUpload extends Library {
    public function upload($file, $path) {
        // Upload logic
    }
}
```

---

## Component Communication

### Vertical Communication (Request Flow)

```
Request → Router → Controller → Service → Model → Database
                                   ↓
                                Libraries
                                   ↓
Response ← View ← Controller ←──────────────────────┘
```

### Horizontal Communication

**Controllers can access:**
- Models (data access)
- Services (business logic)
- Libraries (utilities)
- Views (templates)

**Services can access:**
- Models
- Libraries
- Other Services

**Models can access:**
- Database
- Libraries
- Other Models

**Libraries should be independent:**
- No access to Models or Services
- Utility functions only

### Example Communication Flow

```php
// User registration flow

// 1. Controller receives request
class ControllerUser extends Controller {
    public function register() {
        $data = $this->request->post;
        
        // 2. Delegate to service
        $this->load->service('UserService');
        $userId = $this->UserService->register($data);
        
        // 3. Return response
        $this->response->redirect('/user/profile');
    }
}

// 2. Service handles business logic
class ServiceUserService extends Service {
    public function register($data) {
        // Load model and get instance
        $userModel = $this->load->model('user');
        
        // Validate
        if (!$this->validate($data)) {
            throw new Exception('Invalid data');
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        $userId = $userModel->create($data);
        
        // Send email
        $this->load->service('EmailService');
        $this->EmailService->sendWelcome($data);
        
        return $userId;
    }
}

// 3. Model handles data
class ModelUser extends Model {
    public function create($data) {
        $this->db->query(
            "INSERT INTO users (email, password, name) VALUES (?, ?, ?)",
            [$data['email'], $data['password'], $data['name']]
        );
        return $this->db->getLastId();
    }
}
```

---

## Registry Pattern

EasyAPP uses the Registry pattern for dependency injection.

### Registry Implementation

```php
class Framework {
    private $registry;
    
    public function __construct() {
        $this->registry = new System\Framework\Registry();
        
        // Register core components
        $this->registry->set('db', new System\Framework\Db());
        $this->registry->set('request', new System\Framework\Request());
        $this->registry->set('response', new System\Framework\Response());
        $this->registry->set('cache', new System\Framework\Cache());
        $this->registry->set('logger', new System\Framework\Logger());
    }
}
```

### Accessing Registry Components

All base classes (Controller, Model, Service, Library) have access to the registry:

```php
class Controller {
    protected $registry;
    
    public function __construct($registry) {
        $this->registry = $registry;
    }
    
    // Magic method for easy access
    public function __get($name) {
        return $this->registry->get($name);
    }
}
```

**Usage:**

```php
class ControllerUser extends Controller {
    public function index() {
        // Access registry components
        $users = $this->db->query("SELECT * FROM users")->rows;
        $postData = $this->request->post;
        $this->cache->set('users', $users);
        $this->logger->info('Users loaded');
        
        $this->response->setOutput($output);
    }
}
```

---

## Event System

EasyAPP includes an event system for decoupled communication.

### Event Architecture

```
Component A                  Event Bus                  Component B
    │                            │                            │
    │  trigger('user.created')   │                            │
    ├───────────────────────────>│                            │
    │                            │  listen('user.created')    │
    │                            │<───────────────────────────┤
    │                            │                            │
    │                            │  callback($data)           │
    │                            ├───────────────────────────>│
    │                            │                            │
```

### Registering Event Listeners

```php
// In controller or model
$this->event->register('user.created', function($data) {
    // Log event
    $this->logger->info('User created', $data);
    
    // Send welcome email
    $this->load->service('EmailService');
    $this->EmailService->sendWelcome($data);
});
```

### Triggering Events

```php
public function create($userData) {
    // Create user
    $userId = $this->db->query("INSERT INTO users ...")->getLastId();
    
    // Trigger event
    $this->event->trigger('user.created', [
        'user_id' => $userId,
        'email' => $userData['email']
    ]);
    
    return $userId;
}
```

---

## Design Patterns

EasyAPP implements several design patterns:

### 1. Model-View-Controller (MVC)

Separates presentation, business logic, and data access.

### 2. Registry Pattern

Central storage for shared objects and services.

```php
$this->registry->set('cache', new Cache());
$cache = $this->registry->get('cache');
```

### 3. Factory Pattern

Dynamic creation of components based on configuration.

```php
// Database factory
$db = DbFactory::create([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'myapp'
]);
```

### 4. Active Record Pattern

ORM models represent database records as objects.

```php
$user = User::find(1);
$user->name = 'John Doe';
$user->save();
```

### 5. Proxy Pattern

Method interception for cross-cutting concerns.

```php
class Proxy {
    public function __call($method, $args) {
        // Before method
        $this->beforeMethod($method);
        
        // Execute method
        $result = $this->object->$method(...$args);
        
        // After method
        $this->afterMethod($method, $result);
        
        return $result;
    }
}
```

### 6. Observer Pattern (Events)

Decoupled communication between components.

```php
$this->event->register('order.created', $callback);
$this->event->trigger('order.created', $data);
```

### 7. Strategy Pattern

Interchangeable algorithms (cache drivers, email drivers).

```php
// File cache strategy
$cache = new FileCache();

// Redis cache strategy
$cache = new RedisCache();

// Same interface
$cache->get('key');
$cache->set('key', 'value');
```

### 8. Facade Pattern

Simplified interface to complex subsystems.

```php
// Complex subsystem
$db->query(...);
$db->prepare(...);
$db->execute(...);

// Facade
$this->load->model('user')->getAll();
```

---

## Best Practices

### 1. Follow Layer Responsibilities

```php
// Good: Controller delegates to service
class ControllerOrder extends Controller {
    public function create() {
        $this->load->service('OrderService');
        $orderId = $this->OrderService->create($this->request->post);
        $this->response->redirect('/order/view?id=' . $orderId);
    }
}

// Bad: Controller contains business logic
class ControllerOrder extends Controller {
    public function create() {
        $data = $this->request->post;
        
        // Validate inventory (should be in service)
        $product = $this->load->model('product')->getById($data['product_id']);
        if ($product['stock'] < $data['quantity']) {
            throw new Exception('Out of stock');
        }
        
        // Process payment (should be in service)
        $payment = $this->processPayment($data);
        
        // Create order (should be in service)
        $orderId = $this->load->model('order')->create($data);
        
        // Send email (should be in service)
        $this->sendConfirmation($orderId);
    }
}
```

### 2. Use Dependency Injection

```php
// Good: Dependencies injected
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

// Bad: Hard-coded dependencies
class ServiceOrderService extends Service {
    public function processPayment() {
        $gateway = new StripeGateway(); // Hard-coded
        $gateway->charge(...);
    }
}
```

### 3. Keep Controllers Thin

```php
// Good: Thin controller
public function create() {
    $this->load->service('UserService');
    $userId = $this->UserService->register($this->request->post);
    $this->response->redirect('/user/profile');
}

// Bad: Fat controller
public function create() {
    // 50 lines of validation
    // 30 lines of business logic
    // 20 lines of database operations
    // 15 lines of email sending
}
```

### 4. Use Events for Decoupling

```php
// Good: Event-driven
public function create($userData) {
    $userId = $this->createUser($userData);
    $this->event->trigger('user.created', ['user_id' => $userId]);
    return $userId;
}

// Listeners are registered separately
$this->event->register('user.created', function($data) {
    $this->sendWelcomeEmail($data['user_id']);
});
$this->event->register('user.created', function($data) {
    $this->logUserCreation($data['user_id']);
});
$this->event->register('user.created', function($data) {
    $this->trackAnalytics($data['user_id']);
});
```

### 5. Organize by Feature, Not Layer

```
Good:
app/
├── User/
│   ├── UserController.php
│   ├── UserService.php
│   ├── User.php (model)
│   └── views/

Bad:
app/
├── controllers/
│   └── UserController.php
├── services/
│   └── UserService.php
└── models/
    └── User.php
```

---

## Related Documentation

- **[Request Lifecycle](05-request-lifecycle.md)** - How requests are processed
- **[Dependency Injection](06-dependency-injection.md)** - Registry and DI
- **[Controllers](07-controllers.md)** - Controller layer
- **[Models](08-models-traditional.md)** - Model layer
- **[Services](11-services.md)** - Service layer

---

**Previous:** [Directory Structure](03-directory-structure.md)  
**Next:** [Request Lifecycle](05-request-lifecycle.md)
