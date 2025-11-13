<div align="center">

# EasyAPP Framework

**A Modern, Lightweight PHP Framework for Rapid Development**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL%20v3-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-2.0-orange.svg)](https://github.com/script-php/EasyAPP)

[Features](#features) • [Installation](#installation) • [Quick Start](#quick-start) • [Documentation](#documentation) • [Contributing](#contributing)

---

</div>

EasyAPP is a powerful yet elegant PHP framework designed to make web application development fast, secure, and enjoyable. Built with modern PHP practices, it features a clean MVC architecture, advanced routing, ORM support, and comprehensive development tools.

## Features

### Core Architecture
- **Modern MVC Pattern** - Clean separation with Controllers, Models, Views, Services, and Libraries
- **Dependency Injection** - Registry-based DI container with automatic service resolution
- **Event System** - Powerful event-driven architecture with lifecycle hooks
- **Service Layer** - Business logic separation with service components

### Database & ORM
- **Active Record ORM** - Eloquent-style ORM with relationships (hasOne, hasMany, belongsTo, belongsToMany)
- **Query Builder** - Fluent PDO-based database abstraction layer
- **Migrations** - Database version control and schema management
- **Prepared Statements** - Built-in SQL injection protection

### Routing & Requests
- **Advanced Router** - RESTful routing with parameters, patterns, and named routes
- **Multiple Route Formats** - Support for `/`, `|`, and `-` separators
- **Request Handling** - Comprehensive request/response abstractions
- **Method Spoofing** - PUT, PATCH, DELETE support via POST

### Development Tools
- **CLI Tool** - Powerful command-line interface for scaffolding and management
- **Debug Mode** - Beautiful error pages with stack traces and context
- **Logging System** - PSR-3 compatible multi-level logging
- **Testing Support** - Built-in testing utilities and examples

### Performance & Caching
- **Smart Caching** - File-based caching with automatic invalidation
- **Class Caching** - Automatic model/controller/service instance caching
- **Lazy Loading** - On-demand component loading for optimal performance
- **Proxy Pattern** - AOP-style method interception and monitoring

### Security
- **CSRF Protection** - Automatic token generation and validation
- **Input Sanitization** - XSS prevention and data filtering
- **Secure Headers** - Configurable security headers
- **Path Traversal Protection** - File access security validation

### Internationalization
- **Multi-language Support** - Built-in i18n system with language files
- **Template System** - Parameter substitution with language variables
- **Dynamic Language Switching** - Runtime language detection and switching

## Requirements

- **PHP:** 7.4 or higher (8.0+ recommended)
- **Extensions:** PDO, JSON, MBString (recommended)
- **Web Server:** Apache with mod_rewrite or Nginx
- **Database:** MySQL 5.7+, MariaDB 10.2+, or PostgreSQL
- **Composer:** Optional (for dependency management)

## Installation

### Via Git Clone

```bash
# Clone the repository
git clone https://github.com/script-php/EasyAPP.git my-project
cd my-project

# Configure environment
cp .env.example .env
nano .env  # Edit with your settings

# Set permissions (Linux/Mac)
chmod -R 755 storage/
chmod 644 .env

# Start development server
php easy serve localhost 8000
```

### Via Composer (Coming Soon)

```bash
composer create-project script-php/easyapp my-project
cd my-project
php easy serve
```

### Web Server Configuration

<details>
<summary><b>Apache Configuration</b></summary>

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot "/path/to/my-project"
    
    <Directory "/path/to/my-project">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "/path/to/logs/myapp-error.log"
    CustomLog "/path/to/logs/myapp-access.log" common
</VirtualHost>
```

</details>

<details>
<summary><b>Nginx Configuration</b></summary>

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /path/to/my-project;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

</details>

## Quick Start

### 1. Create Your First Controller

```bash
php easy make:controller Welcome
```

This generates `app/controller/welcome.php`:

```php
<?php

class ControllerWelcome extends Controller {
    
    public function index() {
        $data = [];
        $data['title'] = 'Welcome to EasyAPP';
        $data['message'] = 'Your application is ready!';
        
        $this->response->setOutput(
            $this->load->view('welcome/index.html', $data)
        );
    }
}
```

### 2. Define Routes

Edit `app/router.php`:

```php
<?php

// Basic routes
$router->get('/', 'home');
$router->get('/welcome', 'welcome');

// RESTful routes
$router->get('/users', 'users|index');
$router->get('/users/{id}', 'users|show');
$router->post('/users', 'users|create');
$router->put('/users/{id}', 'users|update');
$router->delete('/users/{id}', 'users|delete');

// Route patterns
$router->pattern('id', '[0-9]+');

// Fallback
$router->fallback('not_found');
```

### 3. Create a Model

**Traditional Style:**
```bash
php easy make:model User
```

```php
<?php

class ModelUser extends Model {
    
    public function getAll() {
        $sql = "SELECT * FROM `" . DB_PREFIX . "users` ORDER BY created_at DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE id = :id";
        $query = $this->db->query($sql, [':id' => $id]);
        return $query->row;
    }
}
```

**ORM Style:**
```php
<?php

class User extends Orm {
    
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'status'];
    
    // Define relationships
    public function posts() {
        return $this->hasMany(Post::class, 'user_id');
    }
    
    public function profile() {
        return $this->hasOne(Profile::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts()->where('status', 'published')->get();
```

### 4. Use in Controller

```php
<?php

class ControllerUsers extends Controller {
    
    public function index() {
        // Traditional style
        $userModel = $this->load->model('user');
        $users = $userModel->getAll();
        
        // OR ORM style
        $users = User::with('posts')->where('active', 1)->get();
        
        $data['users'] = $users;
        
        $this->response->setOutput(
            $this->load->view('users/index.html', $data)
        );
    }
    
    public function show() {
        $id = $this->request->get('id');
        
        // ORM with relationships
        $user = User::with(['posts', 'profile'])->find($id);
        
        if (!$user) {
            $this->response->redirect('/404');
            return;
        }
        
        $data['user'] = $user;
        
        $this->response->setOutput(
            $this->load->view('users/show.html', $data)
        );
    }
}
```

### 5. Create Views

Create `app/view/users/index.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Users</title>
</head>
<body>
    <h1>User List</h1>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <a href="/users/<?php echo $user['id']; ?>">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
```

## Core Concepts

### Model Loading Patterns

EasyAPP supports flexible model loading with three patterns:

```php
// Pattern 1: Explicit (Recommended)
$userModel = $this->load->model('user');
$users = $userModel->getAll();

// Pattern 2: Magic Access (Auto-registered with model_ prefix)
$this->load->model('user');
$users = $this->model_user->getAll();

// Pattern 3: Method Chaining
$users = $this->load->model('user')->getAll();

// Subdirectories
$this->load->model('common/helper');
$data = $this->model_common_helper->getData();
```

### Dependency Injection

All components receive the registry through constructor injection:

```php
class ControllerUsers extends Controller {
    
    private $userService;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Load service
        $this->load->service('UserService');
        $this->userService = $this->UserService;
    }
    
    public function register() {
        $result = $this->userService->register($this->request->post);
        $this->response->json(['success' => true, 'user_id' => $result]);
    }
}
```

### Services Layer

Services encapsulate business logic:

```bash
php easy make:service OrderService
```

```php
<?php

class ServiceOrderService extends Service {
    
    public function processOrder($orderData) {
        // Load dependencies
        $orderModel = $this->load->model('order');
        $inventoryModel = $this->load->model('inventory');
        $paymentModel = $this->load->model('payment');
        
        // Business logic
        if (!$inventoryModel->checkStock($orderData['items'])) {
            throw new Exception('Insufficient stock');
        }
        
        $orderId = $orderModel->create($orderData);
        $paymentModel->process($orderId, $orderData['payment']);
        $inventoryModel->reserve($orderData['items']);
        
        // Trigger event
        $this->events->trigger('order.created', ['order_id' => $orderId]);
        
        return $orderId;
    }
}
```

## Directory Structure

```
EasyAPP/
│
├── app/                      # Application Layer
│   ├── controller/           # Controllers (HTTP request handlers)
│   ├── model/                # Models (data access layer)
│   ├── view/                 # Views (presentation templates)
│   ├── service/              # Services (business logic)
│   ├── library/              # Libraries (reusable components)
│   ├── language/             # Language files (i18n)
│   │   ├── en-gb/           # English translations
│   │   ├── fr-fr/           # French translations
│   │   └── ro-ro/           # Romanian translations
│   ├── config.php           # Application configuration
│   ├── router.php           # Route definitions
│   └── helper.php           # Application helper functions
│
├── system/                   # Framework Core
│   ├── Framework/            # Core framework classes
│   │   ├── Db.php           # Database abstraction
│   │   ├── Router.php       # Routing system
│   │   ├── Orm.php          # Active Record ORM
│   │   ├── Cache.php        # Caching system
│   │   ├── Events.php       # Event system
│   │   ├── Request.php      # HTTP request
│   │   ├── Response.php     # HTTP response
│   │   ├── Load.php         # Component loader
│   │   ├── Registry.php     # DI container
│   │   ├── Migration.php    # Migration system
│   │   └── Exceptions/      # Exception classes
│   ├── Autoloader.php       # PSR-4 autoloader
│   ├── Framework.php        # Framework bootstrap
│   ├── Controller.php       # Base controller
│   ├── Model.php            # Base model
│   ├── Service.php          # Base service
│   ├── Library.php          # Base library
│   └── Vendor/              # Composer dependencies
│
├── storage/                  # Storage (writable)
│   ├── cache/               # Cache files
│   ├── logs/                # Application logs
│   ├── sessions/            # Session data
│   └── uploads/             # File uploads
│
├── migrations/               # Database Migrations
│   └── 001_create_initial_user_system.php
│
├── assets/                   # Public Assets
│   └── app/
│       ├── images/          # Image files
│       ├── javascript/      # JavaScript files
│       └── stylesheet/      # CSS files
│
├── tests/                    # Test Suite
│   ├── OrmTest.php          # ORM tests
│   ├── OrmRelationshipsTest.php
│   └── SystemIntegrationTest.php
│
├── docs/                     # Documentation
│   ├── 01-getting-started.md
│   ├── 02-configuration.md
│   ├── 03-directory-structure.md
│   ├── 04-architecture.md
│   ├── 05-request-lifecycle.md
│   ├── 06-dependency-injection.md
│   ├── 07-controllers.md
│   ├── 08-models-traditional.md
│   ├── 09-models-orm.md
│   ├── 10-views.md
│   ├── 11-services.md
│   ├── 12-libraries.md
│   ├── 13-language.md
│   └── 14-model-loading.md
│
├── .env                      # Environment configuration
├── .env.example             # Environment template
├── .htaccess                # Apache rewrite rules
├── nginx.conf               # Nginx configuration example
├── index.php                # Application entry point
├── config.php               # Framework configuration
├── easy                     # CLI tool (Unix)
├── composer.json            # Composer dependencies
├── LICENSE                  # GPL v3 License
└── README.md                # This file
```

## CLI Commands

EasyAPP includes a powerful CLI tool for rapid development:

### Code Generation

```bash
# Controllers
php easy make:controller UserController
php easy make:controller Admin/UserController

# Models
php easy make:model User
php easy make:model Common/Helper

# Services
php easy make:service UserService
php easy make:service Payment/StripeService

# Libraries
php easy make:library ImageProcessor
```

### Database Migrations

```bash
# Create migration
php easy make:migration create_users_table

# Run migrations
php easy migrate

# Rollback
php easy migrate:rollback

# Migration status
php easy migrate:status
```

### Development Tools

```bash
# Start development server
php easy serve localhost 8000

# Clear cache
php easy cache:clear

# View routes
php easy routes

# Run tests
php easy test

# Show version
php easy version

# Help
php easy help
```

## Configuration

### Environment Variables

Create `.env` file in project root:

```env
# Application Settings
APP_NAME="My EasyAPP"
APP_ENV=development
APP_URL=http://localhost
APP_TIMEZONE=UTC
DEBUG=true

# Database Configuration
DB_DRIVER=mysql
DB_HOSTNAME=localhost
DB_DATABASE=easyapp_db
DB_USERNAME=root
DB_PASSWORD=your_password
DB_PORT=3306
DB_PREFIX=ea_

# Cache Settings
CACHE_ENABLED=true
CACHE_DRIVER=file
CACHE_TTL=3600

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=7200
SESSION_COOKIE_NAME=easyapp_session

# Security
CSRF_PROTECTION=true
CSRF_TOKEN_NAME=csrf_token

# Logging
LOG_ENABLED=true
LOG_LEVEL=debug
LOG_PATH=storage/logs/

# Email (Optional)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Application Configuration

Edit `app/config.php`:

```php
<?php

// Application Settings
$config['platform'] = env('APP_NAME', 'EasyAPP');
$config['debug'] = env('DEBUG', false);
$config['timezone'] = env('APP_TIMEZONE', 'UTC');

// Startup Services (loaded on application bootstrap)
$config['services'] = [
    'auth',                    // Authentication service
    'email|initialize',        // Email service with initialize method
];

// Language Settings
$config['language_default'] = 'en-gb';
$config['language_auto_detect'] = true;

// Security
$config['csrf_protection'] = env('CSRF_PROTECTION', true);
$config['session_security'] = true;

// Performance
$config['cache_views'] = true;
$config['compress_output'] = true;
```

## Database Usage

### Traditional PDO Style

```php
class ModelUser extends Model {
    
    public function getActiveUsers() {
        $sql = "SELECT * FROM `" . DB_PREFIX . "users` 
                WHERE active = :active 
                ORDER BY created_at DESC";
        
        $query = $this->db->query($sql, [':active' => 1]);
        
        return $query->rows;  // All rows
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE id = :id";
        $query = $this->db->query($sql, [':id' => $id]);
        
        return $query->row;  // Single row
    }
    
    public function createUser($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "users` 
                (name, email, password, created_at) 
                VALUES (:name, :email, :password, NOW())";
        
        $this->db->query($sql, [
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
        
        return $this->db->getLastId();
    }
}
```

### ORM Style (Active Record)

```php
class User extends Orm {
    
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'email', 'status'];
    protected $hidden = ['password'];
    protected $timestamps = true;
    
    // Relationships
    public function posts() {
        return $this->hasMany(Post::class, 'user_id');
    }
    
    public function profile() {
        return $this->hasOne(Profile::class, 'user_id');
    }
    
    public function roles() {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }
}

// Usage Examples
// Find by ID
$user = User::find(1);

// Find with conditions
$users = User::where('status', 'active')
             ->where('role', 'admin')
             ->orderBy('created_at', 'DESC')
             ->limit(10)
             ->get();

// Eager load relationships
$user = User::with(['posts', 'profile'])->find(1);
$posts = $user->posts;

// Create
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active'
]);

