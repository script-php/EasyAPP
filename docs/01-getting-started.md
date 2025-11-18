# Getting Started with EasyAPP

This guide will help you install and configure EasyAPP Framework for the first time.

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Installation Methods](#installation-methods)
3. [Initial Configuration](#initial-configuration)
4. [Directory Permissions](#directory-permissions)
5. [Web Server Configuration](#web-server-configuration)
6. [Verification](#verification)
7. [Creating Your First Application](#creating-your-first-application)

---

## System Requirements

### Minimum Requirements

- **PHP:** 7.4 or higher
- **Memory:** 64MB minimum (128MB recommended)
- **Web Server:** Apache 2.4+ or Nginx 1.10+
- **Database:** MySQL 5.7+ / MariaDB 10.2+ / PostgreSQL 9.6+ / SQLite 3

### Required PHP Extensions

- **PDO** - Database abstraction (required for database features)
- **JSON** - JSON processing
- **Session** - Session management
- **MBString** - Multi-byte string handling (recommended)

### Optional Extensions

- **GD** or **ImageMagick** - Image processing
- **cURL** - HTTP requests
- **OpenSSL** - Encryption features

---

## Installation Methods

### Method 1: Direct Download

1. Download the latest release from GitHub
2. Extract the archive to your web server directory
3. Navigate to the project root directory

```bash
# Example for Apache on Windows
cd C:\xampp\htdocs\your-project

# Example for Apache on Linux
cd /var/www/html/your-project
```

### Method 2: Git Clone

```bash
# Clone the repository
git clone https://github.com/script-php/EasyAPP.git your-project

# Navigate to project directory
cd your-project

# Optional: Checkout specific branch
git checkout dev-orm
```

### Method 3: Composer Create-Project

```bash
# Create new project using Composer
composer create-project script-php/easyapp your-project

# Navigate to project directory
cd your-project
```

---

## Initial Configuration

### Step 1: Environment Configuration

Copy the example environment file and configure it:

```bash
# Copy example file
cp .env.example .env
```

Edit `.env` file with your settings:

```ini
# Application Settings
APP_ENV=dev
DEBUG=true
APP_URL=http://localhost
APP_NAME=MyApplication

# Database Configuration
DB_DRIVER=mysql
DB_HOSTNAME=localhost
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_PORT=3306
DB_PREFIX=

# Cache Settings
CACHE_ENABLED=true
CACHE_DRIVER=file
CACHE_TTL=3600

# Security
CSRF_PROTECTION=true
INPUT_SANITIZATION=true

# Logging
LOG_LEVEL=error
LOG_FILE=storage/logs/error.log
```

### Step 2: Application Configuration

Edit `app/config.php` for application-specific settings:

```php
<?php

// Application Name
$config['platform'] = 'My Application';

// Debug Mode (override from .env if needed)
$config['debug'] = env('DEBUG', false);

// Default Language
$config['default_language'] = 'en-gb';

// Timezone
$config['timezone'] = 'UTC';

// Services to auto-load on startup
$config['services'] = [
    // 'service_name',
    // 'service_name|method',
];
```

---

## Directory Permissions

Set appropriate permissions for storage directories:

### Unix/Linux/macOS

```bash
# Make storage directories writable
chmod -R 755 storage/
chmod -R 755 storage/cache/
chmod -R 755 storage/logs/
chmod -R 755 storage/sessions/
chmod -R 755 storage/uploads/

# If using web server user (recommended)
chown -R www-data:www-data storage/
```

### Windows

1. Right-click on `storage` folder
2. Select Properties > Security
3. Ensure IIS_IUSRS or IUSR has write permissions

---

## Web Server Configuration

### Apache Configuration

**Option 1: Using .htaccess (included in framework)**

The framework includes `.htaccess` file in the root directory. Ensure `mod_rewrite` is enabled:

```apache
# Enable mod_rewrite
LoadModule rewrite_module modules/mod_rewrite.so

# Ensure AllowOverride is set
<Directory "/path/to/your/project">
    AllowOverride All
</Directory>
```

**Option 2: Virtual Host Configuration**

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot "/path/to/your/project"
    
    <Directory "/path/to/your/project">
        AllowOverride All
        Require all granted
        
        # Enable rewrite engine
        RewriteEngine On
        
        # Redirect to index.php
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?route=$1 [L,QSA]
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/myapp-error.log
    CustomLog ${APACHE_LOG_DIR}/myapp-access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /path/to/your/project;
    index index.php;

    # Logging
    access_log /var/log/nginx/myapp-access.log;
    error_log /var/log/nginx/myapp-error.log;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires max;
        log_not_found off;
    }
}
```

### Development Server

For development purposes, use PHP's built-in server:

```bash
# Start development server
php easy serve

# Or specify host and port
php easy serve --host=localhost --port=8080
```

Then access your application at: `http://localhost:8000`

---

## Verification

### Step 1: Check Installation

Visit your application URL in a web browser. You should see the default EasyAPP welcome page.

### Step 2: Check PHP Configuration

Create a temporary file `info.php` in the project root:

```php
<?php
phpinfo();
```

Visit `http://your-domain/info.php` to verify:
- PHP version is 7.4 or higher
- Required extensions are loaded
- Configuration settings are correct

**Important:** Delete `info.php` after verification for security.

### Step 3: Check Database Connection

If using a database, verify the connection by checking the framework status:

```bash
php easy migrate:status
```

You should see database connection information without errors.

---

## Creating Your First Application

### Step 1: Create a Controller

Use the CLI to generate a controller:

```bash
php easy make:controller Welcome
```

This creates `app/controller/welcome.php`:

```php
<?php

class ControllerWelcome extends Controller {
    
    public function index() {
        $data = [];
        $data['title'] = 'Welcome';
        $data['message'] = 'Welcome to EasyAPP Framework!';
        
        $this->response->setOutput($this->load->view('welcome/index.html', $data));
    }
}
```

### Step 2: Create a View

Create `app/view/welcome/index.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
    </style>
</head>
<body>
    <h1><?php echo $title; ?></h1>
    <p><?php echo $message; ?></p>
    <p>Your EasyAPP installation is working correctly!</p>
</body>
</html>
```

### Step 3: Configure Routing

Edit `app/router.php`:

```php
<?php

// Define routes
$router->get('/', 'welcome');
$router->get('/welcome', 'welcome');

// Fallback for 404 errors
$router->fallback('not_found');
```

### Step 4: Test Your Application

Visit `http://your-domain/` or `http://your-domain/welcome` in your browser. You should see your welcome message.

---

## Next Steps

Now that you have EasyAPP installed and running, explore these topics:

1. **[Configuration](02-configuration.md)** - Learn about configuration options
2. **[Controllers](07-controllers.md)** - Create controllers to handle requests
3. **[Models](08-models-traditional.md)** - Work with data and business logic
4. **[Views](10-views.md)** - Create presentation templates
5. **[Routing](15-routing.md)** - Define URL patterns

---

## Troubleshooting

### Problem: Blank Page or 500 Error

**Solution:**
1. Enable debug mode in `.env`: `DEBUG=true`
2. Check `storage/logs/error.log` for errors
3. Verify PHP error reporting is enabled
4. Check web server error logs

### Problem: 404 Errors for All Pages

**Solution:**
1. Verify mod_rewrite is enabled (Apache)
2. Check .htaccess file exists and is readable
3. Verify web server configuration allows .htaccess override
4. For Nginx, check rewrite rules in server configuration

### Problem: Database Connection Fails

**Solution:**
1. Verify database credentials in `.env`
2. Ensure database server is running
3. Check database user has proper permissions
4. Verify PDO extension is loaded

### Problem: Permission Denied Errors

**Solution:**
1. Check storage directory permissions (755 or 775)
2. Ensure web server user can write to storage directories
3. On Linux/macOS, check file ownership

---

## Additional Resources

- **Framework Documentation:** [docs/README.md](README.md)
- **Example Applications:** Check `app/controller/` directory
- **Community Support:** GitHub Issues
- **Official Website:** https://script-php.ro

---

**Previous:** [Documentation Index](README.md)  
**Next:** [Configuration](02-configuration.md)
