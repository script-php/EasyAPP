# Controllers

Controllers are the entry point for your application's HTTP requests. They coordinate the flow between models, views, and other components.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Controller Basics](#controller-basics)
3. [Creating Controllers](#creating-controllers)
4. [Controller Structure](#controller-structure)
5. [Request Handling](#request-handling)
6. [Response Generation](#response-generation)
7. [Loading Resources](#loading-resources)
8. [Best Practices](#best-practices)
9. [Advanced Topics](#advanced-topics)

---

## Introduction

Controllers in EasyAPP follow the MVC (Model-View-Controller) pattern. They:

- Handle incoming HTTP requests
- Process user input
- Interact with models to retrieve or manipulate data
- Load views to present information
- Return responses to the client

All controllers extend the base `Controller` class, which provides access to the framework's registry and core services.

---

## Controller Basics

### Base Controller Class

All controllers inherit from the abstract `Controller` class:

```php
abstract class Controller {
    protected $registry;
    
    public function __construct($registry) {
        $this->registry = $registry;
    }
    
    public function __get($key) {
        return $this->registry->get($key);
    }
    
    public function __set($key, $value) {
        $this->registry->set($key, $value);
    }
}
```

### Automatic Property Access

Through magic methods, controllers have automatic access to framework services:

```php
$this->request   // Request object
$this->response  // Response object
$this->db        // Database connection
$this->cache     // Cache system
$this->logger    // Logger
$this->load      // Loader for models, views, etc.
$this->router    // Router instance
$this->events    // Event system
```

---

## Creating Controllers

### Using CLI (Recommended)

Generate a controller using the command-line tool:

```bash
php easy make:controller User
```

This creates `app/controller/user.php` with a basic structure.

### Manual Creation

Create a file in `app/controller/` directory:

**File:** `app/controller/user.php`

```php
<?php

/**
 * User Controller
 * Handles user-related requests
 */
class ControllerUser extends Controller {
    
    public function index() {
        // Default action
    }
}
```

### Naming Conventions

- **Class Name:** `Controller` + PascalCase name
  - Example: `ControllerUser`, `ControllerProduct`, `ControllerUserProfile`
- **File Name:** lowercase, matches the route name
  - Example: `user.php`, `product.php`, `user_profile.php`
- **Method Name:** camelCase for action methods
  - Example: `index()`, `create()`, `updateProfile()`

---

## Controller Structure

### Basic Controller Template

```php
<?php

/**
 * ControllerProduct
 * Handles product management
 */
class ControllerProduct extends Controller {
    
    /**
     * Constructor
     * Initialize controller-specific setup
     */
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Optional: Additional initialization
        // Example: Load language files, check authentication, etc.
    }
    
    /**
     * Default action (index)
     * Accessible via: /product or /product/index
     */
    public function index() {
        $data = [];
        $data['title'] = 'Product List';
        
        // Load data
        $products = $this->load->model('product')->getAll();
        $data['products'] = $products;
        
        // Render view
        $this->response->setOutput(
            $this->load->view('product/list.html', $data)
        );
    }
    
    /**
     * Show single product
     * Accessible via: /product/view or product|view
     */
    public function view() {
        $product_id = $this->request->get('id', 0);
        
        if (!$product_id) {
            $this->response->redirect('/product');
            return;
        }
        
        $data = [];
        $product = $this->load->model('product')->getById($product_id);
        
        if (!$product) {
            $this->response->redirect('/404');
            return;
        }
        
        $data['product'] = $product;
        $data['title'] = $product['name'];
        
        $this->response->setOutput(
            $this->load->view('product/view.html', $data)
        );
    }
    
    /**
     * Create new product
     * Accessible via: /product/create
     */
    public function create() {
        $data = [];
        $data['title'] = 'Create Product';
        
        if ($this->request->server('REQUEST_METHOD') === 'POST') {
            // Handle form submission
            $productData = [
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description'),
                'price' => $this->request->post('price'),
            ];
            
            $product_id = $this->load->model('product')->create($productData);
            
            if ($product_id) {
                $this->response->redirect('/product/view?id=' . $product_id);
                return;
            }
            
            $data['error'] = 'Failed to create product';
        }
        
        $this->response->setOutput(
            $this->load->view('product/form.html', $data)
        );
    }
    
    /**
     * Update existing product
     * Accessible via: /product/edit
     */
    public function edit() {
        $product_id = $this->request->get('id', 0);
        
        if (!$product_id) {
            $this->response->redirect('/product');
            return;
        }
        
        $data = [];
        $product = $this->load->model('product')->getById($product_id);
        
        if (!$product) {
            $this->response->redirect('/404');
            return;
        }
        
        if ($this->request->server('REQUEST_METHOD') === 'POST') {
            $updateData = [
                'name' => $this->request->post('name'),
                'description' => $this->request->post('description'),
                'price' => $this->request->post('price'),
            ];
            
            $success = $this->load->model('product')->update($product_id, $updateData);
            
            if ($success) {
                $this->response->redirect('/product/view?id=' . $product_id);
                return;
            }
            
            $data['error'] = 'Failed to update product';
        }
        
        $data['product'] = $product;
        $data['title'] = 'Edit: ' . $product['name'];
        
        $this->response->setOutput(
            $this->load->view('product/form.html', $data)
        );
    }
    
    /**
     * Delete product
     * Accessible via: /product/delete
     */
    public function delete() {
        $product_id = $this->request->get('id', 0);
        
        if ($product_id) {
            $this->load->model('product')->delete($product_id);
        }
        
        $this->response->redirect('/product');
    }
}
```

---

## Request Handling

### Accessing Request Data

#### GET Parameters

```php
// Get single parameter with default value
$id = $this->request->get('id', 0);
$page = $this->request->get('page', 1);

// Get all GET parameters
$params = $this->request->get;
```

#### POST Parameters

```php
// Get single POST parameter
$username = $this->request->post('username');
$email = $this->request->post('email', '');

// Get all POST data
$postData = $this->request->post;
```

#### Request Method

```php
$method = $this->request->server('REQUEST_METHOD');

if ($method === 'POST') {
    // Handle POST request
}

if ($method === 'GET') {
    // Handle GET request
}
```

#### Other Request Information

```php
// Client IP address
$ip = $this->request->ip;

// Server variables
$userAgent = $this->request->server('HTTP_USER_AGENT');
$referer = $this->request->server('HTTP_REFERER');

// Uploaded files
$files = $this->request->files;

// Cookies
$token = $this->request->cookie('session_token');
```

#### Route Parameters

When using modern routing with parameters:

```php
// Route: /users/{id}
$userId = $this->router->getParam('id');

// Route: /blog/{year}/{month}/{slug}
$year = $this->router->getParam('year');
$month = $this->router->getParam('month');
$slug = $this->router->getParam('slug');
```

---

## Response Generation

### Setting Output

```php
// Set HTML output
$this->response->setOutput($html);

// Set JSON output
$data = ['status' => 'success', 'message' => 'Data saved'];
$this->response->setOutput(json_encode($data));
```

### Headers

```php
// Add custom headers
$this->response->addHeader('Content-Type: application/json');
$this->response->addHeader('X-Custom-Header: value');

// Set cache headers
$this->response->addHeader('Cache-Control: no-cache, must-revalidate');
```

### Redirects

```php
// Simple redirect
$this->response->redirect('/target-url');

// Redirect with HTTP status
$this->response->redirect('/new-location', 301);

// Redirect with query parameters
$this->response->redirect('/product/view?id=' . $product_id);
```

### Compression

```php
// Enable output compression
$this->response->setCompression(6); // Compression level 1-9
```

---

## Loading Resources

### Loading Models

```php
// Style 1: Capture returned instance (recommended for clarity)
$userModel = $this->load->model('user');
$users = $userModel->getAll();

// Style 2: Use magic access (model is auto-registered in registry)
$this->load->model('user');
$users = $this->model_user->getAll();

// Style 3: Method chaining (immediate use)
$users = $this->load->model('user')->getAll();

// Load model from subdirectory
$settingsModel = $this->load->model('common/settings');
$settings = $settingsModel->get();

// OR with magic access (slashes become underscores, model_ prefix added)
$this->load->model('common/settings');
$settings = $this->model_common_settings->get();
```

**Note:** When you call `$this->load->model()`, the framework:
1. Returns the model instance directly (you can capture it in a variable)
2. Automatically registers the model in the registry with `model_` prefix for magic access

This gives you flexibility:
- Use **Style 1** for clarity and when you need multiple references
- Use **Style 2** for quick one-time access with `$this->model_name`
- Use **Style 3** for immediate method chaining

For subdirectories, the registry key converts slashes to underscores with `model_` prefix: `common/settings` becomes `$this->model_common_settings`.

### Loading Views

```php
// Load view and pass data
$data = ['title' => 'Page Title', 'content' => 'Content'];
$html = $this->load->view('template.html', $data);

// Load view from subdirectory
$html = $this->load->view('user/profile.html', $data);

// Set view as output
$this->response->setOutput(
    $this->load->view('page.html', $data)
);
```

### Loading Language Files

```php
// Load language file
$this->load->language('common');

// Access language variables
$text_welcome = $this->language->get('text_welcome');
$button_submit = $this->language->get('button_submit');

// Use in data array
$data['text_welcome'] = $this->language->get('text_welcome');
```

### Loading Services

```php
// Execute service method
$result = $this->load->service('email|send', $to, $subject, $body);

// Execute service with default method
$this->load->service('analytics|trackPageView');
```

### Loading Libraries

```php
// Load custom library
$this->load->library('validation');

// Use library
$this->validation->validate($data, $rules);
```

---

## Best Practices

### 1. Single Responsibility

Each controller should handle a specific resource or feature:

```php
// Good: Focused controller
class ControllerUser extends Controller {
    // User-specific actions
}

class ControllerProduct extends Controller {
    // Product-specific actions
}

// Avoid: Controller with mixed responsibilities
class ControllerEverything extends Controller {
    // Users, products, orders, etc.
}
```

### 2. Thin Controllers

Keep controllers lightweight. Move business logic to models or services:

```php
// Good: Thin controller
public function create() {
    if ($this->request->server('REQUEST_METHOD') === 'POST') {
        $data = $this->request->post;
        $result = $this->load->model('user')->createUser($data);
        
        if ($result) {
            $this->response->redirect('/user/view?id=' . $result);
        }
    }
}

// Avoid: Fat controller with business logic
public function create() {
    if ($this->request->server('REQUEST_METHOD') === 'POST') {
        // Lots of validation, processing, calculations
        // Email sending, file uploads, etc.
        // This belongs in models/services
    }
}
```

### 3. Consistent Action Naming

Use standard REST-like action names:

```php
public function index()   // List all resources
public function view()    // Show single resource
public function create()  // Create new resource (form + processing)
public function edit()    // Edit resource (form + processing)
public function delete()  // Delete resource
```

### 4. Input Validation

Always validate and sanitize input:

```php
public function save() {
    $id = $this->request->post('id', 0);
    $name = trim($this->request->post('name', ''));
    
    // Validate
    if (empty($name)) {
        $data['error'] = 'Name is required';
        $this->response->setOutput($this->load->view('form.html', $data));
        return;
    }
    
    // Process...
}
```

### 5. Error Handling

Handle errors gracefully:

```php
public function view() {
    $id = $this->request->get('id', 0);
    
    if (!$id) {
        // Invalid ID
        $this->response->redirect('/404');
        return;
    }
    
    try {
        $item = $this->load->model('item')->getById($id);
        
        if (!$item) {
            // Not found
            $this->response->redirect('/404');
            return;
        }
        
        // Success
        $data['item'] = $item;
        $this->response->setOutput($this->load->view('view.html', $data));
        
    } catch (Exception $e) {
        // Log error
        $this->logger->error('Failed to load item', ['id' => $id, 'error' => $e->getMessage()]);
        
        // Show error page
        $this->response->redirect('/error');
    }
}
```

### 6. Use Descriptive Method Names

```php
// Good
public function approveOrder() { }
public function cancelSubscription() { }
public function exportToPdf() { }

// Avoid
public function do() { }
public function process() { }
public function action() { }
```

### 7. Return Early

Reduce nesting by returning early:

```php
// Good
public function edit() {
    $id = $this->request->get('id');
    if (!$id) {
        $this->response->redirect('/list');
        return;
    }
    
    $item = $this->load->model('item')->getById($id);
    if (!$item) {
        $this->response->redirect('/404');
        return;
    }
    
    // Main logic here
}

// Avoid deep nesting
public function edit() {
    $id = $this->request->get('id');
    if ($id) {
        $item = $this->load->model('item')->getById($id);
        if ($item) {
            // Deep nesting makes code hard to read
        }
    }
}
```

---

## Advanced Topics

### Authentication and Authorization

```php
class ControllerAccount extends Controller {
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->response->redirect('/login');
        }
    }
    
    private function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    private function hasPermission($permission) {
        // Check user permissions
        return in_array($permission, $_SESSION['permissions'] ?? []);
    }
    
    public function edit() {
        if (!$this->hasPermission('edit_profile')) {
            $this->response->redirect('/forbidden');
            return;
        }
        
        // Allow editing
    }
}
```

### AJAX Responses

```php
public function ajaxSearch() {
    // Ensure AJAX request
    if (!$this->request->server('HTTP_X_REQUESTED_WITH')) {
        $this->response->setOutput(json_encode(['error' => 'Invalid request']));
        return;
    }
    
    $query = $this->request->post('query', '');
    $results = $this->load->model('product')->search($query);
    
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode([
        'success' => true,
        'results' => $results
    ]));
}
```

### File Uploads

```php
public function upload() {
    if ($this->request->server('REQUEST_METHOD') === 'POST') {
        if (isset($this->request->files['file'])) {
            $file = $this->request->files['file'];
            
            // Validate
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploadPath = PATH . 'storage/uploads/';
                $filename = uniqid() . '_' . basename($file['name']);
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                    $data['success'] = 'File uploaded successfully';
                    $data['filename'] = $filename;
                } else {
                    $data['error'] = 'Failed to move uploaded file';
                }
            } else {
                $data['error'] = 'Upload error: ' . $file['error'];
            }
        }
    }
    
    $this->response->setOutput($this->load->view('upload.html', $data));
}
```

### Event Integration

```php
public function save() {
    $data = $this->request->post;
    
    // Trigger before event
    $this->events->trigger('product.before_save', $data);
    
    // Save product
    $product_id = $this->load->model('product')->create($data);
    
    // Trigger after event
    $this->events->trigger('product.after_save', [
        'id' => $product_id,
        'data' => $data
    ]);
    
    $this->response->redirect('/product/view?id=' . $product_id);
}
```

---

## Related Documentation

- **[Models (Traditional)](08-models-traditional.md)** - Data layer
- **[Views](10-views.md)** - Presentation layer
- **[Routing](15-routing.md)** - URL mapping
- **[Request Lifecycle](05-request-lifecycle.md)** - How requests flow through the system

---

**Previous:** [Dependency Injection](06-dependency-injection.md)  
**Next:** [Models (Traditional)](08-models-traditional.md)