// Update
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();

// Delete
$user = User::find(1);
$user->delete();

// Query builder methods
$count = User::where('active', 1)->count();
$exists = User::where('email', 'test@example.com')->exists();
$user = User::firstOrCreate(['email' => 'new@example.com'], ['name' => 'New User']);
```

### Transactions

```php
$this->db->beginTransaction();

try {
    // Create user
    $userId = $this->load->model('user')->create($userData);
    
    // Create profile
    $this->load->model('profile')->create([
        'user_id' => $userId,
        'bio' => $profileData['bio']
    ]);
    
    // Commit transaction
    $this->db->commit();
    
    return $userId;
    
} catch (Exception $e) {
    // Rollback on error
    $this->db->rollBack();
    
    $this->logger->error('Transaction failed: ' . $e->getMessage());
    throw $e;
}
```

## Routing

### Route Definition

Edit `app/router.php`:

```php
<?php

// GET Routes
$router->get('/', 'home');
$router->get('/about', 'pages|about');
$router->get('/contact', 'pages|contact');

// POST Routes
$router->post('/contact/send', 'contact|send');
$router->post('/login', 'auth|login');

// RESTful Resource Routes
$router->get('/users', 'users|index');           // List
$router->get('/users/{id}', 'users|show');       // Show
$router->post('/users', 'users|create');         // Create
$router->put('/users/{id}', 'users|update');     // Update
$router->delete('/users/{id}', 'users|delete');  // Delete

