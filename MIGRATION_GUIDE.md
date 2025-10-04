# EasyAPP Migration System

## Complete Database Migration Solution

The EasyAPP Migration System provides enterprise-grade database schema management with version control, rollback support, and seamless integrat## Migration Structureon with your existing Tables class.

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Creating Migrations](#creating-migrations)
5. [Running Migrations](#running-migrations)
6. [Rolling Back](#rolling-back)
7. [Migration Structure](#migration-structure)
8. [Best Practices](#best-practices)
9. [Advanced Features](#advanced-features)
10. [CLI Commands](#cli-commands)
11. [Troubleshooting](#troubleshooting)

---

## Overview

### What is the Migration System?

The Migration System allows you to:
- **Version Control** your database schema
- **Safely upgrade** databases across environments
- **Rollback changes** when needed
- **Collaborate** with team members on schema changes
- **Preserve data** during schema transformations

### Key Features:

- **Sequential Migrations** - Changes applied in order
- **Automatic Rollback** - Undo changes safely
- **Data Transformation** - Migrate data during schema changes
- **Dependency Checking** - Ensure requirements are met
- **Transaction Safety** - All operations in transactions
- **CLI Interface** - Command-line tools for automation
- **Progress Tracking** - See what's been applied
- **Dry Run Mode** - Preview changes without applying

---

## Installation

The migration system is included with EasyAPP Framework. No additional installation required!

### **System Requirements:**
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- EasyAPP Framework
- CLI access (for command-line tools)

---

## Quick Start

### **1. Check Migration Status**
```bash
php migrate --status
```

### **2. Create Your First Migration**
```bash
php migrate --create="CreateUsersTable"
```

### **3. Edit the Generated Migration**
```php
<?php
class Migration_001_CreateUsersTable extends Migration {
    public function up(): void {
        $this->tables->table('users')
            ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
            ->column('username')->type('VARCHAR(50)')->notNull(true)
            ->column('email')->type('VARCHAR(100)')->notNull(true)
            ->create();
    }
    
    public function down(): void {
        $this->tables->drop('users');
    }
}
```

### **4. Run the Migration**
```bash
php migrate
```

### **5. Verify Changes**
```bash
php migrate --status
```

---

## Creating Migrations

### **Generate Migration File**
```bash
php migrate --create="DescriptiveName"
```

**Examples:**
```bash
php migrate --create="CreateUsersTable"
php migrate --create="AddEmailToUsers" 
php migrate --create="UpdateProductPricing"
php migrate --create="AddIndexesToOrders"
```

### **Migration File Structure**
```
migrations/
├── 001_create_users_table.php
├── 002_add_email_to_users.php
├── 003_update_product_pricing.php
└── 004_add_indexes_to_orders.php
```

### **Migration Class Template**
```php
<?php
use System\Framework\Migration;

class Migration_001_CreateUsersTable extends Migration {
    
    /**
     * Apply the migration (create/modify schema)
     */
    public function up(): void {
        // Your schema changes here
    }
    
    /**
     * Rollback the migration (undo changes)  
     */
    public function down(): void {
        // Undo the changes made in up()
    }
    
    /**
     * Describe what this migration does
     */
    public function getDescription(): string {
        return 'Create users table with basic authentication fields';
    }
}
```

---

## Running Migrations

### **Run All Pending Migrations**
```bash
php migrate
```

### **Run to Specific Version**
```bash
php migrate --to=5
```

### **Dry Run (Preview Changes)**
```bash
php migrate --dry-run
```

### **Programmatic Usage**
```php
use System\Framework\MigrationManager;

$migrationManager = new MigrationManager($registry);

// Run all pending migrations
$results = $migrationManager->migrate();

// Run to specific version
$results = $migrationManager->migrate(5);

// Dry run
$results = $migrationManager->migrate(null, true);
```

---

## Rolling Back

### **Rollback to Specific Version**
```bash
php migrate --rollback=3
```

### **Dry Run Rollback**
```bash
php migrate --rollback=3 --dry-run
```

### **Programmatic Rollback**
```php
// Rollback to version 3
$results = $migrationManager->rollback(3);

// Dry run rollback
$results = $migrationManager->rollback(3, true);
```

---

## 🏗 **Migration Structure**

### **Basic Table Operations**

#### **Create Table**
```php
public function up(): void {
    $this->tables->table('users')
        ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
        ->column('username')->type('VARCHAR(50)')->notNull(true)
        ->column('email')->type('VARCHAR(100)')->notNull(true)
        ->column('created_at')->timestamp(true)
        ->index('idx_username', ['username'])
        ->unique('email')
        ->create();
}

public function down(): void {
    $this->tables->drop('users');
}
```

#### **Modify Table**
```php
public function up(): void {
    $this->tables->table('users')
        ->column('phone')->type('VARCHAR(20)')->nullable()->after('email')
        ->column('status')->enum(['active', 'inactive'])->default('active')
        ->create(); // This will ALTER the existing table
}

public function down(): void {
    $this->query("ALTER TABLE `" . CONFIG_DB_PREFIX . "users` DROP COLUMN `phone`");
    $this->query("ALTER TABLE `" . CONFIG_DB_PREFIX . "users` DROP COLUMN `status`");
}
```

### **Modern Column Types**
```php
public function up(): void {
    $this->tables->table('products')
        ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
        ->column('data')->json()                              // JSON column
        ->column('price')->decimal(10, 2)                     // Precise decimal
        ->column('status')->enum(['draft', 'published'])      // Enumeration
        ->column('uuid')->uuid()                              // UUID field
        ->column('location')->geometry()                      // Spatial data
        ->column('created_at')->timestamp(true)               // Auto timestamp
        ->create();
}
```

### **Foreign Key Relations**
```php
public function up(): void {
    $this->tables->table('user_profiles')
        ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
        ->column('user_id')->type('INT(11)')->notNull(true)
        ->column('bio')->text()
        ->foreign('user_id', 'users', 'id', true)  // CASCADE delete
        ->create();
}
```

### **Data Transformation**
```php
public function up(): void {
    // Add new column
    $this->tables->table('users')
        ->column('display_name')->type('VARCHAR(100)')->nullable()
        ->create();
    
    // Transform existing data
    $this->transformData('users', function($row) {
        return [
            'display_name' => ucwords(str_replace('_', ' ', $row['username']))
        ];
    });
}
```

---

## Best Practices

### **1. Naming Conventions**
```
Good Names:
- CreateUsersTable
- AddEmailIndexToUsers  
- UpdateProductPricing
- RemoveDeprecatedColumns

Bad Names:
- Fix
- Update
- Change
- Temp
```

### **2. Atomic Changes**
```php
// Good - Single responsibility
class Migration_005_AddUserPhoneField extends Migration {
    public function up(): void {
        $this->tables->table('users')
            ->column('phone')->type('VARCHAR(20)')->nullable()
            ->create();
    }
}

// Bad - Multiple unrelated changes
class Migration_005_VariousUpdates extends Migration {
    public function up(): void {
        // Adding phone field
        // Creating orders table
        // Updating product pricing
        // ... too many changes!
    }
}
```

### **3. Always Implement down()**
```php
// Good - Complete rollback
public function down(): void {
    $this->tables->drop('user_sessions');
}

// Bad - No rollback capability
public function down(): void {
    // TODO: Implement rollback
}
```

### **4. Data Safety**
```php
public function up(): void {
    // Check before making destructive changes
    if ($this->columnExists('users', 'old_field')) {
        // Copy data to new field first
        $this->query("UPDATE users SET new_field = old_field");
        
        // Then drop old field
        $this->query("ALTER TABLE users DROP COLUMN old_field");
    }
}
```

### **5. Use Dependencies**
```php
public function checkDependencies(string $direction = 'up'): bool {
    if ($direction === 'up') {
        return $this->tableExists('users'); // Require users table
    }
    return true;
}
```

---

## Advanced Features

### **Batch Data Processing**
```php
public function up(): void {
    // Process large datasets in batches
    $this->transformData('large_table', function($row) {
        return [
            'processed_field' => process_data($row['raw_field'])
        ];
    }, 1000); // Process 1000 rows at a time
}
```

### **Complex Schema Changes**
```php
public function up(): void {
    // Create temporary table for complex transformations
    $this->tables->copyTable('users_backup', 'users', true);
    
    // Make changes
    $this->tables->table('users')
        ->column('new_structure')->json()
        ->create();
    
    // Transform data with complex logic
    $this->complexDataTransformation();
    
    // Cleanup
    $this->tables->drop('users_backup');
}

private function complexDataTransformation(): void {
    // Your complex transformation logic here
}
```

### **Conditional Migrations**
```php
public function up(): void {
    // Only run if condition is met
    if ($this->shouldApplyChanges()) {
        $this->tables->table('conditional_table')
            ->column('id')->type('INT(11)')->autoIncrement(true)
            ->create();
    }
}

private function shouldApplyChanges(): bool {
    // Check some condition (config, environment, etc.)
    return CONFIG_ENVIRONMENT === 'production';
}
```

---

## CLI Commands

### **Basic Commands**
```bash
# Show help
php migrate --help

# Show current status  
php migrate --status

# Run all pending migrations
php migrate

# Run to specific version
php migrate --to=5

# Create new migration
php migrate --create="MigrationName"
```

### **Rollback Commands**
```bash
# Rollback to version 3
php migrate --rollback=3

# Dry run rollback
php migrate --rollback=3 --dry-run
```

### **Development Commands**
```bash
# Preview what would be executed
php migrate --dry-run

# See detailed status
php migrate --status
```

### **Example CLI Output**
```
Migration Status
======================================================================
Database: my_app
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

---

## Troubleshooting

### **Common Issues**

#### **Migration Failed to Execute**
```bash
# Check the error logs
tail -f storage/logs/error.log

# Try dry run to see what would be executed
php migrate --dry-run
```

#### **Foreign Key Constraint Errors**
```php
// Solution: Drop foreign keys first
public function down(): void {
    $this->tables->dropForeignKeys('child_table');
    $this->tables->drop('child_table');
    $this->tables->drop('parent_table');
}
```

#### **Table Already Exists**
```php
// Solution: Check existence first
public function up(): void {
    if (!$this->tableExists('new_table')) {
        $this->tables->table('new_table')
            ->column('id')->type('INT(11)')
            ->create();
    }
}
```

#### **Large Data Migration Timeout**
```php
// Solution: Use batch processing
public function up(): void {
    // Increase timeout
    ini_set('max_execution_time', 0);
    
    // Process in smaller batches
    $this->transformData('large_table', function($row) {
        return ['new_field' => transform($row)];
    }, 500); // Smaller batch size
}
```

### **Recovery Procedures**

#### **Stuck Migration**
```sql
-- Manual cleanup if needed
DELETE FROM framework_migrations WHERE version = 'problematic_version';
```

#### **Rollback Failed**
```bash
# Manual rollback approach
php migrate --rollback=previous_working_version --dry-run
# Review what would be executed, then run manually if needed
```

---

## Migration Checklist

### **Before Creating Migration:**
- [ ] Clear understanding of required changes
- [ ] Considered data preservation needs
- [ ] Planned rollback strategy
- [ ] Reviewed dependencies

### **When Writing Migration:**
- [ ] Descriptive class and file names
- [ ] Complete up() method implementation
- [ ] Complete down() method implementation  
- [ ] Added appropriate indexes
- [ ] Handled foreign key constraints
- [ ] Added data transformation if needed
- [ ] Tested with sample data

### **Before Deploying:**
- [ ] Tested migration locally
- [ ] Tested rollback procedure
- [ ] Verified on staging environment
- [ ] Backed up production data
- [ ] Planned maintenance window if needed
- [ ] Documented any manual steps

---

## You're Ready!

The EasyAPP Migration System provides everything you need for professional database schema management. Start with simple table creation and gradually use more advanced features as your application grows.

### Next Steps:
1. Create your first migration
2. Test the rollback process  
3. Set up automated deployments
4. Train your team on the workflow

### Need Help?
- Check the example migrations in `migrations/`
- Review the Tables class documentation
- Use dry-run mode to preview changes
- Test everything in development first

**Happy migrating!**