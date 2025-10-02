# EasyAPP Framework Documentation

> **A comprehensive guide to building applications with EasyAPP Framework**

Welcome to the EasyAPP Framework documentation! This guide will help you understand and master all aspects of the framework, from basic concepts to advanced features.

---

## Table of Contents

- [Architecture Overview](#-architecture-overview)
- [Getting Started](#-getting-started)
- [Directory Structure](#-directory-structure)
- [MVC Pattern](#-mvc-pattern)
- [Routing System](#-routing-system)
- [Database Usage](#-database-usage)
- [Views & Templates](#-views--templates)
- [Internationalization](#-internationalization)
- [Services & Business Logic](#-services--business-logic)
- [Caching](#-caching)
- [Logging](#-logging)
- [Security](#-security)
- [Testing](#-testing)
- [CLI Commands](#-cli-commands)
- [Performance](#-performance)
- [Configuration](#-configuration)
- [API Reference](#-api-reference)

---

## Architecture Overview

EasyAPP follows the **Model-View-Controller (MVC)** architectural pattern with additional layers for services and dependency injection. The framework is built around these core concepts:

- **Registry Pattern**: Centralized service container for dependency injection
- **Proxy Pattern**: AOP-style method interception and monitoring  
- **Event System**: Hook into framework lifecycle with events
- **Modular Design**: Clean separation of concerns

### Key Components

| Component | Purpose | Location |
|-----------|---------|----------|
| **Controllers** | Handle HTTP requests and responses | `app/controller/` |
| **Models** | Data access and business logic | `app/model/` |
| **Views** | Presentation layer templates | `app/view/` |
| **Services** | Reusable business logic | `app/service/` |
| **Languages** | Internationalization files | `app/language/` |

---

## Getting Started

### Prerequisites

- **PHP 7.4+** with PDO extension
- **Web server** (Apache/Nginx) with mod_rewrite
- **Composer** (optional, for dependencies)

### Quick Setup

1. **Download & Extract**
   ```bash
   git clone https://github.com/script-php/EasyAPP.git
   cd EasyAPP
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

3. **Set Web Server**
   Point your web server document root to the project directory

4. **Create Your First Page**
   ```bash
   php easyapp make:controller Welcome
   ```

5. **Visit Your Application**
   Open your browser and navigate to your domain

---

## Directory Structure

```
EasyAPP/
├── app/                     # Your application code
│   ├── controller/          # Controllers (URL handlers)
│   ├── model/              # Models (data layer)
│   ├── view/               # Views (templates)
│   ├── service/            # Services (business logic)
│   ├── language/           # Translations
│   ├── config.php          # App configuration
│   └── router.php          # Route definitions
├── system/                 # Framework core
│   ├── Framework/          # Core classes
│   ├── Library/            # Framework libraries
│   └── Vendor/             # Third-party libraries
├── storage/                # Storage directory
│   ├── cache/              # Cache files
│   ├── logs/               # Application logs
│   └── sessions/           # Session storage
├── assets/                 # Public assets
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript
│   └── images/             # Images
├── tests/                  # Test files
├── .env                    # Environment config
├── index.php               # Entry point
└── easyapp                 # CLI tool
```

---

## MVC Pattern

### Controllers

Controllers handle incoming HTTP requests and coordinate responses. They act as the entry point for your application logic.

#### Creating a Controller

**Using CLI:**
```bash
php easyapp make:controller User
```

**Manual creation:**
```php
<?php
// app/controller/user.php

class ControllerUser extends Controller {
    
    public function __construct($registry) {
        parent::__construct($registry);
    }
    
    // Default action (accessed via /user)
    public function index() {
        $data = [];
        $data['title'] = 'Users';
        $data['users'] = $this->load->model('user')->getAll();
        
        $this->response->setOutput($this->load->view('user/index.html', $data));
    }
    
    // Specific action (accessed via /user/profile or user|profile)
    public function profile() {
        $userId = $this->request->get('id', 1);
        $user = $this->load->model('user')->getById($userId);
        
        if (!$user) {
            $this->response->redirect('/404');
            return;
        }
        
        $data = [];
        $data['user'] = $user;
        
        $this->response->setOutput($this->load->view('user/profile.html', $data));
    }
}
```

#### Controller Features

- **Registry Access**: `$this->registry` provides access to all services
- **Magic Methods**: `$this->request`, `$this->response`, `$this->db`, etc.
- **Load System**: `$this->load->model()`, `$this->load->view()`, etc.
- **Event Integration**: Automatic event triggering on method calls

### Models

Models handle data access and contain business logic related to your data.

#### Creating a Model

**Using CLI:**
```bash
php easyapp make:model User
```

**Manual creation:**
```php
<?php
// app/model/user.php

class ModelUser extends Model {
    
    public function __construct($registry) {
        parent::__construct($registry);
    }
    
    public function getAll() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $query = $this->db->query($sql, [':id' => $id]);
        return $query->row;
    }
    
    public function create($data) {
        $sql = "INSERT INTO users (name, email, created_at) VALUES (:name, :email, :created_at)";
        $params = [
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->query($sql, $params);
        return $this->db->getLastId();
    }
    
    public function update($id, $data) {
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':email' => $data['email']
        ];
        
        return $this->db->query($sql, $params);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM users WHERE id = :id";
        return $this->db->query($sql, [':id' => $id]);
    }
}
```

#### Using Models in Controllers

```php
// Load and use model
$this->load->model('user');
$users = $this->model_user->getAll();

// Or using direct method
$user = $this->load->model('user')->getById(1);
```

---

## Routing System

EasyAPP provides a powerful routing system that supports both traditional query string routes and modern clean URLs.

### Route Types

#### 1. Traditional Routes (Query String)
```
http://yoursite.com/index.php?route=controller
http://yoursite.com/index.php?route=controller|method
http://yoursite.com/index.php?route=folder/controller|method
```

#### 2. Clean URLs (Modern Routing)
```
http://yoursite.com/users
http://yoursite.com/users/123
http://yoursite.com/api/users
```

### Defining Routes

Routes are defined in `app/router.php`:

```php
<?php
// app/router.php

// Basic routes
$router->get('/', 'home');
$router->get('/about', 'about');
$router->post('/contact', 'contact|submit');

// Routes with parameters
$router->get('/users/{id}', 'users|show');
$router->get('/blog/{slug}', 'blog|post');

// Pattern constraints
$router->pattern('id', '[0-9]+');
$router->pattern('slug', '[a-zA-Z0-9-]+');

// RESTful routes
$router->get('/api/users', 'api/users|index');
$router->post('/api/users', 'api/users|create');
$router->put('/api/users/{id}', 'api/users|update');
$router->delete('/api/users/{id}', 'api/users|delete');

// Fallback for 404 errors
$router->fallback('not_found');
```

### Route Parameters

Access route parameters in your controller:

```php
// Route: /users/{id}
public function show() {
    $id = $this->router->getParam('id');
    // or
    $id = $this->request->get('id');
    
    $user = $this->load->model('user')->getById($id);
    // ... rest of method
}
```

### Route Patterns

Define custom patterns for route parameters:

```php
// Numeric IDs only
$router->pattern('id', '[0-9]+');

// Alphanumeric slugs
$router->pattern('slug', '[a-zA-Z0-9-_]+');

// Date format
$router->pattern('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

// Then use in routes
$router->get('/posts/{date}/{slug}', 'blog|show');
```

---

## Database Usage

EasyAPP provides a PDO-based database abstraction layer that supports multiple database drivers.

### Configuration

Set up your database in `.env`:

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password
DB_PORT=3306
DB_ENCODING=utf8mb4
```

### Basic Queries

```php
// SELECT queries
$sql = "SELECT * FROM users WHERE active = :active";
$query = $this->db->query($sql, [':active' => 1]);

// Get single row
$user = $query->row;

// Get all rows
$users = $query->rows;

// Get row count
$count = $query->num_rows;

// INSERT queries
$sql = "INSERT INTO users (name, email) VALUES (:name, :email)";
$params = [':name' => 'John Doe', ':email' => 'john@example.com'];
$this->db->query($sql, $params);

// Get inserted ID
$newId = $this->db->getLastId();

// UPDATE queries
$sql = "UPDATE users SET name = :name WHERE id = :id";
$this->db->query($sql, [':name' => 'Jane Doe', ':id' => 1]);

// Check affected rows
$affected = $this->db->countAffected();

// DELETE queries
$sql = "DELETE FROM users WHERE id = :id";
$this->db->query($sql, [':id' => 1]);
```

### Transactions

```php
$this->db->beginTransaction();

try {
    // Multiple database operations
    $this->db->query("INSERT INTO users ...", $params1);
    $this->db->query("INSERT INTO profiles ...", $params2);
    $this->db->query("UPDATE counters ...", $params3);
    
    $this->db->commit();
    echo "All operations completed successfully";
} catch (Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

### Query Result Object

```php
$query = $this->db->query($sql, $params);

// Properties
$query->row;        // First row as associative array
$query->rows;       // All rows as array of associative arrays
$query->num_rows;   // Number of rows returned

// Example usage
if ($query->num_rows > 0) {
    foreach ($query->rows as $row) {
        echo $row['name'];
    }
}
```

---

## Views & Templates

Views are responsible for presenting data to users. EasyAPP supports flexible templating with PHP-based templates.

### Creating Views

Views are stored in `app/view/` and typically use `.html` extension:

```html
<!-- app/view/user/profile.html -->
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($user['name']); ?> - Profile</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($user['name']); ?></h1>
    <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
    <p>Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
</body>
</html>
```

### Loading Views

In your controller:

```php
public function profile() {
    $data = [];
    $data['user'] = $this->load->model('user')->getById(1);
    $data['title'] = $data['user']['name'] . ' - Profile';
    
    // Load view with data
    $this->response->setOutput($this->load->view('user/profile.html', $data));
}
```

### Template Inheritance

Create a base template:

```html
<!-- app/view/base.html -->
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($title) ? $title : 'EasyAPP'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <nav><!-- Navigation --></nav>
    </header>
    
    <main>
        <?php echo isset($content) ? $content : ''; ?>
    </main>
    
    <footer>
        <!-- Footer content -->
    </footer>
    
    <script src="/assets/js/app.js"></script>
</body>
</html>
```

Use in controller:

```php
public function index() {
    $data = [];
    $data['title'] = 'User List';
    $data['content'] = $this->load->view('user/list.html', $data);
    
    $this->response->setOutput($this->load->view('base.html', $data));
}
```

### Partial Views

Include reusable components:

```php
// In your main view
<?php echo $this->load->view('partials/header.html', $data); ?>
<div class="content">
    <!-- Main content -->
</div>
<?php echo $this->load->view('partials/footer.html'); ?>
```

---

## Internationalization

EasyAPP provides built-in support for multiple languages and localization.

### Language Files

Create language files in `app/language/{locale}/`:

```php
<?php
// app/language/en-gb/common.php
$_['text_welcome'] = 'Welcome';
$_['text_hello'] = 'Hello %s!';
$_['button_submit'] = 'Submit';
$_['error_required'] = 'This field is required';

// app/language/es-es/common.php
$_['text_welcome'] = 'Bienvenido';
$_['text_hello'] = '¡Hola %s!';
$_['button_submit'] = 'Enviar';
$_['error_required'] = 'Este campo es obligatorio';
```

### Loading Languages

In your controller:

```php
public function index() {
    // Load language file
    $this->load->language('common');
    
    $data = [];
    $data['text_welcome'] = $this->language->get('text_welcome');
    $data['text_hello'] = sprintf($this->language->get('text_hello'), 'John');
    
    $this->response->setOutput($this->load->view('home.html', $data));
}
```

### Using in Views

```html
<h1><?php echo $text_welcome; ?></h1>
<p><?php echo $text_hello; ?></p>
<button type="submit"><?php echo $button_submit; ?></button>
```

### Setting Language

Configure default language in `.env`:

```env
DEFAULT_LANGUAGE=en-gb
```

Or set dynamically:

```php
// In controller or service
$this->language->setLanguage('es-es');
```

---

## Services & Business Logic

Services encapsulate reusable business logic that can be shared across multiple controllers.

### Creating Services

**Using CLI:**
```bash
php easyapp make:service Email
```

**Manual creation:**
```php
<?php
// app/service/email.php

class ServiceEmail extends Service {
    
    public function __construct($registry) {
        parent::__construct($registry);
    }
    
    public function sendWelcomeEmail($user) {
        $subject = 'Welcome to Our Platform!';
        $message = $this->buildWelcomeMessage($user);
        
        return $this->mail->send([
            'to' => $user['email'],
            'subject' => $subject,
            'html' => $message
        ]);
    }
    
    public function sendPasswordReset($user, $token) {
        $subject = 'Password Reset Request';
        $resetLink = $this->url->link('auth/reset', 'token=' . $token);
        
        $message = $this->load->view('email/password_reset.html', [
            'user' => $user,
            'reset_link' => $resetLink
        ]);
        
        return $this->mail->send([
            'to' => $user['email'],
            'subject' => $subject,
            'html' => $message
        ]);
    }
    
    private function buildWelcomeMessage($user) {
        return $this->load->view('email/welcome.html', ['user' => $user]);
    }
}
```

### Using Services

Load and use services in controllers:

```php
public function register() {
    if ($this->request->server('REQUEST_METHOD') === 'POST') {
        // Create user
        $userId = $this->load->model('user')->create($this->request->post);
        $user = $this->load->model('user')->getById($userId);
        
        // Send welcome email via service
        $this->load->service('email|sendWelcomeEmail', $user);
        
        $this->response->redirect('/welcome');
    }
}
```

### Service Autoloading

Configure services to be loaded automatically in `app/config.php`:

```php
$config['services'] = [
    'email',
    'notification',
    'payment'
];
```

---

## Caching

EasyAPP includes a built-in caching system to improve performance.

### Basic Caching

```php
// Store data in cache
$this->cache->set('user_list', $users, 3600); // Cache for 1 hour

// Retrieve from cache
$users = $this->cache->get('user_list');

if (!$users) {
    // Cache miss - load from database
    $users = $this->load->model('user')->getAll();
    $this->cache->set('user_list', $users, 3600);
}
```

### Remember Pattern

```php
// Cache with callback
$users = $this->cache->remember('user_list', function() {
    return $this->load->model('user')->getAll();
}, 3600);
```

### Cache Management

```php
// Check if key exists
if ($this->cache->has('user_list')) {
    // Cache exists
}

// Delete specific cache
$this->cache->delete('user_list');

// Clear all cache
$this->cache->clear();
```

### Configuration

Enable caching in `.env`:

```env
CACHE_ENABLED=true
CACHE_DRIVER=file
CACHE_TTL=3600
```

---

## Logging

EasyAPP provides comprehensive logging capabilities for debugging and monitoring.

### Basic Logging

```php
// Different log levels
$this->logger->debug('Debug information', ['user_id' => 123]);
$this->logger->info('User logged in', ['user_id' => 123]);
$this->logger->notice('Unusual activity detected');
$this->logger->warning('Deprecated method used');
$this->logger->error('Database connection failed');
$this->logger->critical('System is unstable');
$this->logger->alert('Immediate action required');
$this->logger->emergency('System is down');

// Log exceptions
try {
    // Some risky operation
} catch (Exception $e) {
    $this->logger->exception($e);
    throw $e;
}
```

### Configuration

Configure logging in `.env`:

```env
LOG_LEVEL=error
LOG_FILE=storage/logs/error.log
```

### Log Levels

| Level | Description | When to Use |
|-------|-------------|-------------|
| **emergency** | System unusable | System crashes, major failures |
| **alert** | Immediate action required | Critical security issues |
| **critical** | Critical conditions | Application errors, exceptions |
| **error** | Error conditions | Runtime errors, exceptions |
| **warning** | Warning conditions | Deprecated usage, poor practices |
| **notice** | Normal but significant | Significant events |
| **info** | Informational | General information |
| **debug** | Debug-level messages | Detailed debug information |

---

## Security

EasyAPP includes several built-in security features to protect your application.

### Input Sanitization

All input is automatically sanitized:

```php
// Input is automatically cleaned
$username = $this->request->post('username'); // Already sanitized
$id = $this->request->get('id'); // Already sanitized
```

### CSRF Protection

Enable CSRF protection in `.env`:

```env
CSRF_PROTECTION=true
```

Use in controllers:

```php
public function submit() {
    // Check CSRF token
    if (!$this->request->csrf('post')) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Process form
}
```

### Database Security

Use parameterized queries (automatically handled):

```php
// Safe - uses prepared statements
$sql = "SELECT * FROM users WHERE id = :id";
$user = $this->db->query($sql, [':id' => $id])->row;

// Never do this - SQL injection risk
// $sql = "SELECT * FROM users WHERE id = " . $id; // DANGEROUS!
```

### Password Hashing

```php
// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify password
if (password_verify($inputPassword, $hashedPassword)) {
    // Password is correct
}
```

### Session Security

Configure secure sessions in `.env`:

```env
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_LIFETIME=7200
```

---

## Testing

EasyAPP includes a built-in testing framework for unit and integration tests.

### Creating Tests

```php
<?php
// tests/UserTest.php

require_once 'system/TestCase.php';

class UserTest extends TestCase {
    
    public function testUserCreation() {
        $user = $this->load->model('user');
        $result = $user->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertTrue($result > 0);
        $this->assertNotNull($user->getById($result));
    }
    
    public function testUserValidation() {
        $user = $this->load->model('user');
        
        // Test invalid data
        $this->expectException(ValidationException::class);
        $user->create(['name' => '']); // Should throw exception
    }
    
    public function testUserList() {
        $user = $this->load->model('user');
        $users = $user->getAll();
        
        $this->assertTrue(is_array($users));
        $this->assertCount(0, $users); // Assuming empty database
    }
}
```

### Running Tests

```bash
# Run all tests
php test

# Run specific test
php test tests/UserTest.php
```

### Test Assertions

Available assertion methods:

```php
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertCount($expectedCount, $array);
$this->assertContains($needle, $haystack);
```

---

## CLI Commands

EasyAPP provides a powerful CLI tool for development tasks.

### Available Commands

```bash
# Generate files
php easyapp make:controller UserController
php easyapp make:model User
php easyapp make:service EmailService

# Development server
php easyapp serve                    # localhost:8000
php easyapp serve localhost 3000    # Custom host/port

# Cache management
php easyapp clear:cache

# Database migrations (future feature)
php easyapp db:migrate

# Help and version
php easyapp help
php easyapp --version
```

### Generated File Templates

The CLI generates professional, ready-to-use templates:

**Controller Template:**
```php
class ControllerUser extends Controller {
    public function index() { /* List users */ }
    public function create() { /* Create user */ }
    public function edit() { /* Edit user */ }
    public function delete() { /* Delete user */ }
}
```

**Model Template:**
```php
class ModelUser extends Model {
    public function getAll() { /* Get all records */ }
    public function getById($id) { /* Get by ID */ }
    public function create($data) { /* Create record */ }
    public function update($id, $data) { /* Update record */ }
    public function delete($id) { /* Delete record */ }
}
```

---

## Performance

### Optimization Tips

1. **Enable Caching**
   ```env
   CACHE_ENABLED=true
   CACHE_TTL=3600
   ```

2. **Use Database Indexing**
   ```sql
   CREATE INDEX idx_users_email ON users(email);
   CREATE INDEX idx_posts_created_at ON posts(created_at);
   ```

3. **Optimize Queries**
   ```php
   // Good - specific fields
   $sql = "SELECT id, name, email FROM users WHERE active = 1";
   
   // Avoid - selecting all fields
   $sql = "SELECT * FROM users";
   ```

4. **Enable Compression**
   ```env
   COMPRESSION=6
   ```

5. **Use Services for Heavy Logic**
   ```php
   // Move complex logic to services
   $this->load->service('analytics|processUserData', $userData);
   ```

### Monitoring Performance

```php
// Log slow queries
$start = microtime(true);
$result = $this->db->query($sql, $params);
$time = microtime(true) - $start;

if ($time > 1.0) { // Log queries over 1 second
    $this->logger->warning('Slow query detected', [
        'query' => $sql,
        'time' => $time
    ]);
}
```

---

## Configuration

### Environment Configuration (.env)

```env
# Application
APP_ENV=dev
DEBUG=true
APP_URL=http://localhost
APP_NAME=MyApp

# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=myapp_db
DB_USER=root
DB_PASS=secret
DB_PORT=3306

# Cache
CACHE_ENABLED=true
CACHE_DRIVER=file
CACHE_TTL=3600

# Security
CSRF_PROTECTION=true
INPUT_SANITIZATION=true

# Logging
LOG_LEVEL=error
LOG_FILE=storage/logs/error.log

# Performance
COMPRESSION=6
```

### Application Configuration (app/config.php)

```php
<?php
// Override defaults
$config['platform'] = 'My Amazing App';
$config['debug'] = env('DEBUG', false);

// Services to auto-load
$config['services'] = [
    'email',
    'notification',
    'analytics'
];

// Custom settings
$config['upload_max_size'] = '10MB';
$config['allowed_file_types'] = ['jpg', 'png', 'pdf'];
```

---

## API Reference

### Registry Methods

```php
$this->registry->get($key);           // Get service
$this->registry->set($key, $value);   // Set service
$this->registry->has($key);           // Check if exists
```

### Request Methods

```php
$this->request->get($key, $default);     // GET parameter
$this->request->post($key, $default);    // POST parameter
$this->request->cookie($key, $default);  // Cookie value
$this->request->server($key, $default);  // Server variable
$this->request->files;                   // Uploaded files
$this->request->ip;                      // Client IP
```

### Response Methods

```php
$this->response->setOutput($content);           // Set response body
$this->response->addHeader($header);            // Add HTTP header
$this->response->redirect($url, $status);       // Redirect
$this->response->setCompression($level);        // Set compression
```

### Database Methods

```php
$this->db->query($sql, $params);        // Execute query
$this->db->getLastId();                  // Last insert ID
$this->db->countAffected();              // Affected rows
$this->db->beginTransaction();           // Start transaction
$this->db->commit();                     // Commit transaction
$this->db->rollBack();                   // Rollback transaction
```

### Load Methods

```php
$this->load->model($model);                      // Load model
$this->load->view($view, $data);                 // Load view
$this->load->language($language);                // Load language
$this->load->service($service, ...$args);        // Load service
$this->load->library($library, ...$args);        // Load library
```

### Router Methods

```php
$router->get($uri, $handler);            // GET route
$router->post($uri, $handler);           // POST route
$router->put($uri, $handler);            // PUT route
$router->delete($uri, $handler);         // DELETE route
$router->pattern($key, $pattern);        // Route pattern
$router->fallback($handler);             // Fallback route
```

### Cache Methods

```php
$this->cache->get($key, $default);              // Get cached value
$this->cache->set($key, $value, $ttl);          // Set cached value
$this->cache->has($key);                         // Check if cached
$this->cache->delete($key);                      // Delete cached value
$this->cache->clear();                           // Clear all cache
$this->cache->remember($key, $callback, $ttl);   // Remember pattern
```

### Logger Methods

```php
$this->logger->emergency($message, $context);   // Emergency level
$this->logger->alert($message, $context);       // Alert level
$this->logger->critical($message, $context);    // Critical level
$this->logger->error($message, $context);       // Error level
$this->logger->warning($message, $context);     // Warning level
$this->logger->notice($message, $context);      // Notice level
$this->logger->info($message, $context);        // Info level
$this->logger->debug($message, $context);       // Debug level
$this->logger->exception($exception);           // Log exception
```

---

## Troubleshooting

### Common Issues

**1. "Class not found" errors**
- Check file naming conventions
- Verify autoloader is working
- Ensure proper namespace usage

**2. Database connection errors**
- Verify credentials in `.env`
- Check database server status
- Confirm PDO extension is installed

**3. Routes not working**
- Enable mod_rewrite
- Check `.htaccess` file
- Verify route definitions

**4. Permission errors**
- Check file permissions (755 for directories, 644 for files)
- Ensure web server can write to `storage/` directory

### Debug Mode

Enable debug mode for detailed error information:

```env
DEBUG=true
```

This will show:
- Detailed error messages
- Stack traces
- Database queries
- Performance metrics

---

** Happy coding with EasyAPP Framework!**

> For more examples and advanced topics, visit our [GitHub repository](https://github.com/script-php/EasyAPP) or check the `examples/` directory.