// Route Parameters with Patterns
$router->pattern('id', '[0-9]+');
$router->pattern('slug', '[a-z0-9-]+');
$router->pattern('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

$router->get('/posts/{slug}', 'posts|show');
$router->get('/api/users/{uuid}', 'api/users|show');

// Multiple Parameters
$router->get('/blog/{category}/{slug}', 'blog|show');

// Optional Parameters
$router->get('/search/{query?}', 'search|index');

// Grouped Routes
$router->prefix('/admin', function($router) {
    $router->get('/dashboard', 'admin/dashboard');
    $router->get('/users', 'admin/users|index');
    $router->get('/settings', 'admin/settings');
});

// API Routes
$router->prefix('/api/v1', function($router) {
    $router->get('/users', 'api/users|index');
    $router->post('/users', 'api/users|create');
    $router->get('/users/{id}', 'api/users|show');
});

// Fallback (404)
$router->fallback('not_found');
```

### Accessing Route Parameters

```php
class ControllerUsers extends Controller {
    
    public function show() {
        // Method 1: Via router
        $id = $this->router->getParam('id');
        
        // Method 2: Via request
        $id = $this->request->get('id');
        
        // Load user
        $user = User::find($id);
        
        if (!$user) {
            $this->response->redirect('/404');
            return;
        }
        
        $data['user'] = $user;
        $this->response->setOutput($this->load->view('users/show.html', $data));
    }
    
    public function blogPost() {
        $category = $this->router->getParam('category');
        $slug = $this->router->getParam('slug');
        
        $post = Post::where('category', $category)
                    ->where('slug', $slug)
                    ->first();
        
        // ... render view
    }
}
```

### Route Formats

EasyAPP supports multiple route separator formats:

```php
// Slash format (recommended)
$router->get('/users', 'users|index');

// Pipe format
$router->get('/users', 'users|index');

// Dash format
$router->get('/users', 'users-index');
```

## Views and Templates

### Loading Views

```php
class ControllerProducts extends Controller {
    
    public function index() {
        // Prepare data
        $data = [];
        $data['title'] = 'Product List';
        $data['products'] = Product::where('active', 1)->get();
        $data['categories'] = Category::all();
        
        // Load view
        $this->response->setOutput(
            $this->load->view('products/index.html', $data)
        );
    }
}
```

### View Templates

Create `app/view/products/index.html`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="/assets/app/stylesheet/style.css">
</head>
<body>
    <div class="container">
        <h1><?php echo $title; ?></h1>
        
        <!-- Category Filter -->
        <div class="categories">
            <?php foreach ($categories as $category): ?>
                <a href="/products?category=<?php echo $category->id; ?>" class="btn">
                    <?php echo htmlspecialchars($category->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Product Grid -->
        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo $product->image; ?>" alt="<?php echo htmlspecialchars($product->name); ?>">
                        <h3><?php echo htmlspecialchars($product->name); ?></h3>
                        <p class="price">$<?php echo number_format($product->price, 2); ?></p>
                        <a href="/products/<?php echo $product->id; ?>" class="btn btn-primary">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results">No products found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="/assets/app/javascript/app.js"></script>
</body>
</html>
```

### Partial Views

```php
// Load partial view
$header = $this->load->view('common/header.html', $data);
$footer = $this->load->view('common/footer.html', $data);

// Combine views
$data['header'] = $header;
$data['footer'] = $footer;

$this->response->setOutput($this->load->view('page.html', $data));
```

### JSON Responses

```php
class ControllerApi extends Controller {
    
    public function users() {
        $users = User::all();
        
        $this->response->json([
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ]);
    }
    
    public function create() {
        try {
            $user = User::create($this->request->post);
            
            $this->response->json([
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $user->id
            ]);
            
        } catch (Exception $e) {
            $this->response->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## Security

### CSRF Protection

```php
// Automatically enabled in forms
<form method="POST" action="/users/create">
    <?php echo $this->csrf->token(); ?>
    
    <input type="text" name="username">
    <button type="submit">Submit</button>
</form>

// Manual validation
if (!$this->csrf->validate()) {
    throw new Exception('CSRF token validation failed');
}
```

### Input Validation

```php
class ControllerUsers extends Controller {
    
    public function create() {
        $rules = [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'age' => 'integer|min:18'
        ];
        
        $validator = $this->validator->make($this->request->post, $rules);
        
        if ($validator->fails()) {
            $this->response->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
            return;
        }
        
        // Process valid data
        $user = User::create($validator->validated());
        
        $this->response->json(['success' => true, 'user_id' => $user->id]);
    }
}
```

### SQL Injection Prevention

```php
// ✓ SAFE: Using prepared statements
$sql = "SELECT * FROM users WHERE email = :email";
$query = $this->db->query($sql, [':email' => $email]);

// ✓ SAFE: Using ORM
$user = User::where('email', $email)->first();

// ✗ UNSAFE: Direct string concatenation (don't do this)
$sql = "SELECT * FROM users WHERE email = '" . $email . "'";
```

### XSS Prevention

```php
// Always escape output in views
<?php echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8'); ?>

// Short helper
<?php echo e($user_input); ?>
```

### Secure Headers

```php
// Configured in app/config.php
$config['security_headers'] = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin'
];
```

## Caching

### Basic Cache Usage

```php
// Check and get from cache
$users = $this->cache->get('users_list');

