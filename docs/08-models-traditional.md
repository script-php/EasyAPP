# Models (Traditional Style)

Models handle data access and business logic in your application. This guide covers the traditional model style that directly uses the database connection.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Model Basics](#model-basics)
3. [Creating Models](#creating-models)
4. [Database Operations](#database-operations)
5. [Query Examples](#query-examples)
6. [Best Practices](#best-practices)
7. [Advanced Topics](#advanced-topics)

---

## Introduction

Traditional models in EasyAPP provide direct access to the database through the PDO-based database abstraction layer. They are suitable for:

- Simple CRUD operations
- Complex custom queries
- Legacy database schemas
- Performance-critical queries
- Direct SQL control

For modern Active Record pattern, see [Models (ORM)](09-models-orm.md).

---

## Model Basics

### Base Model Class

All models extend the abstract `Model` class:

```php
abstract class Model {
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

Models have access to framework services through magic methods:

```php
$this->db        // Database connection
$this->cache     // Cache system
$this->logger    // Logger
$this->load      // Loader for other models
$this->events    // Event system
```

---

## Creating Models

### Using CLI (Recommended)

```bash
php easy make:model User
```

This creates `app/model/user.php` with a basic structure.

### Manual Creation

Create a file in `app/model/` directory:

**File:** `app/model/user.php`

```php
<?php

/**
 * User Model
 * Handles user data operations
 */
class ModelUser extends Model {
    
    /**
     * Get all users
     * 
     * @return array
     */
    public function getAll() {
        $sql = "SELECT * FROM `" . DB_PREFIX . "users` ORDER BY created_at DESC";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $user_id
     * @return array|null
     */
    public function getById($user_id) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE id = :id";
        $query = $this->db->query($sql, [':id' => $user_id]);
        return $query->row;
    }
    
    /**
     * Create new user
     * 
     * @param array $data
     * @return int Last insert ID
     */
    public function create($data) {
        $sql = "INSERT INTO `" . DB_PREFIX . "users` 
                SET name = :name, 
                    email = :email, 
                    password = :password,
                    created_at = NOW()";
        
        $params = [
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ];
        
        $this->db->query($sql, $params);
        return $this->db->getLastId();
    }
    
    /**
     * Update user
     * 
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function update($user_id, $data) {
        $sql = "UPDATE `" . DB_PREFIX . "users` 
                SET name = :name, 
                    email = :email,
                    updated_at = NOW()
                WHERE id = :id";
        
        $params = [
            ':id' => $user_id,
            ':name' => $data['name'],
            ':email' => $data['email']
        ];
        
        $this->db->query($sql, $params);
        return $this->db->countAffected() > 0;
    }
    
    /**
     * Delete user
     * 
     * @param int $user_id
     * @return bool
     */
    public function delete($user_id) {
        $sql = "DELETE FROM `" . DB_PREFIX . "users` WHERE id = :id";
        $this->db->query($sql, [':id' => $user_id]);
        return $this->db->countAffected() > 0;
    }
}
```

### Naming Conventions

- **Class Name:** `Model` + PascalCase name
  - Example: `ModelUser`, `ModelProduct`, `ModelOrderItem`
- **File Name:** lowercase, matches the route/controller name
  - Example: `user.php`, `product.php`, `order_item.php`
- **Method Name:** camelCase, descriptive
  - Example: `getAll()`, `getById()`, `getActiveUsers()`

---

## Database Operations

### Query Execution

#### Basic Query

```php
$sql = "SELECT * FROM `" . DB_PREFIX . "users`";
$query = $this->db->query($sql);
```

#### Query with Parameters (Prepared Statements)

```php
$sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE id = :id";
$query = $this->db->query($sql, [':id' => $user_id]);
```

### Result Retrieval

#### Get Single Row

```php
$sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE id = :id";
$query = $this->db->query($sql, [':id' => 1]);
$user = $query->row; // Returns associative array or null
```

#### Get All Rows

```php
$sql = "SELECT * FROM `" . DB_PREFIX . "users`";
$query = $this->db->query($sql);
$users = $query->rows; // Returns array of associative arrays
```

#### Get Row Count

```php
$sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE status = :status";
$query = $this->db->query($sql, [':status' => 1]);
$count = $query->num_rows;
```

### Insert Operations

#### Single Insert

```php
$sql = "INSERT INTO `" . DB_PREFIX . "users` (name, email) VALUES (:name, :email)";
$this->db->query($sql, [
    ':name' => 'John Doe',
    ':email' => 'john@example.com'
]);

// Get the inserted ID
$user_id = $this->db->getLastId();
```

#### Insert with Multiple Columns

```php
$sql = "INSERT INTO `" . DB_PREFIX . "products` 
        SET name = :name,
            description = :description,
            price = :price,
            stock = :stock,
            created_at = NOW()";

$params = [
    ':name' => $data['name'],
    ':description' => $data['description'],
    ':price' => $data['price'],
    ':stock' => $data['stock']
];

$this->db->query($sql, $params);
```

### Update Operations

```php
$sql = "UPDATE `" . DB_PREFIX . "users` 
        SET name = :name, 
            email = :email,
            updated_at = NOW()
        WHERE id = :id";

$params = [
    ':id' => $user_id,
    ':name' => $new_name,
    ':email' => $new_email
];

$this->db->query($sql, $params);

// Check affected rows
$affected = $this->db->countAffected();
```

### Delete Operations

```php
$sql = "DELETE FROM `" . DB_PREFIX . "users` WHERE id = :id";
$this->db->query($sql, [':id' => $user_id]);

// Check if deleted
$deleted = $this->db->countAffected() > 0;
```

### Transactions

```php
public function transferFunds($from_account, $to_account, $amount) {
    $this->db->beginTransaction();
    
    try {
        // Deduct from source account
        $sql = "UPDATE `" . DB_PREFIX . "accounts` 
                SET balance = balance - :amount 
                WHERE id = :id AND balance >= :amount";
        $this->db->query($sql, [':id' => $from_account, ':amount' => $amount]);
        
        if ($this->db->countAffected() === 0) {
            throw new Exception('Insufficient funds');
        }
        
        // Add to destination account
        $sql = "UPDATE `" . DB_PREFIX . "accounts` 
                SET balance = balance + :amount 
                WHERE id = :id";
        $this->db->query($sql, [':id' => $to_account, ':amount' => $amount]);
        
        // Commit transaction
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $this->db->rollBack();
        throw $e;
    }
}
```

---

## Query Examples

### SELECT Queries

#### Simple SELECT

```php
public function getActiveUsers() {
    $sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE status = 1";
    $query = $this->db->query($sql);
    return $query->rows;
}
```

#### SELECT with WHERE Conditions

```php
public function getUsersByRole($role) {
    $sql = "SELECT * FROM `" . DB_PREFIX . "users` 
            WHERE role = :role AND status = :status
            ORDER BY created_at DESC";
    
    $query = $this->db->query($sql, [
        ':role' => $role,
        ':status' => 1
    ]);
    
    return $query->rows;
}
```

#### SELECT with LIMIT and OFFSET

```php
public function getUsersPaginated($page = 1, $perPage = 10) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT * FROM `" . DB_PREFIX . "users` 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset";
    
    // Note: LIMIT and OFFSET need special handling
    $query = $this->db->query($sql . " LIMIT {$perPage} OFFSET {$offset}");
    
    return $query->rows;
}
```

#### SELECT with JOIN

```php
public function getUsersWithProfiles() {
    $sql = "SELECT u.*, p.bio, p.avatar 
            FROM `" . DB_PREFIX . "users` u
            LEFT JOIN `" . DB_PREFIX . "profiles` p ON u.id = p.user_id
            ORDER BY u.created_at DESC";
    
    $query = $this->db->query($sql);
    return $query->rows;
}
```

#### SELECT with Aggregation

```php
public function getUserStatistics() {
    $sql = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN status = 1 THEN 1 END) as active_users,
                COUNT(CASE WHEN status = 0 THEN 1 END) as inactive_users
            FROM `" . DB_PREFIX . "users`";
    
    $query = $this->db->query($sql);
    return $query->row;
}
```

#### SELECT with GROUP BY

```php
public function getUserCountByRole() {
    $sql = "SELECT role, COUNT(*) as count 
            FROM `" . DB_PREFIX . "users` 
            GROUP BY role 
            ORDER BY count DESC";
    
    $query = $this->db->query($sql);
    return $query->rows;
}
```

### Complex Queries

#### Subqueries

```php
public function getUsersWithMostOrders() {
    $sql = "SELECT u.*, 
                   (SELECT COUNT(*) 
                    FROM `" . DB_PREFIX . "orders` o 
                    WHERE o.user_id = u.id) as order_count
            FROM `" . DB_PREFIX . "users` u
            HAVING order_count > 0
            ORDER BY order_count DESC
            LIMIT 10";
    
    $query = $this->db->query($sql);
    return $query->rows;
}
```

#### UNION Queries

```php
public function getAllTransactions($user_id) {
    $sql = "SELECT 'deposit' as type, amount, created_at 
            FROM `" . DB_PREFIX . "deposits` 
            WHERE user_id = :user_id
            UNION ALL
            SELECT 'withdrawal' as type, amount, created_at 
            FROM `" . DB_PREFIX . "withdrawals` 
            WHERE user_id = :user_id
            ORDER BY created_at DESC";
    
    $query = $this->db->query($sql, [':user_id' => $user_id]);
    return $query->rows;
}
```

#### CASE Statements

```php
public function getUsersWithStatusLabel() {
    $sql = "SELECT *,
                CASE 
                    WHEN status = 1 THEN 'Active'
                    WHEN status = 0 THEN 'Inactive'
                    WHEN status = 2 THEN 'Banned'
                    ELSE 'Unknown'
                END as status_label
            FROM `" . DB_PREFIX . "users`";
    
    $query = $this->db->query($sql);
    return $query->rows;
}
```

### Search Queries

```php
public function search($keyword) {
    $keyword = '%' . $keyword . '%';
    
    $sql = "SELECT * FROM `" . DB_PREFIX . "users` 
            WHERE name LIKE :keyword 
               OR email LIKE :keyword
            ORDER BY name ASC";
    
    $query = $this->db->query($sql, [':keyword' => $keyword]);
    return $query->rows;
}
```

---

## Best Practices

### 1. Always Use Prepared Statements

```php
// Good: Prevents SQL injection
$sql = "SELECT * FROM users WHERE email = :email";
$query = $this->db->query($sql, [':email' => $email]);

// Bad: Vulnerable to SQL injection
$sql = "SELECT * FROM users WHERE email = '$email'";
$query = $this->db->query($sql);
```

### 2. Use Table Prefixes

```php
// Good: Supports table prefixes
$sql = "SELECT * FROM `" . DB_PREFIX . "users`";

// Acceptable if no prefix needed
$sql = "SELECT * FROM `users`";
```

### 3. Return Consistent Data Types

```php
// Good: Always returns array
public function getAll() {
    $query = $this->db->query($sql);
    return $query->rows ?? [];
}

// Good: Returns array or null
public function getById($id) {
    $query = $this->db->query($sql, [':id' => $id]);
    return $query->row;
}
```

### 4. Handle Errors Gracefully

```php
public function create($data) {
    try {
        $sql = "INSERT INTO `" . DB_PREFIX . "users` ...";
        $this->db->query($sql, $params);
        return $this->db->getLastId();
    } catch (Exception $e) {
        $this->logger->error('Failed to create user', [
            'error' => $e->getMessage(),
            'data' => $data
        ]);
        return false;
    }
}
```

### 5. Use Meaningful Method Names

```php
// Good: Descriptive method names
public function getActiveUsers() { }
public function findByEmail($email) { }
public function getUserOrderCount($user_id) { }

// Avoid: Generic names
public function get() { }
public function find() { }
public function data() { }
```

### 6. Document Complex Queries

```php
/**
 * Get users with their order statistics
 * 
 * This query joins users with orders and calculates:
 * - Total number of orders
 * - Total amount spent
 * - Last order date
 * 
 * @param int $min_orders Minimum number of orders
 * @return array
 */
public function getUsersWithOrderStats($min_orders = 1) {
    $sql = "SELECT 
                u.*,
                COUNT(o.id) as order_count,
                SUM(o.total) as total_spent,
                MAX(o.created_at) as last_order
            FROM `" . DB_PREFIX . "users` u
            LEFT JOIN `" . DB_PREFIX . "orders` o ON u.id = o.user_id
            GROUP BY u.id
            HAVING order_count >= :min_orders
            ORDER BY total_spent DESC";
    
    $query = $this->db->query($sql, [':min_orders' => $min_orders]);
    return $query->rows;
}
```

### 7. Separate Concerns

```php
// Good: Focused model methods
class ModelUser extends Model {
    public function getById($id) { }
    public function create($data) { }
    public function update($id, $data) { }
}

class ModelUserProfile extends Model {
    public function getByUserId($user_id) { }
    public function updateProfile($user_id, $data) { }
}

// Avoid: One model doing everything
class ModelUser extends Model {
    public function getUser() { }
    public function saveUser() { }
    public function getUserProfile() { }
    public function saveProfile() { }
    public function getUserOrders() { }
    // Too many responsibilities
}
```

---

## Advanced Topics

### Caching Query Results

```php
public function getAll() {
    $cache_key = 'users.all';
    
    // Try to get from cache
    $users = $this->cache->get($cache_key);
    
    if ($users === null) {
        // Cache miss - query database
        $sql = "SELECT * FROM `" . DB_PREFIX . "users`";
        $query = $this->db->query($sql);
        $users = $query->rows;
        
        // Store in cache for 1 hour
        $this->cache->set($cache_key, $users, 3600);
    }
    
    return $users;
}
```

### Loading Related Models

```php
class ModelOrder extends Model {
    
    public function getOrderWithItems($order_id) {
        // Get order
        $sql = "SELECT * FROM `" . DB_PREFIX . "orders` WHERE id = :id";
        $query = $this->db->query($sql, [':id' => $order_id]);
        $order = $query->row;
        
        if (!$order) {
            return null;
        }
        
        // Get order items using another model
        // Both styles work:
        $orderItemModel = $this->load->model('order_item');
        $order['items'] = $orderItemModel->getByOrderId($order_id);
        
        // OR use magic access:
        // $this->load->model('order_item');
        // $order['items'] = $this->model_order_item->getByOrderId($order_id);
        
        return $order;
    }
}
```

### Dynamic Query Building

```php
public function getFiltered($filters = []) {
    $sql = "SELECT * FROM `" . DB_PREFIX . "users` WHERE 1=1";
    $params = [];
    
    if (!empty($filters['role'])) {
        $sql .= " AND role = :role";
        $params[':role'] = $filters['role'];
    }
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (name LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $query = $this->db->query($sql, $params);
    return $query->rows;
}
```

### Batch Operations

```php
public function bulkUpdateStatus($user_ids, $status) {
    if (empty($user_ids)) {
        return false;
    }
    
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    
    $sql = "UPDATE `" . DB_PREFIX . "users` 
            SET status = ? 
            WHERE id IN ($placeholders)";
    
    $params = array_merge([$status], $user_ids);
    
    $this->db->query($sql, $params);
    return $this->db->countAffected();
}
```

---

## Related Documentation

- **[Models (ORM)](09-models-orm.md)** - Active Record pattern
- **[Database Usage](17-database.md)** - Database configuration
- **[Query Builder](18-query-builder.md)** - Fluent query interface
- **[Controllers](07-controllers.md)** - Using models in controllers

---

**Previous:** [Controllers](07-controllers.md)  
**Next:** [Models (ORM)](09-models-orm.md)
