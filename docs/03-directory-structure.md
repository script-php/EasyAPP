# Directory Structure

Understanding the EasyAPP directory structure is essential for organizing your application effectively.

---

## Table of Contents

1. [Overview](#overview)
2. [Root Directory](#root-directory)
3. [Application Directory](#application-directory)
4. [System Directory](#system-directory)
5. [Storage Directory](#storage-directory)
6. [Assets Directory](#assets-directory)
7. [Migrations Directory](#migrations-directory)
8. [Tests Directory](#tests-directory)
9. [Best Practices](#best-practices)

---

## Overview

EasyAPP follows a clean separation between framework code, application code, and data storage:

```
your-project/
├── app/                    # Your application code
├── assets/                 # Public assets (CSS, JS, images)
├── docs/                   # Documentation
├── migrations/             # Database migrations
├── storage/                # Cache, logs, sessions, uploads
├── system/                 # Framework core (do not modify)
├── tests/                  # Test files
├── config.php              # Configuration file
├── index.php               # Application entry point
└── .htaccess               # Apache configuration
```

---

## Root Directory

### Entry Point

**`index.php`**

The main entry point that bootstraps the framework:

```php
<?php

// Load configuration
require_once('config.php');

// Load framework
require_once('system/Framework.php');

// Start application
$framework = new System\Framework();
$framework->start();
```

### Configuration

**`config.php`**

Main configuration file with database, session, and application settings.

**`app/config.php`**

Application-specific configuration and custom constants.

### Web Server Configuration

**`.htaccess`** (Apache)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [L,QSA]
```

**`nginx.conf`** (Nginx)

```nginx
location / {
    try_files $uri $uri/ /index.php?route=$uri&$args;
}
```

### Composer

**`composer.json`**

Dependency management and autoloading configuration.

### Environment

**`.env`**

Environment-specific configuration (not committed to version control).

**`.env.example`**

Template for required environment variables.

### Version Control

**`.gitignore`**

```
.env
storage/cache/*
storage/logs/*
storage/sessions/*
storage/uploads/*
vendor/
```

---

## Application Directory

The `app/` directory contains all your application-specific code.

### Structure

```
app/
├── config.php              # Application configuration
├── helper.php              # Global helper functions
├── router.php              # Route definitions
├── controller/             # Controllers
│   ├── home.php
│   ├── user.php
│   └── product.php
├── model/                  # Models
│   ├── User.php
│   ├── Product.php
│   └── common/
│       └── base.php
├── view/                   # Views/Templates
│   ├── base.html
│   ├── home/
│   │   └── index.html
│   └── user/
│       ├── list.html
│       └── form.html
├── service/                # Services (business logic)
│   ├── EmailService.php
│   └── PaymentService.php
├── library/                # Custom libraries
│   ├── Upload.php
│   └── Pagination.php
└── language/               # Translations
    ├── en-gb/
    │   ├── common.php
    │   └── home.php
    └── fr-fr/
        ├── common.php
        └── home.php
```

### Controllers

**Location:** `app/controller/`

Controllers handle HTTP requests and coordinate responses.

**Naming:** `[name].php` → `Controller[Name]`

```
app/controller/
├── home.php                # ControllerHome
├── user.php                # ControllerUser
├── product.php             # ControllerProduct
└── api/
    └── user.php            # ControllerApiUser
```

### Models

**Location:** `app/model/`

Models handle data access and business logic.

**Naming:** `[Name].php` → `Model[Name]`

```
app/model/
├── User.php                # ModelUser (ORM)
├── Product.php             # ModelProduct (ORM)
├── home.php                # Modelhome (traditional)
├── user.php                # Modeluser (traditional)
└── common/
    └── base.php            # ModelCommonBase
```

### Views

**Location:** `app/view/`

Views contain presentation templates.

**Naming:** `[path]/[name].html`

```
app/view/
├── base.html               # Base layout
├── home/
│   ├── index.html
│   └── about.html
├── user/
│   ├── list.html
│   ├── view.html
│   └── form.html
└── partials/
    ├── header.html
    ├── footer.html
    └── sidebar.html
```

### Services

**Location:** `app/service/`

Services encapsulate business logic and external integrations.

**Naming:** `[Name]Service.php` → `Service[Name]Service`

```
app/service/
├── EmailService.php        # ServiceEmailService
├── PaymentService.php      # ServicePaymentService
├── UserService.php         # ServiceUserService
└── ReportService.php       # ServiceReportService
```

### Libraries

**Location:** `app/library/`

Custom utility classes and third-party wrappers.

**Naming:** `[Name].php` → `Library[Name]`

```
app/library/
├── Upload.php              # LibraryUpload
├── Pagination.php          # LibraryPagination
├── Pdf.php                 # LibraryPdf
└── ImageProcessor.php      # LibraryImageProcessor
```

### Language Files

**Location:** `app/language/[locale]/`

Translation files organized by locale.

**Structure:**

```
app/language/
├── en-gb/                  # English (Great Britain)
│   ├── common.php          # Common translations
│   ├── home.php            # Home module
│   ├── user.php            # User module
│   └── error.php           # Error messages
├── fr-fr/                  # French (France)
│   ├── common.php
│   ├── home.php
│   └── user.php
└── ro-ro/                  # Romanian (Romania)
    ├── common.php
    ├── home.php
    └── user.php
```

### Router

**File:** `app/router.php`

Define custom routes:

```php
<?php

// Custom routes
$this->router->add('/', 'home/index');
$this->router->add('/about', 'home/about');
$this->router->add('/user/{id:\d+}', 'user/view');
$this->router->add('/api/users', 'api/user/list');
```

### Helper

**File:** `app/helper.php`

Global helper functions:

```php
<?php

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
```

---

## System Directory

The `system/` directory contains the framework core. **Do not modify these files.**

### Structure

```
system/
├── Autoloader.php          # PSR-4 autoloader
├── Framework.php           # Framework bootstrap
├── Controller.php          # Base controller class
├── Model.php               # Base model class
├── Service.php             # Base service class
├── Library.php             # Base library class
├── TestCase.php            # Base test case class
├── Cli.php                 # CLI handler
├── Framework/              # Core components
│   ├── Db.php              # Database layer
│   ├── Request.php         # HTTP request
│   ├── Response.php        # HTTP response
│   ├── Router.php          # Routing system
│   ├── Load.php            # Resource loader
│   ├── Language.php        # Language system
│   ├── Cache.php           # Caching
│   ├── Logger.php          # Logging
│   ├── Mail.php            # Email
│   ├── Orm.php             # Active Record ORM
│   ├── Collection.php      # Collection class
│   ├── Validator.php       # Validation
│   ├── Csrf.php            # CSRF protection
│   ├── Event.php           # Event system
│   ├── Migration.php       # Migration base
│   └── Exceptions/         # Framework exceptions
│       ├── FrameworkException.php
│       ├── DatabaseException.php
│       └── ControllerNotFound.php
├── Library/                # Built-in libraries
└── Vendor/                 # Composer dependencies
    └── autoload.php
```

### Core Components

**Base Classes:**
- `Controller.php` - Base controller
- `Model.php` - Base model
- `Service.php` - Base service
- `Library.php` - Base library

**Framework Classes:**
- `Framework/Db.php` - Database operations
- `Framework/Request.php` - HTTP requests
- `Framework/Response.php` - HTTP responses
- `Framework/Router.php` - URL routing
- `Framework/Orm.php` - Active Record ORM
- `Framework/Cache.php` - Caching layer
- `Framework/Logger.php` - PSR-3 logging

---

## Storage Directory

The `storage/` directory holds generated files and user uploads.

### Structure

```
storage/
├── cache/                  # Application cache
│   ├── routes/
│   └── views/
├── logs/                   # Application logs
│   ├── error.log
│   └── access.log
├── sessions/               # Session files
└── uploads/                # User uploads
    ├── images/
    ├── documents/
    └── temp/
```

### Permissions

These directories must be writable by the web server:

```bash
chmod -R 755 storage/
chmod -R 777 storage/cache/
chmod -R 777 storage/logs/
chmod -R 777 storage/sessions/
chmod -R 777 storage/uploads/
```

### Cache

**Location:** `storage/cache/`

Stores cached views, routes, and application data.

```
storage/cache/
├── routes/
│   └── routes.php
├── views/
│   └── compiled/
└── data/
    └── *.cache
```

### Logs

**Location:** `storage/logs/`

Application and error logs.

```
storage/logs/
├── error.log               # PHP errors
├── application.log         # Application logs
├── query.log               # Database queries (dev mode)
└── access.log              # Access logs
```

### Sessions

**Location:** `storage/sessions/`

PHP session files when using file-based sessions.

### Uploads

**Location:** `storage/uploads/`

User-uploaded files organized by type or date.

```
storage/uploads/
├── images/
│   ├── 2024/
│   │   ├── 01/
│   │   └── 02/
│   └── avatars/
├── documents/
│   └── pdfs/
└── temp/                   # Temporary uploads
```

---

## Assets Directory

The `assets/` directory contains public static files.

### Structure

```
assets/
├── app/                    # Application assets
│   ├── images/
│   │   ├── logo.png
│   │   └── icons/
│   ├── javascript/
│   │   ├── app.js
│   │   └── modules/
│   └── stylesheet/
│       ├── app.css
│       └── themes/
└── vendor/                 # Third-party assets
    ├── bootstrap/
    ├── jquery/
    └── fontawesome/
```

### Organization

**Images:** `assets/app/images/`
```
images/
├── logo.png
├── favicon.ico
├── backgrounds/
├── icons/
└── products/
```

**JavaScript:** `assets/app/javascript/`
```
javascript/
├── app.js                  # Main application JS
├── modules/
│   ├── auth.js
│   └── product.js
└── vendor/
    └── third-party-lib.js
```

**Stylesheets:** `assets/app/stylesheet/`
```
stylesheet/
├── app.css                 # Main stylesheet
├── themes/
│   ├── light.css
│   └── dark.css
├── components/
│   ├── buttons.css
│   └── forms.css
└── vendor/
    └── third-party.css
```

---

## Migrations Directory

The `migrations/` directory contains database migration files.

### Structure

```
migrations/
├── 001_create_users_table.php
├── 002_create_products_table.php
├── 003_add_email_to_users.php
└── 004_create_orders_table.php
```

### Migration File Format

**Naming:** `[number]_[description].php`

```php
<?php

use System\Framework\Migration;

class Migration001CreateUsersTable extends Migration {
    
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(255) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $this->db->query($sql);
    }
    
    public function down() {
        $this->db->query("DROP TABLE IF EXISTS `users`");
    }
}
```

---

## Tests Directory

The `tests/` directory contains test files.

### Structure

```
tests/
├── OrmTest.php             # ORM tests
├── OrmFeatureTest.php      # ORM feature tests
├── OrmRelationshipsTest.php # Relationship tests
├── SystemIntegrationTest.php # Integration tests
└── Unit/                   # Unit tests
    ├── ModelTest.php
    ├── ControllerTest.php
    └── ServiceTest.php
```

### Test Organization

```
tests/
├── Unit/                   # Unit tests
│   ├── Model/
│   │   ├── UserTest.php
│   │   └── ProductTest.php
│   ├── Service/
│   │   └── EmailServiceTest.php
│   └── Library/
│       └── UploadTest.php
├── Feature/                # Feature tests
│   ├── AuthenticationTest.php
│   └── ProductCrudTest.php
└── Integration/            # Integration tests
    └── ApiTest.php
```

---

## Best Practices

### 1. Keep Application Code in `app/`

```
Good:
app/controller/user.php
app/model/User.php
app/service/EmailService.php

Bad:
system/controller/user.php  # Never modify system/
```

### 2. Organize by Feature

```
app/
├── controller/
│   ├── user/
│   │   ├── profile.php
│   │   ├── settings.php
│   │   └── authentication.php
│   └── product/
│       ├── catalog.php
│       └── admin.php
├── model/
│   ├── user/
│   │   ├── User.php
│   │   └── UserProfile.php
│   └── product/
│       ├── Product.php
│       └── Category.php
```

### 3. Use Subdirectories for Large Applications

```
app/view/
├── layouts/
│   ├── main.html
│   ├── admin.html
│   └── auth.html
├── partials/
│   ├── header.html
│   ├── footer.html
│   └── sidebar.html
├── user/
│   ├── profile/
│   │   ├── view.html
│   │   └── edit.html
│   └── settings/
│       ├── account.html
│       └── privacy.html
```

### 4. Separate Public and Storage Files

```
✓ Public files:   assets/app/images/logo.png
✗ User uploads:   assets/app/uploads/avatar.jpg

✓ User uploads:   storage/uploads/avatars/user123.jpg
✗ Public files:   storage/images/logo.png
```

### 5. Keep Storage Directory Clean

```bash
# Clean cache regularly
rm -rf storage/cache/*

# Rotate logs
mv storage/logs/error.log storage/logs/error-$(date +%Y%m%d).log

# Clean old sessions
find storage/sessions/ -type f -mtime +1 -delete
```

### 6. Version Control Exclusions

**`.gitignore`:**

```
# Environment
.env
.env.local

# Storage (except .gitkeep)
storage/cache/*
!storage/cache/.gitkeep
storage/logs/*
!storage/logs/.gitkeep
storage/sessions/*
!storage/sessions/.gitkeep
storage/uploads/*
!storage/uploads/.gitkeep

# Dependencies
vendor/
node_modules/

# IDE
.vscode/
.idea/
*.swp
```

### 7. Maintain Directory Structure

Create `.gitkeep` files to maintain empty directories:

```bash
touch storage/cache/.gitkeep
touch storage/logs/.gitkeep
touch storage/sessions/.gitkeep
touch storage/uploads/.gitkeep
```

---

## Related Documentation

- **[Getting Started](01-getting-started.md)** - Installation and setup
- **[Configuration](02-configuration.md)** - Configuration files
- **[Architecture](04-architecture.md)** - Application architecture
- **[Controllers](07-controllers.md)** - Controller organization
- **[Models](08-models-traditional.md)** - Model organization

---

**Previous:** [Configuration](02-configuration.md)  
**Next:** [Architecture](04-architecture.md)