if (!$users) {
    // Cache miss - fetch from database
    $users = User::where('active', 1)->get();
    
    // Store in cache for 1 hour (3600 seconds)
    $this->cache->set('users_list', $users, 3600);
}

return $users;
```

### Cache Remember Pattern

```php
// Fetch or cache in one call
$products = $this->cache->remember('featured_products', function() {
    return Product::where('featured', 1)
                  ->orderBy('views', 'DESC')
                  ->limit(10)
                  ->get();
}, 3600);
```

### Cache Management

```php
// Delete specific cache key
$this->cache->delete('users_list');

// Clear all cache
$this->cache->clear();

// Check if key exists
if ($this->cache->has('settings')) {
    $settings = $this->cache->get('settings');
}

// Cache forever (no expiration)
$this->cache->forever('app_settings', $settings);
```

### Cache Tags (Grouping)

```php
// Tag-based caching
$this->cache->tags(['users', 'active'])->set('user_list', $users, 3600);

// Flush by tag
$this->cache->tags(['users'])->flush();
```

## Logging

### Log Levels

```php
// Emergency: System is unusable
$this->logger->emergency('Database server is down');

// Alert: Action must be taken immediately
$this->logger->alert('Disk space critically low');

// Critical: Critical conditions
$this->logger->critical('Application component unavailable');

