# EasyAPP Framework

**A Modern, Lightweight PHP Framework for Rapid Development**

EasyAPP is a powerful yet simple PHP framework designed to make web application development fast, secure, and enjoyable. Built with modern PHP practices and featuring a clean MVC architecture, advanced routing, and comprehensive tooling.

## Features

- **Modern MVC Architecture** - Clean separation of concerns with Controller, Model, View pattern
- **Advanced Routing System** - Support for RESTful routes, parameters, and middleware
- **Dependency Injection** - Built-in service container and registry pattern  
- **Database Abstraction** - PDO-based database layer with query builder
- **Caching System** - File-based caching with easy API
- **Event System** - Hook into framework lifecycle with events
- **CLI Tools** - Command-line interface for scaffolding and development
- **Error Handling** - Comprehensive exception handling with beautiful debug pages
- **Environment Configuration** - Support for .env files and multiple environments
- **Security Features** - CSRF protection, input sanitization, secure headers
- **Proxy Pattern** - AOP-style method interception and monitoring
- **Logging** - PSR-3 compatible logging with multiple levels

## Requirements

- PHP 7.4 or higher
- PDO extension
- mod_rewrite (for clean URLs)
- Composer (optional, for dependencies)

## Quick Start

### Installation

1. **Clone or download the framework:**
   ```bash
   git clone https://github.com/script-php/EasyAPP.git
   cd EasyAPP
   ```

2. **Set up your web server to point to the project directory**

3. **Configure your environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials and settings
   ```

4. **Create your first controller:**
   ```bash
   php easyapp make:controller Welcome
   ```

5. **Visit your application in the browser!**

### Basic Usage

**Create a Controller:**
```php
<?php
class ControllerWelcome extends Controller {
    public function index() {
        $data = [];
        $data['message'] = 'Welcome to EasyAPP!';
        
        $this->response->setOutput($this->load->view('welcome.html', $data));
    }
}
```

**Define Routes:**
```php
// app/router.php
$router->get('/', 'home');
$router->get('/welcome', 'welcome');
$router->post('/api/users', 'api/users|create');
```

**Create a Model:**
```php
<?php
class ModelUser extends Model {
    public function getUsers() {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }
}
```

## Directory Structure

```
├── app/                    # Application files
│   ├── controller/         # Controllers
│   ├── model/             # Models  
│   ├── view/              # View templates
│   ├── service/           # Business logic services
│   ├── language/          # Internationalization files
│   ├── config.php         # Application configuration
│   └── router.php         # Route definitions
├── system/                # Framework core files
│   ├── Framework/         # Core framework classes
│   ├── Library/           # Framework libraries
│   └── Vendor/            # Third-party libraries
├── storage/               # Storage directory
│   ├── cache/             # Cache files
│   ├── logs/              # Log files  
│   └── sessions/          # Session files
├── assets/                # Public assets
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── images/            # Images
├── tests/                 # Test files
├── .env                   # Environment configuration
├── index.php              # Application entry point
└── easyapp               # CLI tool
```

## CLI Commands

EasyAPP includes a powerful CLI tool for development:

```bash
# Generate files
php easyapp make:controller UserController
php easyapp make:model User  
php easyapp make:service UserService

# Development server
php easyapp serve localhost 8000

# Cache management
php easyapp clear:cache

# Help
php easyapp help
```

## Configuration

### Environment Variables (.env)
```env
# Application
APP_ENV=dev
DEBUG=true
APP_URL=http://localhost

# Database
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=easyapp
DB_USER=root
DB_PASS=
DB_PORT=3306

# Cache
CACHE_ENABLED=true
CACHE_DRIVER=file
CACHE_TTL=3600
```

### Application Configuration (app/config.php)
```php
<?php
$config['platform'] = 'My EasyAPP';
$config['debug'] = env('DEBUG', false);
$config['services'] = ['user', 'email'];
```

## Database Usage

```php
// In your model or controller
$sql = "SELECT * FROM users WHERE active = :active";
$query = $this->db->query($sql, [':active' => 1]);

// Get single row
$user = $query->row;

// Get all rows  
$users = $query->rows;

// Get count
$count = $query->num_rows;

// Transactions
$this->db->beginTransaction();
try {
    $this->db->query("INSERT INTO users (name) VALUES (:name)", [':name' => 'John']);
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

## Routing

### Basic Routes
```php
// GET route
$router->get('/users', 'users');
$router->get('/users/{id}', 'users|show');

// POST route  
$router->post('/users', 'users|create');

// Route with patterns
$router->pattern('id', '[0-9]+');
$router->get('/users/{id}', 'users|show');

// Fallback route
$router->fallback('not_found');
```

### Route Parameters
```php
// In your controller
public function show() {
    $id = $this->router->getParam('id');
    // or
    $id = $this->request->get('id');
}
```

## Views and Templates

```php
// In controller
$data = [];
$data['title'] = 'Welcome';
$data['users'] = $this->load->model('user')->getUsers();

$this->response->setOutput($this->load->view('users/index.html', $data));
```

```html
<!-- In view template -->
<html>
<head>
    <title><?php echo $title; ?></title>
</head>
<body>
    <?php foreach ($users as $user): ?>
        <p><?php echo $user['name']; ?></p>
    <?php endforeach; ?>
</body>
</html>
```

## Security Features

- **CSRF Protection**: Automatic CSRF token validation
- **Input Sanitization**: All input is sanitized by default
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Prevention**: HTML encoding of output
- **Secure Headers**: Configurable security headers

## Caching

```php
// Get from cache
$users = $this->cache->get('users');

if (!$users) {
    // Load from database
    $users = $this->load->model('user')->getUsers();
    
    // Cache for 1 hour
    $this->cache->set('users', $users, 3600);
}

// Remember pattern
$users = $this->cache->remember('users', function() {
    return $this->load->model('user')->getUsers();
}, 3600);
```

## Logging

```php
// In your application
$this->logger->info('User logged in', ['user_id' => 123]);
$this->logger->error('Database error', ['query' => $sql]);
$this->logger->debug('Debug information', $data);

// Log exceptions
try {
    // Some code
} catch (Exception $e) {
    $this->logger->exception($e);
    throw $e;
}
```

## Events System

```php
// Register event listener
$this->events->on('user.created', function($data) {
    // Send welcome email
    $this->load->service('email|sendWelcome', $data['user']);
});

// Trigger event
$this->events->trigger('user.created', ['user' => $user]);
```

## Testing

EasyAPP includes testing utilities and examples:

```php
// Basic test example
class UserTest extends TestCase {
    public function testUserCreation() {
        $user = $this->load->model('user');
        $result = $user->create(['name' => 'Test User']);
        
        $this->assertTrue($result > 0);
    }
}
```

## Documentation

- [CLI Guide](CLI_GUIDE.md) - Unified command-line interface
- [Migration Guide](MIGRATION_GUIDE.md) - Database migration system
- [Getting Started Guide](DOCUMENTATION.md) - Framework basics
- [Contributing](CONTRIBUTING.md)

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

EasyAPP Framework is open-source software licensed under the [GPL v3 License](LICENSE).

## Credits

Created with ❤️ by [YoYo](https://script-php.ro)

---

**Ready to build amazing applications? [Get started now!](#quick-start)**
