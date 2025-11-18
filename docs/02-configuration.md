# Configuration

EasyAPP uses a simple configuration system based on PHP constants. This guide covers all configuration options and best practices.

---

## Table of Contents

1. [Configuration Files](#configuration-files)
2. [Application Configuration](#application-configuration)
3. [Database Configuration](#database-configuration)
4. [Environment Variables](#environment-variables)
5. [Custom Configuration](#custom-configuration)
6. [Best Practices](#best-practices)

---

## Configuration Files

EasyAPP uses two main configuration files:

### Root Configuration

**File:** `config.php` (in the root directory)

This is the main configuration file loaded by the framework.

```php
<?php

// Application settings
define('CONFIG_APP_NAME', 'My Application');
define('CONFIG_APP_URL', 'http://localhost');
define('CONFIG_APP_ENV', 'development'); // development, production

// Database settings
define('CONFIG_DB_DRIVER', 'mysql');
define('CONFIG_DB_HOSTNAME', 'localhost');
define('CONFIG_DB_USERNAME', 'root');
define('CONFIG_DB_PASSWORD', '');
define('CONFIG_DB_DATABASE', 'myapp');
define('CONFIG_DB_PORT', '3306');
define('CONFIG_DB_PREFIX', '');

// Session settings
define('CONFIG_SESSION_NAME', 'MYAPP_SESSION');
define('CONFIG_SESSION_LIFETIME', 3600); // 1 hour in seconds

// Language settings
define('CONFIG_LANGUAGE', 'en-gb');

// Security settings
define('CONFIG_CSRF_ENABLED', true);
define('CONFIG_CSRF_TOKEN_NAME', 'csrf_token');

// Error reporting
define('CONFIG_ERROR_DISPLAY', true);
define('CONFIG_ERROR_LOG', true);

// Cache settings
define('CONFIG_CACHE_ENABLED', true);
define('CONFIG_CACHE_DRIVER', 'file'); // file, memcache, redis
define('CONFIG_CACHE_LIFETIME', 3600);

// Email settings
define('CONFIG_MAIL_DRIVER', 'mail'); // mail, smtp
define('CONFIG_MAIL_FROM', 'noreply@example.com');
define('CONFIG_MAIL_FROM_NAME', 'My Application');

// SMTP settings (if using SMTP)
define('CONFIG_SMTP_HOST', 'smtp.example.com');
define('CONFIG_SMTP_PORT', 587);
define('CONFIG_SMTP_USERNAME', '');
define('CONFIG_SMTP_PASSWORD', '');
define('CONFIG_SMTP_ENCRYPTION', 'tls'); // tls, ssl
```

### Application Configuration

**File:** `app/config.php`

Application-specific configuration and custom constants.

```php
<?php

// Custom application constants
define('CONFIG_UPLOAD_MAX_SIZE', 5242880); // 5MB
define('CONFIG_UPLOAD_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf');
define('CONFIG_ITEMS_PER_PAGE', 20);

// API keys
define('CONFIG_PAYMENT_API_KEY', 'your_payment_api_key');
define('CONFIG_WEATHER_API_KEY', 'your_weather_api_key');

// Feature flags
define('CONFIG_FEATURE_REGISTRATION', true);
define('CONFIG_FEATURE_COMMENTS', true);
define('CONFIG_FEATURE_RATINGS', false);

// Business logic
define('CONFIG_MIN_PASSWORD_LENGTH', 8);
define('CONFIG_MAX_LOGIN_ATTEMPTS', 5);
define('CONFIG_PASSWORD_RESET_EXPIRY', 86400); // 24 hours
```

---

## Application Configuration

### Basic Settings

```php
// Application name (used in emails, page titles, etc.)
define('CONFIG_APP_NAME', 'My Application');

// Application URL (used for generating links)
define('CONFIG_APP_URL', 'http://localhost');

// Environment: development, staging, production
define('CONFIG_APP_ENV', 'development');
```

**Usage in code:**

```php
// In controller
$data['app_name'] = CONFIG_APP_NAME;

// In view
<title><?php echo CONFIG_APP_NAME; ?></title>

// In email
$subject = 'Welcome to ' . CONFIG_APP_NAME;

// Generate URL
$resetLink = CONFIG_APP_URL . '/reset-password?token=' . $token;
```

### Error Reporting

```php
// Display errors on screen
define('CONFIG_ERROR_DISPLAY', true);

// Log errors to file
define('CONFIG_ERROR_LOG', true);

// Error log file (if CONFIG_ERROR_LOG is true)
define('CONFIG_ERROR_LOG_FILE', 'storage/logs/error.log');
```

**Recommended settings:**

```php
// Development
define('CONFIG_APP_ENV', 'development');
define('CONFIG_ERROR_DISPLAY', true);
define('CONFIG_ERROR_LOG', true);

// Production
define('CONFIG_APP_ENV', 'production');
define('CONFIG_ERROR_DISPLAY', false);
define('CONFIG_ERROR_LOG', true);
```

---

## Database Configuration

### Connection Settings

```php
// Database driver (mysql, pgsql, sqlite)
define('CONFIG_DB_DRIVER', 'mysql');

// Database host
define('CONFIG_DB_HOSTNAME', 'localhost');

// Database username
define('CONFIG_DB_USERNAME', 'root');

// Database password
define('CONFIG_DB_PASSWORD', 'secret');

// Database name
define('CONFIG_DB_DATABASE', 'myapp');

// Database port
define('CONFIG_DB_PORT', '3306');

// Table prefix (optional)
define('CONFIG_DB_PREFIX', 'app_');
```

### Multiple Database Connections

You can define multiple database connections:

```php
// Primary database
define('CONFIG_DB_HOSTNAME', 'localhost');
define('CONFIG_DB_USERNAME', 'root');
define('CONFIG_DB_PASSWORD', 'secret');
define('CONFIG_DB_DATABASE', 'myapp');

// Secondary database (analytics)
define('CONFIG_DB_ANALYTICS_HOSTNAME', 'analytics-server');
define('CONFIG_DB_ANALYTICS_USERNAME', 'analytics');
define('CONFIG_DB_ANALYTICS_PASSWORD', 'secret');
define('CONFIG_DB_ANALYTICS_DATABASE', 'analytics');
```

**Usage:**

```php
// Default connection
$this->db->query("SELECT * FROM users");

// Custom connection
$analyticsDb = new System\Framework\Db([
    'hostname' => CONFIG_DB_ANALYTICS_HOSTNAME,
    'username' => CONFIG_DB_ANALYTICS_USERNAME,
    'password' => CONFIG_DB_ANALYTICS_PASSWORD,
    'database' => CONFIG_DB_ANALYTICS_DATABASE,
]);
$analyticsDb->query("SELECT * FROM events");
```

### Database Charset and Collation

```php
define('CONFIG_DB_CHARSET', 'utf8mb4');
define('CONFIG_DB_COLLATION', 'utf8mb4_unicode_ci');
```

---

## Environment Variables

### Using .env Files

Create a `.env` file in the root directory:

```env
APP_NAME="My Application"
APP_URL=http://localhost
APP_ENV=development

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret

SESSION_LIFETIME=3600

CACHE_ENABLED=true
CACHE_DRIVER=file

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM=noreply@example.com

PAYMENT_API_KEY=your_payment_api_key
```

### Loading Environment Variables

**File:** `config.php`

```php
<?php

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $envReader = new System\Framework\EnvReader(__DIR__ . '/.env');
    $envReader->load();
}

// Use environment variables with fallback defaults
define('CONFIG_APP_NAME', getenv('APP_NAME') ?: 'My Application');
define('CONFIG_APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('CONFIG_APP_ENV', getenv('APP_ENV') ?: 'development');

define('CONFIG_DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
define('CONFIG_DB_HOSTNAME', getenv('DB_HOST') ?: 'localhost');
define('CONFIG_DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('CONFIG_DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('CONFIG_DB_DATABASE', getenv('DB_DATABASE') ?: 'myapp');
define('CONFIG_DB_PORT', getenv('DB_PORT') ?: '3306');

define('CONFIG_SESSION_LIFETIME', (int)getenv('SESSION_LIFETIME') ?: 3600);

define('CONFIG_CACHE_ENABLED', getenv('CACHE_ENABLED') === 'true');
define('CONFIG_CACHE_DRIVER', getenv('CACHE_DRIVER') ?: 'file');

define('CONFIG_MAIL_DRIVER', getenv('MAIL_DRIVER') ?: 'mail');
define('CONFIG_MAIL_HOST', getenv('MAIL_HOST') ?: '');
define('CONFIG_MAIL_PORT', (int)getenv('MAIL_PORT') ?: 587);
define('CONFIG_MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('CONFIG_MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('CONFIG_MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@example.com');

define('CONFIG_PAYMENT_API_KEY', getenv('PAYMENT_API_KEY') ?: '');
```

### Security Note

Always add `.env` to your `.gitignore`:

```
.env
.env.local
.env.production
```

Create a `.env.example` file to document required variables:

```env
APP_NAME=
APP_URL=
APP_ENV=development

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

PAYMENT_API_KEY=
```

---

## Custom Configuration

### Accessing Configuration Values

Configuration constants are available globally:

```php
// In controllers
public function index() {
    $appName = CONFIG_APP_NAME;
    $itemsPerPage = CONFIG_ITEMS_PER_PAGE;
}

// In models
public function getUsers() {
    $limit = CONFIG_ITEMS_PER_PAGE;
    return $this->db->query("SELECT * FROM users LIMIT ?", [$limit]);
}

// In views
<title><?php echo CONFIG_APP_NAME; ?></title>
```

### Creating Custom Configuration Groups

You can organize related configuration into groups:

**File:** `app/config.php`

```php
<?php

// Upload configuration
define('CONFIG_UPLOAD_PATH', 'storage/uploads/');
define('CONFIG_UPLOAD_MAX_SIZE', 5242880); // 5MB
define('CONFIG_UPLOAD_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf');
define('CONFIG_UPLOAD_IMAGE_MAX_WIDTH', 2000);
define('CONFIG_UPLOAD_IMAGE_MAX_HEIGHT', 2000);

// Pagination configuration
define('CONFIG_PAGINATION_ITEMS_PER_PAGE', 20);
define('CONFIG_PAGINATION_MAX_LINKS', 5);

// Security configuration
define('CONFIG_SECURITY_PASSWORD_MIN_LENGTH', 8);
define('CONFIG_SECURITY_PASSWORD_REQUIRE_UPPERCASE', true);
define('CONFIG_SECURITY_PASSWORD_REQUIRE_NUMBER', true);
define('CONFIG_SECURITY_PASSWORD_REQUIRE_SPECIAL', true);
define('CONFIG_SECURITY_MAX_LOGIN_ATTEMPTS', 5);
define('CONFIG_SECURITY_LOCKOUT_DURATION', 900); // 15 minutes

// API configuration
define('CONFIG_API_RATE_LIMIT', 100); // requests per hour
define('CONFIG_API_TOKEN_LIFETIME', 86400); // 24 hours
define('CONFIG_API_VERSION', 'v1');
```

### Configuration Classes

For more complex configuration, you can create configuration classes:

**File:** `app/config/UploadConfig.php`

```php
<?php

namespace App\Config;

class UploadConfig {
    
    const MAX_SIZE = 5242880; // 5MB
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    const PATH = 'storage/uploads/';
    
    public static function isAllowedExtension($extension) {
        return in_array(strtolower($extension), self::ALLOWED_EXTENSIONS);
    }
    
    public static function formatMaxSize() {
        return number_format(self::MAX_SIZE / 1024 / 1024, 2) . ' MB';
    }
}
```

**Usage:**

```php
use App\Config\UploadConfig;

if ($fileSize > UploadConfig::MAX_SIZE) {
    throw new Exception('File too large. Maximum: ' . UploadConfig::formatMaxSize());
}

if (!UploadConfig::isAllowedExtension($extension)) {
    throw new Exception('Invalid file type');
}
```

---

## Best Practices

### 1. Use Environment-Specific Configuration

```php
// Detect environment
$environment = getenv('APP_ENV') ?: 'production';

switch ($environment) {
    case 'development':
        define('CONFIG_ERROR_DISPLAY', true);
        define('CONFIG_CACHE_ENABLED', false);
        define('CONFIG_DB_HOSTNAME', 'localhost');
        break;
    
    case 'staging':
        define('CONFIG_ERROR_DISPLAY', false);
        define('CONFIG_CACHE_ENABLED', true);
        define('CONFIG_DB_HOSTNAME', 'staging-db.example.com');
        break;
    
    case 'production':
        define('CONFIG_ERROR_DISPLAY', false);
        define('CONFIG_CACHE_ENABLED', true);
        define('CONFIG_DB_HOSTNAME', 'prod-db.example.com');
        break;
}
```

### 2. Never Commit Sensitive Data

```php
// Bad: Hardcoded credentials
define('CONFIG_DB_PASSWORD', 'my_secret_password');
define('CONFIG_API_KEY', 'sk_live_abc123');

// Good: Use environment variables
define('CONFIG_DB_PASSWORD', getenv('DB_PASSWORD'));
define('CONFIG_API_KEY', getenv('API_KEY'));
```

### 3. Provide Default Values

```php
// Good: Fallback to sensible defaults
define('CONFIG_ITEMS_PER_PAGE', (int)getenv('ITEMS_PER_PAGE') ?: 20);
define('CONFIG_CACHE_ENABLED', getenv('CACHE_ENABLED') === 'true' ?: true);
define('CONFIG_SESSION_LIFETIME', (int)getenv('SESSION_LIFETIME') ?: 3600);
```

### 4. Use Descriptive Names

```php
// Good: Clear and descriptive
define('CONFIG_PASSWORD_RESET_TOKEN_EXPIRY', 86400);
define('CONFIG_MAX_UPLOAD_FILE_SIZE', 5242880);

// Bad: Unclear abbreviations
define('CONFIG_PRT_EXP', 86400);
define('CONFIG_MAX_UP_SIZE', 5242880);
```

### 5. Group Related Configuration

```php
// Upload settings
define('CONFIG_UPLOAD_PATH', 'storage/uploads/');
define('CONFIG_UPLOAD_MAX_SIZE', 5242880);
define('CONFIG_UPLOAD_ALLOWED_TYPES', 'jpg,png,gif');

// Email settings
define('CONFIG_MAIL_DRIVER', 'smtp');
define('CONFIG_MAIL_HOST', 'smtp.example.com');
define('CONFIG_MAIL_PORT', 587);

// Session settings
define('CONFIG_SESSION_NAME', 'APP_SESSION');
define('CONFIG_SESSION_LIFETIME', 3600);
define('CONFIG_SESSION_SECURE', false);
```

### 6. Document Configuration Options

```php
/**
 * Application Configuration
 */

// Application name displayed in UI and emails
define('CONFIG_APP_NAME', 'My Application');

// Base URL of the application (no trailing slash)
define('CONFIG_APP_URL', 'https://example.com');

// Environment: development, staging, production
// Affects error display and caching behavior
define('CONFIG_APP_ENV', 'production');

/**
 * Database Configuration
 */

// Database driver: mysql, pgsql, sqlite
define('CONFIG_DB_DRIVER', 'mysql');

// Database host address
define('CONFIG_DB_HOSTNAME', 'localhost');

// Database connection port
define('CONFIG_DB_PORT', '3306');
```

### 7. Validate Configuration

Create a configuration validator:

**File:** `system/Framework/ConfigValidator.php`

```php
<?php

namespace System\Framework;

class ConfigValidator {
    
    public static function validate() {
        $required = [
            'CONFIG_APP_NAME',
            'CONFIG_APP_URL',
            'CONFIG_DB_HOSTNAME',
            'CONFIG_DB_USERNAME',
            'CONFIG_DB_DATABASE',
        ];
        
        $missing = [];
        foreach ($required as $constant) {
            if (!defined($constant)) {
                $missing[] = $constant;
            }
        }
        
        if (!empty($missing)) {
            throw new \Exception('Missing required configuration: ' . implode(', ', $missing));
        }
        
        // Validate database connection
        if (defined('CONFIG_DB_HOSTNAME') && defined('CONFIG_DB_USERNAME')) {
            // Test connection
        }
        
        return true;
    }
}
```

### 8. Cache Configuration in Production

```php
// Generate config cache file
public static function cache() {
    $config = [];
    
    $constants = get_defined_constants(true);
    foreach ($constants['user'] as $name => $value) {
        if (strpos($name, 'CONFIG_') === 0) {
            $config[$name] = $value;
        }
    }
    
    file_put_contents(
        'storage/cache/config.php',
        '<?php return ' . var_export($config, true) . ';'
    );
}

// Load cached config
if (file_exists('storage/cache/config.php')) {
    $config = require 'storage/cache/config.php';
    foreach ($config as $name => $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}
```

---

## Configuration Examples

### Development Configuration

```php
<?php

define('CONFIG_APP_NAME', 'My App (Dev)');
define('CONFIG_APP_URL', 'http://localhost:8000');
define('CONFIG_APP_ENV', 'development');

define('CONFIG_DB_HOSTNAME', 'localhost');
define('CONFIG_DB_USERNAME', 'root');
define('CONFIG_DB_PASSWORD', '');
define('CONFIG_DB_DATABASE', 'myapp_dev');

define('CONFIG_ERROR_DISPLAY', true);
define('CONFIG_ERROR_LOG', true);

define('CONFIG_CACHE_ENABLED', false);

define('CONFIG_MAIL_DRIVER', 'log'); // Log emails instead of sending
```

### Production Configuration

```php
<?php

// Load environment variables
$envReader = new System\Framework\EnvReader(__DIR__ . '/.env');
$envReader->load();

define('CONFIG_APP_NAME', getenv('APP_NAME'));
define('CONFIG_APP_URL', getenv('APP_URL'));
define('CONFIG_APP_ENV', 'production');

define('CONFIG_DB_HOSTNAME', getenv('DB_HOST'));
define('CONFIG_DB_USERNAME', getenv('DB_USERNAME'));
define('CONFIG_DB_PASSWORD', getenv('DB_PASSWORD'));
define('CONFIG_DB_DATABASE', getenv('DB_DATABASE'));

define('CONFIG_ERROR_DISPLAY', false);
define('CONFIG_ERROR_LOG', true);

define('CONFIG_CACHE_ENABLED', true);
define('CONFIG_CACHE_DRIVER', 'redis');

define('CONFIG_MAIL_DRIVER', 'smtp');
define('CONFIG_MAIL_HOST', getenv('MAIL_HOST'));
define('CONFIG_MAIL_PORT', (int)getenv('MAIL_PORT'));
define('CONFIG_MAIL_USERNAME', getenv('MAIL_USERNAME'));
define('CONFIG_MAIL_PASSWORD', getenv('MAIL_PASSWORD'));
define('CONFIG_MAIL_ENCRYPTION', 'tls');

define('CONFIG_SESSION_SECURE', true); // HTTPS only
define('CONFIG_SESSION_HTTPONLY', true);
```

---

## Related Documentation

- **[Environment Variables](06-environment-variables.md)** - Detailed env configuration
- **[Database](17-database.md)** - Database setup and usage
- **[Security](24-security.md)** - Security configuration
- **[Deployment](27-deployment.md)** - Production configuration

---

**Previous:** [Getting Started](01-getting-started.md)  
**Next:** [Directory Structure](03-directory-structure.md)