// Error: Runtime errors
$this->logger->error('Failed to send email', [
    'to' => $email,
    'error' => $e->getMessage()
]);

// Warning: Exceptional occurrences that are not errors
$this->logger->warning('Deprecated API called', ['method' => 'oldMethod']);

// Notice: Normal but significant events
$this->logger->notice('User password changed', ['user_id' => $userId]);

// Info: Interesting events
$this->logger->info('User logged in', [
    'user_id' => $userId,
    'ip' => $this->request->ip()
]);

// Debug: Detailed debug information
$this->logger->debug('API Response', ['data' => $response]);
```

### Exception Logging

```php
try {
    $result = $this->someRiskyOperation();
    
} catch (Exception $e) {
    // Log exception with full context
    $this->logger->exception($e, [
        'user_id' => $this->session->get('user_id'),
        'request' => $this->request->all()
    ]);
    
    // Re-throw or handle
    throw $e;
}
```

### Custom Log Channels

```php
// Log to specific file
$this->logger->channel('payments')->info('Payment processed', [
    'order_id' => $orderId,
    'amount' => $amount
]);

$this->logger->channel('security')->warning('Failed login attempt', [
    'username' => $username,
    'ip' => $ip
]);
```

## Events System

### Registering Event Listeners

```php
// In app/config.php or bootstrap
$this->events->on('user.created', function($data) {
    // Send welcome email
    $this->load->service('EmailService')->sendWelcome($data['user']);
});

