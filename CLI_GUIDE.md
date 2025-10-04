# EasyPHP CLI Guide

## Unified Command Line Interface

The EasyPHP CLI tool provides a unified interface for all framework operations including migrations, code generation, testing, and development utilities.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Installation & Setup](#installation--setup)
3. [Command Structure](#command-structure)
4. [Migration Commands](#migration-commands)
5. [Generator Commands](#generator-commands)
6. [Test Commands](#test-commands)
7. [Utility Commands](#utility-commands)
8. [Development Server](#development-server)
9. [Command Reference](#command-reference)
10. [Advanced Usage](#advanced-usage)
11. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Quick Start

The EasyPHP CLI follows modern framework conventions with grouped commands and intuitive syntax:

```bash
# Show all available commands
php easyphp help

# Check migration status
php easyphp migrate:status

# Create a new controller
php easyphp make:controller UserController

# Run tests
php easyphp test

# Start development server
php easyphp serve
```

### Basic Syntax

```bash
php easyphp <command> [arguments] [options]
```

**Examples:**
```bash
php easyphp migrate                    # Run migrations
php easyphp migrate:create AddUsers    # Create migration
php easyphp make:controller User       # Generate controller
php easyphp test --unit               # Run unit tests
php easyphp serve --port=9000         # Start server on port 9000
```

---

## Installation & Setup

### Prerequisites

- **PHP 7.4+** with CLI support
- **EasyAPP Framework** installed
- **Database connection** configured in `config.php`
- **Command line access** to your project directory

### Verification

Check that the CLI tool is working:

```bash
# Navigate to your project root
cd /path/to/your/project

# Test CLI access
php easyphp --version

# Expected output:
# EasyPHP CLI Tool
# EasyAPP Framework v2.0
# Copyright (c) 2022, script-php.ro
```

---

## Command Structure

### Command Groups

Commands are organized into logical groups:

| Group | Purpose | Examples |
|-------|---------|----------|
| `migrate:*` | Database migrations | `migrate`, `migrate:status`, `migrate:rollback` |
| `make:*` | Code generation | `make:controller`, `make:model`, `make:service` |
| `test:*` | Testing framework | `test`, `test:unit`, `test:integration` |
| `cache:*` | Cache management | `cache:clear` |
| Standalone | Utilities | `serve`, `help` |

### Getting Help

```bash
# General help
php easyphp help

# Command-specific help
php easyphp migrate --help
php easyphp make:controller --help

# List all commands
php easyphp help
```

---

## Migration Commands

### Overview

Database migration commands provide complete schema management:

```bash
php easyphp migrate:status      # Check migration status
php easyphp migrate             # Run pending migrations  
php easyphp migrate:create      # Create new migration
php easyphp migrate:rollback    # Rollback migrations
```

### migrate:status

Show current migration state and history:

```bash
php easyphp migrate:status
```

**Output:**
```
Migration Status
======================================================================
Database: my_app_db
Current Version: 3
Latest Version: 5
Total Migrations: 5
Applied: 3
Pending: 2

Version  Status      Description                           Applied At
--------------------------------------------------------------------------------
1        Applied     Create Initial User System            2024-01-01 10:00:00
2        Applied     Add Product System                    2024-01-02 11:30:00  
3        Applied     Add User Address Support              2024-01-03 09:15:00
4        Pending     Transform User Data Example           -
5        Pending     Add Order Management System           -
```

### migrate

Execute pending migrations:

```bash
# Run all pending migrations
php easyphp migrate

# Run to specific version
php easyphp migrate --to=5

# Preview changes without applying
php easyphp migrate --dry-run

# Run to specific version in dry-run mode
php easyphp migrate --to=3 --dry-run
```

**Example Output:**
```
Running Migrations
==================================================
[SUCCESS] Migration_004_TransformUserData (45ms)
   Transform User Data Example
[SUCCESS] Migration_005_AddOrderSystem (120ms)
   Add Order Management System

Summary:
  Executed: 2
  Errors: 0
  Total time: 165ms

All migrations completed successfully!
```

### migrate:create

Generate new migration files:

```bash
# Create migration with descriptive name
php easyphp migrate:create CreateUsersTable
php easyphp migrate:create AddEmailToUsers
php easyphp migrate:create UpdateProductPricing
```

**Output:**
```
Creating New Migration
==================================================
Created migration file: 006_add_email_verification.php
Path: /project/migrations/006_add_email_verification.php

Next steps:
1. Edit the migration file to implement up() and down() methods
2. Run 'php easyphp migrate' to apply the migration
```

### migrate:rollback

Revert migrations to previous state:

```bash
# Rollback to specific version
php easyphp migrate:rollback 3

# Preview rollback changes
php easyphp migrate:rollback 3 --dry-run
```

**Example Output:**
```
Rolling Back Migrations
==================================================
Rolling back to version: 3
[ROLLBACK] Migration_005_AddOrderSystem (89ms)
   Add Order Management System
[ROLLBACK] Migration_004_TransformUserData (34ms)
   Transform User Data Example

Summary:
  Rolled back: 2
  Errors: 0
  Total time: 123ms

Rollback completed successfully!
```

---

## Generator Commands

### Overview

Code generation commands create boilerplate files following framework conventions:

```bash
php easyphp make:controller     # Generate controller class
php easyphp make:model         # Generate model class
php easyphp make:service       # Generate service class
php easyphp make:migration     # Alias for migrate:create
```

### make:controller

Generate controller files:

```bash
# Create basic controller
php easyphp make:controller UserController
php easyphp make:controller ProductController
php easyphp make:controller Auth

# Creates: app/controller/user.php
# Class: ControllerUser extends Controller
```

**Generated File Structure:**
```php
<?php

/**
 * ControllerUser
 * Generated by EasyPHP CLI
 */

class ControllerUser extends Controller {
    
    public function index() {
        // Controller logic here
        $this->load->view('user/index');
    }
    
}
```

### make:model

Generate model files:

```bash
# Create model classes
php easyphp make:model User
php easyphp make:model Product
php easyphp make:model Order

# Creates: app/model/user.php
# Class: ModelUser extends Model
```

**Generated File Structure:**
```php
<?php

/**
 * ModelUser
 * Generated by EasyPHP CLI
 */

class ModelUser extends Model {
    
    protected $table = 'user';
    
    public function __construct() {
        parent::__construct();
    }
    
}
```

### make:service

Generate service files:

```bash
# Create service classes
php easyphp make:service UserService
php easyphp make:service EmailService
php easyphp make:service PaymentService

# Creates: app/service/userservice.php
# Class: UserService extends Service
```

**Generated File Structure:**
```php
<?php

/**
 * UserService
 * Generated by EasyPHP CLI
 */

class UserService extends Service {
    
    public function __construct() {
        parent::__construct();
    }
    
}
```

---

## Test Commands

### Overview

Testing commands provide comprehensive test execution:

```bash
php easyphp test              # Run all tests
php easyphp test:unit         # Run unit tests only
php easyphp test:integration  # Run integration tests only
```

### test

Execute complete test suite (all unit and integration tests):

```bash
# Run all available tests
php easyphp test
```

**Example Output:**
```
Running All Tests
==================================================
Found 5 test file(s)

Running: UserValidationUnitTest.php [PASSED]
Running: UserSystemIntegrationTest.php [PASSED]
Running: SecurityFixesTest.php [PASSED]

==================================================
Test Results Summary
==================================================
Total Tests: 5
Passed: 5
Failed: 0

✅ All tests passed!

===================================================
All Tests Summary:
===================================================
Total Tests: 5
Passed: 5
Failed: 0

All All Tests completed successfully!
```

### test:unit

Execute only unit tests (fast, isolated component testing):

```bash
# Run only unit tests
php easyphp test:unit
```

**What Unit Tests Do:**
- Test individual components in isolation
- Mock external dependencies (no database calls)
- Execute very quickly (milliseconds per test)
- Focus on pure logic validation
- Ideal for TDD and rapid feedback during development

**Example Output:**
```
Running Unit Tests
===================================================
Unit tests focus on individual components in isolation
- Fast execution (milliseconds)
- No external dependencies (database, APIs)
- Pure logic validation

Running Unit Tests
==================================================
Found 1 test file(s)

Running: UserValidationUnitTest.php
==================================================
Running tests for UserValidationUnitTest
==================================================
✓ testEmailValidation
✓ testPasswordStrength
✓ testUsernameValidation
✓ testDataSanitization
✓ testArrayOperations

Results: 5/5 tests passed
[PASSED]

===================================================
Unit Tests Summary:
===================================================
Total Tests: 1
Passed: 1
Failed: 0

All Unit Tests completed successfully!
```

### test:integration

Execute only integration tests (slower, full system testing):

```bash
# Run only integration tests
php easyphp test:integration
```

**What Integration Tests Do:**
- Test component interaction with real dependencies
- Use actual database connections and file system
- Execute slower (seconds per test) due to I/O operations
- Verify end-to-end workflows and data persistence
- Essential for deployment validation and system verification

**Example Output:**
```
Running Integration Tests
===================================================
Integration tests verify component interaction
- Slower execution (seconds)
- Uses real dependencies (database, files)
- End-to-end workflow validation

Running Integration Tests
==================================================
Found 4 test file(s)

Running: ComplexRelationshipTest.php [PASSED]
Running: SecurityFixesTest.php [PASSED]
Running: UserSystemIntegrationTest.php [PASSED]

===================================================
Integration Tests Summary:
===================================================
Total Tests: 4
Passed: 4
Failed: 0

All Integration Tests completed successfully!
```

### Test Organization

The **TestRunner** is a core framework component located in `system/TestRunner.php` that automatically categorizes tests based on:

**Unit Test Detection:**
- Filename contains 'Unit' or 'unit'
- File content focuses on pure logic without database calls
- Uses only assertion methods without external dependencies

**Integration Test Detection:**
- Filename contains 'Integration' or 'integration' 
- File content includes database operations (`$this->db`)
- Uses `require_once` or framework initialization
- Tests real component interaction

**Framework Integration:**
- TestRunner and TestBootstrap are part of the core EasyAPP Framework
- TestBootstrap (`system/TestBootstrap.php`) - Environment setup and helper functions
- TestRunner (`system/TestRunner.php`) - Core test execution engine
- Integrates seamlessly with the framework registry and database connections
- Provides consistent testing API across all applications
- Safe from tests directory deletion or deployment exclusions

**Test File Examples:**
```
tests/
├── UserValidationUnitTest.php          # Unit test
├── UserSystemIntegrationTest.php       # Integration test
├── ComplexRelationshipTest.php         # Integration test (detected)
├── SecurityFixesTest.php               # Integration test (detected)
└── ExampleTest.php                     # Unit test (detected)
```

---

## Utility Commands

### cache:clear

Clear application cache:

```bash
php easyphp cache:clear
```

**Output:**
```
Clearing Cache
====================
Cleared 47 cache files
```

**What it clears:**
- All files in `storage/cache/` directory
- Compiled templates
- Cached configuration
- Session cache files

---

## Development Server

### serve

Start the built-in development server:

```bash
# Start server on default port (8000)
php easyphp serve

# Start on specific port
php easyphp serve --port=9000

# Start on specific host and port
php easyphp serve --host=0.0.0.0 --port=8080
```

**Default Configuration:**
- **Host:** `127.0.0.1`
- **Port:** `8000`
- **Document Root:** Project root directory

**Output:**
```
EasyAPP Development Server
==============================
Server starting at http://127.0.0.1:8000
Press Ctrl+C to stop
```

**Server Options:**
```bash
# Local development (default)
php easyphp serve

# Accept external connections
php easyphp serve --host=0.0.0.0

# Custom port
php easyphp serve --port=3000

# Specific configuration
php easyphp serve --host=192.168.1.100 --port=9000
```

---

## Command Reference

### Complete Command List

```bash
# Framework Generation Commands
php easyphp make:controller <name>     # Create controller
php easyphp make:model <name>          # Create model  
php easyphp make:service <name>        # Create service
php easyphp make:migration <name>      # Create migration

# Database Migration Commands  
php easyphp migrate                    # Run pending migrations
php easyphp migrate:status             # Show migration status
php easyphp migrate:rollback <version> # Rollback to version
php easyphp migrate:create <name>      # Create migration file

# Test Commands
php easyphp test                       # Run all tests
php easyphp test:unit                  # Run unit tests
php easyphp test:integration           # Run integration tests

# Cache Commands
php easyphp cache:clear                # Clear application cache

# Development Server
php easyphp serve                      # Start development server

# Utility Commands  
php easyphp help                       # Show help information
php easyphp --version                  # Show version information
```

### Global Options

Available for most commands:

```bash
--help, -h        # Show command-specific help
--dry-run         # Preview changes without applying (migrations)
--verbose, -v     # Increase output verbosity
```

### Migration-Specific Options

```bash
# Migration execution options
--to=<version>    # Migrate to specific version
--dry-run         # Preview without applying changes

# Migration creation options
<name>            # Descriptive migration name (required)
```

### Server Options

```bash
--host=<host>     # Bind to specific host (default: 127.0.0.1)
--port=<port>     # Listen on specific port (default: 8000)
```

---

## Advanced Usage

### Automation & Scripting

#### Deployment Scripts

```bash
#!/bin/bash
# deploy.sh - Automated deployment script

echo "Deploying application..."

# Clear cache
php easyphp cache:clear

# Run migrations
php easyphp migrate

# Run tests
php easyphp test

echo "Deployment complete!"
```

#### Development Workflow

```bash
#!/bin/bash
# dev-setup.sh - Development environment setup

# Create basic application structure
php easyphp make:controller HomeController
php easyphp make:model User
php easyphp make:service AuthService

# Create initial migration
php easyphp migrate:create SetupInitialDatabase

# Start development server
php easyphp serve --port=8080
```

### CI/CD Integration

#### GitHub Actions Example

```yaml
name: EasyAPP Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - name: Run Tests
        run: |
          php easyphp migrate
          php easyphp test
```

### Multiple Environments

#### Environment-Specific Commands

```bash
# Development
php easyphp migrate:status
php easyphp serve

# Testing  
php easyphp migrate --dry-run
php easyphp test

# Production
php easyphp migrate --to=latest
php easyphp cache:clear
```

---

## Troubleshooting

### Common Issues

#### Command Not Found

**Problem:** `php easyphp` command not recognized

**Solution:**
```bash
# Ensure you're in the project root directory
pwd
ls -la easyphp

# Check PHP CLI is available
php --version

# Verify file permissions (Unix/Linux)
chmod +x easyphp
```

#### Database Connection Errors

**Problem:** Migration commands fail with database errors

**Solution:**
```bash
# Check database configuration
cat config.php | grep -i db

# Test database connectivity
php -r "new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');"

# Verify database credentials
php easyphp migrate:status
```

#### Permission Errors

**Problem:** Cannot write to directories

**Solution:**
```bash
# Check directory permissions
ls -la storage/
ls -la migrations/

# Fix permissions (Unix/Linux)
chmod -R 755 storage/
chmod -R 755 migrations/

# Verify web server user permissions
chown -R www-data:www-data storage/
```

### Performance Issues

#### Large Migration Files

```bash
# Use dry-run to preview large migrations
php easyphp migrate --dry-run

# Run specific migration only
php easyphp migrate --to=5

# Monitor migration progress
php easyphp migrate:status
```

#### Slow Test Execution

```bash
# Run specific test types
php easyphp test:unit

# Clear cache before testing
php easyphp cache:clear && php easyphp test
```

### Debug Mode

Enable debug output for troubleshooting:

```bash
# Enable debug in config.php
define('CONFIG_DEBUG', true);

# Run commands to see detailed output
php easyphp migrate --dry-run
php easyphp test --verbose
```

---

## CLI Best Practices

### Development Workflow

1. **Start New Features:**
   ```bash
   php easyphp make:controller FeatureController
   php easyphp make:model FeatureModel
   php easyphp migrate:create AddFeatureTables
   ```

2. **Test Changes:**
   ```bash
   php easyphp migrate --dry-run
   php easyphp test
   ```

3. **Deploy Updates:**
   ```bash
   php easyphp cache:clear
   php easyphp migrate
   ```

### Code Organization

- **Use descriptive names** for generated files
- **Group related functionality** in services
- **Create atomic migrations** with single responsibilities
- **Test all generated code** before deployment

### Security Considerations

- **Never run CLI commands as root** in production
- **Validate database credentials** before migrations
- **Use dry-run mode** for production deployments
- **Backup databases** before major migrations

---

## Integration Examples

### IDE Integration

#### VS Code Tasks

Create `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "EasyPHP: Run Migrations",
            "type": "shell",
            "command": "php",
            "args": ["easyphp", "migrate"],
            "group": "build"
        },
        {
            "label": "EasyPHP: Start Server",
            "type": "shell",
            "command": "php",
            "args": ["easyphp", "serve"],
            "group": "build",
            "isBackground": true
        }
    ]
}
```

### Makefile Integration

Create `Makefile`:

```makefile
.PHONY: migrate test serve clean

migrate:
	php easyphp migrate

migrate-status:
	php easyphp migrate:status

test:
	php easyphp test

serve:
	php easyphp serve

clean:
	php easyphp cache:clear

deploy: clean migrate test
	@echo "Deployment complete"
```

Usage:
```bash
make migrate
make test
make serve
make deploy
```

---

## Summary

The EasyPHP CLI provides a comprehensive, unified interface for all framework operations:

### Key Benefits:
- **Unified Interface:** Single command for all operations
- **Industry Standards:** Follows Laravel/Symfony conventions  
- **Professional Output:** Clear, colorized feedback
- **Comprehensive Help:** Built-in documentation
- **Development Focus:** Streamlined developer experience

### Command Groups:
- **Migration:** `migrate:*` - Database schema management
- **Generation:** `make:*` - Code scaffolding and boilerplate
- **Testing:** `test:*` - Test execution and validation
- **Utilities:** Cache management and development server

### Next Steps:
1. **Explore Commands:** Try `php easyphp help`
2. **Create Components:** Use `make:*` commands
3. **Manage Database:** Use `migrate:*` commands  
4. **Develop Locally:** Use `php easyphp serve`
5. **Automate Workflows:** Integrate with CI/CD

The unified CLI interface makes EasyAPP development faster, more consistent, and follows modern framework best practices.