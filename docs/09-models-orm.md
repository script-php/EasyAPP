# Models (ORM)

Modern Active Record implementation for elegant database interaction using object-oriented patterns.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Basic Usage](#basic-usage)
3. [Creating ORM Models](#creating-orm-models)
4. [CRUD Operations](#crud-operations)
5. [Query Builder](#query-builder)
6. [Relationships](#relationships)
7. [Advanced Features](#advanced-features)
8. [Validation](#validation)
9. [Best Practices](#best-practices)

---

## Introduction

The ORM (Object-Relational Mapping) provides an Active Record implementation that allows you to interact with your database using object-oriented syntax. Each database table has a corresponding Model class.

### Key Features

- Fluent query builder interface
- Automatic relationship handling
- Built-in validation
- Soft deletes support
- Automatic timestamps
- Mass assignment protection
- Attribute casting
- Model events
- Collection class for results

---

## Basic Usage

### Extending the ORM Base Class

```php
<?php

namespace App\Model;

use System\Framework\Orm;

class User extends Orm {
    
    protected static $table = 'users';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;
    
    protected static $fillable = [
        'name',
        'email',
        'password',
    ];
    
    protected static $hidden = [
        'password',
    ];
    
    protected static $casts = [
        'id' => 'int',
        'is_active' => 'bool',
    ];
}
```

### Simple Queries

```php
// Get all records
$users = User::all();

// Find by primary key
$user = User::find(1);

// Find or throw exception
$user = User::findOrFail(1);

// Check if record exists
$exists = User::where('email', $email)->exists();

// Count records
$count = User::where('is_active', true)->count();
```

---

## Creating ORM Models

### Using CLI (Recommended)

```bash
php easy make:model User
```

### Manual Creation

Create a file in `app/model/` directory:

**File:** `app/model/User.php`

```php
<?php

namespace App\Model;

use System\Framework\Orm;

/**
 * User Model
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Orm {
    
    /**
     * Table name
     */
    protected static $table = 'users';
    
    /**
     * Primary key
     */
    protected static $primaryKey = 'id';
    
    /**
     * Enable timestamps
     */
    protected static $timestamps = true;
    protected static $createdAtColumn = 'created_at';
    protected static $updatedAtColumn = 'updated_at';
    
    /**
     * Fillable attributes for mass assignment
     */
    protected static $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];
    
    /**
     * Guarded attributes (cannot be mass assigned)
     */
    protected static $guarded = [
        'id',
    ];
    
    /**
     * Hidden attributes (excluded from toArray/toJson)
     */
    protected static $hidden = [
        'password',
    ];
    
    /**
     * Attribute casts
     */
    protected static $casts = [
        'id' => 'int',
        'status' => 'int',
        'is_verified' => 'bool',
    ];
    
    /**
     * Enable soft deletes
     */
    protected static $softDelete = true;
    protected static $deletedAtColumn = 'deleted_at';
    
    /**
     * Validation rules
     */
    public function rules() {
        return [
            ['name', 'required|string|minLength:2|maxLength:255'],
            ['email', 'required|email|maxLength:255'],
            ['password', 'required|string|minLength:8'],
        ];
    }
    
    /**
     * Attribute labels for validation messages
     */
    public function attributeLabels() {
        return [
            'name' => 'Full Name',
            'email' => 'Email Address',
            'password' => 'Password',
        ];
    }
}
```

---

## CRUD Operations

### Create (Insert)

#### Method 1: Using create()

```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123',
]);

echo $user->id; // Auto-assigned ID
```

#### Method 2: Using save()

```php
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->password = 'secret123';
$user->save();

echo $user->id;
```

#### Method 3: Using fill() and save()

```php
$user = new User();
$user->fill([
    'name' => 'Bob Smith',
    'email' => 'bob@example.com',
    'password' => 'secret123',
]);
$user->save();
```

### Read (Select)

#### Find by primary key

```php
$user = User::find(1);

if ($user) {
    echo $user->name;
    echo $user->email;
}
```

#### Get all records

```php
$users = User::all();

foreach ($users as $user) {
    echo $user->name;
}
```

#### Get first record

```php
$user = User::where('email', $email)->first();
```

#### Find or create

```php
$user = User::firstOrCreate(
    ['email' => 'user@example.com'],
    ['name' => 'New User', 'status' => 1]
);
```

#### Update or create

```php
$user = User::updateOrCreate(
    ['email' => 'user@example.com'],
    ['name' => 'Updated Name', 'status' => 1]
);
```

### Update

#### Method 1: Find and update

```php
$user = User::find(1);
$user->name = 'Updated Name';
$user->email = 'updated@example.com';
$user->save();
```

#### Method 2: Mass update

```php
$affected = User::where('status', 0)
    ->update(['status' => 1]);
```

#### Method 3: Increment/Decrement

```php
// Increment column value
User::where('id', 1)->increment('login_count');
User::where('id', 1)->increment('points', 5);

// Decrement column value
User::where('id', 1)->decrement('credits');
User::where('id', 1)->decrement('balance', 10);
```

### Delete

#### Soft delete (if enabled)

```php
$user = User::find(1);
$user->delete(); // Sets deleted_at timestamp
```

#### Hard delete

```php
$user = User::find(1);
$user->forceDelete(); // Permanently deletes
```

#### Delete by query

```php
User::where('status', 0)->delete();
```

#### Restore soft deleted

```php
$user = User::withTrashed()->find(1);
$user->restore();
```

---

## Query Builder

### WHERE Clauses

```php
// Basic where
$users = User::where('status', 1)->get();

// Where with operator
$users = User::where('age', '>', 18)->get();

// Multiple where clauses
$users = User::where('status', 1)
    ->where('age', '>=', 18)
    ->get();

// OR where
$users = User::where('status', 1)
    ->orWhere('role', 'admin')
    ->get();

// Filter where (ignores null values)
$users = User::filterWhere('name', $name)
    ->filterWhere('email', $email)
    ->get();
```

### WHERE IN/NOT IN

```php
// WHERE IN
$users = User::whereIn('id', [1, 2, 3, 4, 5])->get();

// WHERE NOT IN
$users = User::whereNotIn('status', [0, 2])->get();
```

### WHERE BETWEEN

```php
// WHERE BETWEEN
$users = User::whereBetween('age', [18, 65])->get();

// WHERE NOT BETWEEN
$users = User::whereNotBetween('score', [0, 50])->get();
```

### WHERE NULL

```php
// WHERE NULL
$users = User::whereNull('deleted_at')->get();

// WHERE NOT NULL
$users = User::whereNotNull('email_verified_at')->get();
```

### Date Queries

```php
// WHERE date
$users = User::whereDate('created_at', '2025-01-01')->get();

// WHERE month
$users = User::whereMonth('created_at', 11)->get();

// WHERE year
$users = User::whereYear('created_at', 2025)->get();
```

### Ordering

```php
// ORDER BY ASC
$users = User::orderBy('name')->get();

// ORDER BY DESC
$users = User::orderBy('created_at', 'DESC')->get();

// Multiple ORDER BY
$users = User::orderBy('status', 'DESC')
    ->orderBy('name', 'ASC')
    ->get();
```

### Limiting and Offsetting

```php
// LIMIT
$users = User::limit(10)->get();

// LIMIT with OFFSET
$users = User::limit(10)->offset(20)->get();

// Take (alias for limit)
$users = User::take(5)->get();
```

### Grouping

```php
// GROUP BY
$stats = User::select(['role', 'COUNT(*) as count'])
    ->groupBy('role')
    ->get();

// GROUP BY with HAVING
$stats = User::select(['role', 'COUNT(*) as count'])
    ->groupBy('role')
    ->having('count', '>', 5)
    ->get();
```

### Joins

```php
// INNER JOIN
$users = User::join('profiles', 'users.id', '=', 'profiles.user_id')
    ->select(['users.*', 'profiles.bio'])
    ->get();

// LEFT JOIN
$users = User::leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->get();
```

### Aggregates

```php
// Count
$count = User::where('status', 1)->count();

// Max
$maxAge = User::max('age');

// Min
$minAge = User::min('age');

// Sum
$totalPoints = User::sum('points');

// Average
$avgAge = User::avg('age');
```

### Selecting Columns

```php
// Select specific columns
$users = User::select(['id', 'name', 'email'])->get();

// Select with alias
$users = User::select(['name', 'email as user_email'])->get();
```

### Pluck

```php
// Get array of single column values
$names = User::pluck('name');
// Returns: ['John', 'Jane', 'Bob']

// Get associative array (key => value)
$users = User::pluck('name', 'id');
// Returns: [1 => 'John', 2 => 'Jane', 3 => 'Bob']
```

### Chunking

```php
// Process large datasets in chunks
User::chunk(100, function($users) {
    foreach ($users as $user) {
        // Process each user
        echo $user->name;
    }
});
```

### Pagination

```php
// Paginate results
$result = User::where('status', 1)
    ->orderBy('created_at', 'DESC')
    ->paginate(15);

// Access pagination data
$users = $result['data'];
$total = $result['total'];
$currentPage = $result['current_page'];
$lastPage = $result['last_page'];
$hasMore = $result['has_more_pages'];
```

---

## Relationships

### One-to-One (hasOne)

```php
// In User model
public function profile() {
    return $this->hasOne(Profile::class, 'user_id', 'id');
}

// Usage
$user = User::find(1);
$profile = $user->profile();
```

### One-to-Many (hasMany)

```php
// In User model
public function posts() {
    return $this->hasMany(Post::class, 'user_id', 'id');
}

// Usage
$user = User::find(1);
$posts = $user->posts();
```

### Belongs To (belongsTo)

```php
// In Post model
public function user() {
    return $this->belongsTo(User::class, 'user_id', 'id');
}

// Usage
$post = Post::find(1);
$user = $post->user();
```

### Many-to-Many (belongsToMany)

```php
// In User model
public function roles() {
    return $this->belongsToMany(
        Role::class,
        'user_roles',      // Pivot table
        'user_id',         // Foreign key on pivot
        'role_id',         // Related key on pivot
        'id',              // Parent key
        'id'               // Related key
    );
}

// Usage
$user = User::find(1);
$roles = $user->roles();
```

### Eager Loading

```php
// Load with relationships
$users = User::with('posts', 'profile')->get();

foreach ($users as $user) {
    echo $user->name;
    foreach ($user->posts as $post) {
        echo $post->title;
    }
}

// Nested eager loading
$users = User::with('posts.comments')->get();
```

### Relationship Counts

```php
// Count related records without loading them
$users = User::withCount('posts')->get();

foreach ($users as $user) {
    echo $user->posts_count; // Automatic attribute
}

// Multiple counts
$users = User::withCount('posts', 'comments')->get();

// Conditional count
$users = User::withCount([
    'posts' => function($query) {
        $query->where('published', 1);
    }
])->get();
```

### Join With Relations

```php
// Join with relationship
$posts = Post::joinWith('user')->get();

// Left join with relationship
$posts = Post::joinWith('user', 'LEFT')->get();

// Join with constraints
$posts = Post::joinWith([
    'user' => function($query) {
        $query->where('status', 1);
    }
])->get();
```

### Inverse Relations

```php
// In Post model
public function user() {
    return $this->belongsTo(User::class, 'user_id')
        ->inverseOf('posts');
}

// In User model
public function posts() {
    return $this->hasMany(Post::class, 'user_id')
        ->inverseOf('user');
}
```

---

## Advanced Features

### Soft Deletes

```php
// Enable in model
protected static $softDelete = true;

// Query including trashed
$users = User::withTrashed()->get();

// Query only trashed
$users = User::onlyTrashed()->get();

// Restore deleted
$user = User::withTrashed()->find(1);
$user->restore();
```

### Attribute Casting

```php
protected static $casts = [
    'id' => 'int',
    'is_active' => 'bool',
    'price' => 'float',
    'metadata' => 'json',
    'data' => 'array',
    'created_at' => 'date',
];

// Usage - automatic casting
$user = User::find(1);
$isActive = $user->is_active; // Returns boolean, not 0/1
```

### Hidden Attributes

```php
protected static $hidden = [
    'password',
    'remember_token',
];

// Converts to array without hidden fields
$array = $user->toArray();
$json = $user->toJson();
```

### Mass Assignment Protection

```php
// Whitelist attributes
protected static $fillable = [
    'name',
    'email',
];

// Or blacklist attributes
protected static $guarded = [
    'id',
    'is_admin',
];

// Usage
$user = User::create($request->post); // Only fillable attributes are set
```

### Model Events

```php
class User extends Orm {
    
    // Before saving (insert or update)
    protected function beforeSave() {
        // Modify attributes before save
        if (isset($this->attributes['email'])) {
            $this->attributes['email'] = strtolower($this->attributes['email']);
        }
    }
    
    // After saving
    protected function afterSave() {
        // Log the save operation
        $this->logger->info('User saved', ['id' => $this->id]);
    }
    
    // Before insert
    protected function beforeInsert() {
        // Generate default values
        if (!isset($this->attributes['uuid'])) {
            $this->attributes['uuid'] = uniqid();
        }
    }
    
    // After insert
    protected function afterInsert() {
        // Send welcome email
        $this->load->service('email|sendWelcome', $this);
    }
    
    // Before update
    protected function beforeUpdate($changedAttributes) {
        // Validate changes
    }
    
    // After update
    protected function afterUpdate($changedAttributes) {
        // Log what changed
        $this->logger->info('User updated', $changedAttributes);
    }
    
    // Before delete
    protected function beforeDelete() {
        // Check if can be deleted
        if ($this->posts_count > 0) {
            return false; // Cancel deletion
        }
    }
    
    // After delete
    protected function afterDelete() {
        // Cleanup related data
    }
}
```

### Raw SQL Queries

```php
// Find by raw SQL
$users = User::findBySql(
    "SELECT * FROM users WHERE status = ? AND created_at > ?",
    [1, '2025-01-01']
);

// Returns collection of User objects
foreach ($users as $user) {
    echo $user->name;
}
```

### Scoped Queries

```php
class User extends Orm {
    
    public static function active() {
        return static::where('status', 1);
    }
    
    public static function admins() {
        return static::where('role', 'admin');
    }
}

// Usage
$activeUsers = User::active()->get();
$admins = User::admins()->get();
$activeAdmins = User::active()->admins()->get();
```

---

## Validation

### Defining Rules

```php
public function rules() {
    return [
        ['name', 'required|string|minLength:2|maxLength:255'],
        ['email', 'required|email|maxLength:255'],
        ['password', 'required|string|minLength:8'],
        ['age', 'integer|min:18|max:120'],
        ['phone', 'optional|phone'],
    ];
}
```

### Available Rules

- **required** - Field must not be empty
- **optional** - Field can be empty
- **email** - Valid email format
- **url** - Valid URL format
- **phone** - Valid phone number
- **uuid** - Valid UUID format
- **json** - Valid JSON string
- **string** - Must be string
- **integer** - Must be integer
- **numeric** - Must be numeric
- **boolean** - Must be boolean
- **minLength:n** - Minimum string length
- **maxLength:n** - Maximum string length
- **min:n** - Minimum numeric value
- **max:n** - Maximum numeric value
- **between:min,max** - Value between range
- **in:val1,val2** - Value in list
- **notIn:val1,val2** - Value not in list
- **regex:pattern** - Match regex pattern

### Scenarios

```php
public function scenarios() {
    return [
        'register' => ['name', 'email', 'password'],
        'update' => ['name', 'email'],
        'admin' => ['name', 'email', 'role', 'status'],
    ];
}

// Usage
$user = new User();
$user->setScenario('register');
$user->fill($data);

if ($user->save()) {
    // Validation passed
} else {
    // Get validation errors
    $errors = $user->getErrors();
}
```

### Manual Validation

```php
$user = new User();
$user->fill($data);

if ($user->validate()) {
    // Validation passed
    $user->save(false); // Skip validation since already done
} else {
    // Get errors
    $errors = $user->getErrors();
    foreach ($errors as $field => $messages) {
        echo "$field: " . implode(', ', $messages);
    }
}
```

---

## Best Practices

### 1. Use Namespaces

```php
namespace App\Model;

use System\Framework\Orm;

class User extends Orm {
    // ...
}
```

### 2. Type Hint Properties

```php
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 */
class User extends Orm {
    // ...
}
```

### 3. Define Relationships Properly

```php
// Always define both sides of relationship
class User extends Orm {
    public function posts() {
        return $this->hasMany(Post::class, 'user_id')->inverseOf('user');
    }
}

class Post extends Orm {
    public function user() {
        return $this->belongsTo(User::class, 'user_id')->inverseOf('posts');
    }
}
```

### 4. Use Eager Loading

```php
// Good: N+1 query problem solved
$users = User::with('posts')->get();
foreach ($users as $user) {
    foreach ($user->posts as $post) {
        // No additional queries
    }
}

// Bad: N+1 query problem
$users = User::all();
foreach ($users as $user) {
    foreach ($user->posts() as $post) {
        // Additional query for each user
    }
}
```

### 5. Protect Sensitive Data

```php
protected static $hidden = [
    'password',
    'remember_token',
    'api_secret',
];
```

---

## Related Documentation

- **[Models (Traditional)](08-models-traditional.md)** - Traditional database queries
- **[ORM Relationships](19-orm-relationships.md)** - Detailed relationship guide
- **[Validation](23-validation.md)** - Complete validation reference
- **[Query Builder](18-query-builder.md)** - Advanced query building

---

**Previous:** [Models (Traditional)](08-models-traditional.md)  
**Next:** [Views](10-views.md)