$this->events->on('order.completed', function($data) {
    // Update inventory
    $this->load->model('inventory')->updateStock($data['items']);
    
    // Send confirmation email
    $this->load->service('EmailService')->orderConfirmation($data['order']);
});

$this->events->on('product.viewed', function($data) {
    // Track analytics
    $this->load->model('analytics')->track('product_view', $data['product_id']);
});
```

### Triggering Events

```php
class ControllerUsers extends Controller {
    
    public function register() {
        $userData = $this->request->post;
        
        // Create user
        $user = User::create($userData);
        
        // Trigger event
        $this->events->trigger('user.created', [
            'user' => $user,
            'ip' => $this->request->ip(),
            'timestamp' => time()
        ]);
        
        $this->response->json(['success' => true, 'user_id' => $user->id]);
    }
}
```

### Framework Events

```php
// Before/After controller execution
$this->events->on('before:controller.execute', function($data) {
    // Log request
    $this->logger->info('Controller executing', [
        'route' => $data['route'],
        'method' => $data['method']
    ]);
});

// Model loaded
$this->events->on('model.loaded', function($data) {
    // Track model usage
});

// View rendered
$this->events->on('after:view', function($data) {
    // Minify HTML output
    $data['output'] = $this->minify($data['output']);
});
```

### Priority Events

```php
// Higher priority (executed first)
$this->events->on('user.login', function($data) {
    // Check if user is banned
}, 100);

// Lower priority (executed later)
$this->events->on('user.login', function($data) {
    // Log login
}, 10);
```

## Testing

### Running Tests

```bash
# Run all tests
php easy test

# Run specific test file
php easy test tests/OrmTest.php

# Run with verbose output
php easy test --verbose
```

### Writing Tests

Create `tests/UserTest.php`:

```php
<?php

class UserTest extends TestCase {
    
    public function setUp() {
        parent::setUp();
        
        // Set up test database
        $this->db->query("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255),
            email VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    public function testUserCreation() {
        // Create user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        // Assertions
        $this->assertNotNull($user->id);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }
    
    public function testUserRelationships() {
        // Create user with posts
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        
        $post = Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'Content here'
        ]);
        
        // Test relationship
        $userPosts = $user->posts()->get();
        
        $this->assertCount(1, $userPosts);
        $this->assertEquals('Test Post', $userPosts[0]->title);
    }
    
    public function tearDown() {
        // Clean up
        $this->db->query("DROP TABLE IF EXISTS users");
        $this->db->query("DROP TABLE IF EXISTS posts");
        
        parent::tearDown();
    }
}
```

### Test Examples

See the `tests/` directory for comprehensive examples:
- `tests/OrmTest.php` - ORM functionality tests
- `tests/OrmRelationshipsTest.php` - Relationship tests
- `tests/OrmAdvancedTest.php` - Advanced ORM features
- `tests/SystemIntegrationTest.php` - Full system tests

## Documentation

Comprehensive documentation is available in the `docs/` directory:

### Getting Started
- [Installation and Setup](docs/01-getting-started.md)
- [Configuration](docs/02-configuration.md)
- [Directory Structure](docs/03-directory-structure.md)

### Core Concepts
- [Architecture Overview](docs/04-architecture.md)
- [Request Lifecycle](docs/05-request-lifecycle.md)
- [Dependency Injection](docs/06-dependency-injection.md)

### Components
- [Controllers](docs/07-controllers.md)
- [Models (Traditional)](docs/08-models-traditional.md)
- [Models (ORM)](docs/09-models-orm.md)
- [Views](docs/10-views.md)
- [Services](docs/11-services.md)
- [Libraries](docs/12-libraries.md)
- [Language Files](docs/13-language.md)
- [Model Loading Patterns](docs/14-model-loading.md)

### Additional Guides
- [CLI Guide](CLI_GUIDE.md) - Command-line interface
- [Migration Guide](MIGRATION_GUIDE.md) - Database migrations
- [Database Usage](DATABASE_USAGE.md) - Database layer
- [Contributing](CONTRIBUTING.md) - Contribution guidelines

## Contributing

We welcome contributions from the community! Here's how you can help:

### Ways to Contribute

- **Report Bugs** - Submit detailed bug reports with reproduction steps
- **Suggest Features** - Propose new features or improvements
- **Improve Documentation** - Fix typos, clarify explanations, add examples
- **Submit Pull Requests** - Fix bugs or implement new features
- **Star the Repository** - Show your support!

### Development Setup

```bash
# Fork and clone the repository
git clone https://github.com/YOUR-USERNAME/EasyAPP.git
cd EasyAPP

# Create a feature branch
git checkout -b feature/your-feature-name

# Make your changes and test
php easy test

# Commit with clear message
git commit -m "Add: Brief description of your changes"

# Push to your fork
git push origin feature/your-feature-name

# Create a Pull Request
```

### Guidelines

- Follow PSR-12 coding standards
- Write clear, descriptive commit messages
- Add tests for new features
- Update documentation as needed
- Ensure all tests pass before submitting PR

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and release notes.

## Support & Community

### Getting Help

- **Documentation** - Check the [docs/](docs/) directory
- **GitHub Issues** - Report bugs or ask questions
- **Official Website** - Visit [script-php.ro](https://script-php.ro)
- **Email** - Contact the maintainers

### Useful Resources

- [Stack Overflow](https://stackoverflow.com/questions/tagged/easyapp) - Tag: `easyapp`
- [GitHub Discussions](https://github.com/script-php/EasyAPP/discussions) - Community forum
- [Example Projects](https://github.com/script-php/EasyAPP-examples) - Sample applications

## Project Status

- **Stable** - Version 2.0 (dev-orm branch)
- **Actively Maintained** - Regular updates and bug fixes
- **Growing** - New features in development

## Acknowledgments

Special thanks to all contributors who have helped make EasyAPP better:
- Community members for bug reports and feature suggestions
- Contributors for code improvements and documentation
- Everyone who has starred and shared the project

## License

EasyAPP Framework is open-source software licensed under the **GPL v3 License**.

```
Copyright (c) 2022-2025, script-php.ro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE) file for full license text.

## Author

**YoYo**
- Website: [script-php.ro](https://script-php.ro)
- GitHub: [@script-php](https://github.com/script-php)

---

<div align="center">

**Built with care for the PHP community**

[Back to Top](#easyapp-framework)

</div>
