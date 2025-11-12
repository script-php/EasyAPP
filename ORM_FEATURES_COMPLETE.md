# Complete ORM Features Reference

Complete guide to all features available in the EasyAPP ORM system.

---

## Table of Contents

1. [Basic CRUD Operations](#basic-crud-operations)
2. [Query Builder - WHERE Clauses](#query-builder---where-clauses)
3. [Query Builder - Advanced](#query-builder---advanced)
4. [Relationships](#relationships)
5. [Collections](#collections)
6. [Transactions](#transactions)
7. [Soft Deletes](#soft-deletes)
8. [Timestamps](#timestamps)
9. [Model Events](#model-events)
10. [Query Result Formats](#query-result-formats)
11. [Mass Assignment](#mass-assignment)
12. [Attribute Casting](#attribute-casting)
13. [Scopes](#scopes)

---

## Basic CRUD Operations

### 1. Create New Record
```php
// Method 1: Create and save
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Method 2: Mass assignment
$user = new User(['name' => 'John Doe', 'email' => 'john@example.com']);
$user->save();

// Method 3: Create directly
$user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
```

### 2. Find by Primary Key
```php
// Find by ID (returns model or null)
$user = User::find(1);

// Find or fail (throws exception if not found)
$user = User::findOrFail(1);

// Find multiple by IDs
$users = User::find([1, 2, 3]);
```

### 3. Retrieve All Records
```php
// Get all records as Collection
$users = User::all();

// Get all with conditions
$users = User::query()->where('status', '=', 'active')->get();
```

### 4. Update Record
```php
// Method 1: Find and update
$user = User::find(1);
$user->name = 'Jane Doe';
$user->save();

// Method 2: Mass update via query
User::query()->where('status', '=', 'pending')->update(['status' => 'active']);
```

### 5. Delete Record
```php
// Delete single record
$user = User::find(1);
$user->delete(); // Soft delete if enabled

// Delete via query
User::query()->where('status', '=', 'banned')->delete();

// Force delete (permanent)
$user->forceDelete();
```

### 6. Refresh Model
```php
// Reload model from database
$user = User::find(1);
$user->name = 'Changed';
$user->refresh(); // Discards changes, reloads from DB
```

### 7. Check if Model Exists
```php
$user = User::find(1);
if ($user->exists()) {
    echo "User exists in database";
}
```

### 8. Get Primary Key
```php
$user = User::find(1);
$id = $user->getKey(); // Returns primary key value
```

---

## Query Builder - WHERE Clauses

### 9. Basic WHERE
```php
// Simple equality
$users = User::query()->where('status', '=', 'active')->get();

// Can omit operator (defaults to =)
$users = User::query()->where('status', 'active')->get();

// Multiple conditions (AND)
$users = User::query()
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->get();
```

### 10. OR WHERE
```php
// OR conditions
$users = User::query()
    ->where('status', 'active')
    ->orWhere('role', 'admin')
    ->get();
```

### 11. AND WHERE
```php
// Explicit AND (same as chaining where)
$users = User::query()
    ->where('status', 'active')
    ->andWhere('verified', true)
    ->get();
```

### 12. Filter WHERE (Ignores Empty Values)
```php
// Only adds WHERE if value is not empty
$status = ''; // Empty value
$role = 'admin'; // Has value

$users = User::query()
    ->filterWhere('status', $status) // Ignored
    ->filterWhere('role', $role)     // Applied
    ->get();

// Also: andFilterWhere
$users = User::query()
    ->where('active', true)
    ->andFilterWhere('name', $searchTerm) // Only applied if $searchTerm not empty
    ->get();
```

### 13. WHERE IN
```php
// Single array of values
$users = User::query()->whereIn('id', [1, 2, 3])->get();

// With status check
$users = User::query()->whereIn('role', ['admin', 'moderator'])->get();
```

### 14. WHERE NOT IN
```php
// Exclude values
$users = User::query()->whereNotIn('status', ['banned', 'deleted'])->get();
```

### 15. WHERE BETWEEN
```php
// Range query
$users = User::query()->whereBetween('age', [18, 65])->get();

// Date range
$orders = Order::query()->whereBetween('created_at', ['2024-01-01', '2024-12-31'])->get();
```

### 16. WHERE NOT BETWEEN
```php
// Exclude range
$users = User::query()->whereNotBetween('age', [13, 17])->get();
```

### 17. WHERE NULL
```php
// Find NULL values
$users = User::query()->whereNull('deleted_at')->get();
```

### 18. WHERE NOT NULL
```php
// Find non-NULL values
$users = User::query()->whereNotNull('email_verified_at')->get();
```

### 19. WHERE Date Functions
```php
// WHERE DATE(column) = value
$users = User::query()->whereDate('created_at', '2024-11-12')->get();

// WHERE MONTH(column) = value
$users = User::query()->whereMonth('created_at', 11)->get();

// WHERE YEAR(column) = value
$users = User::query()->whereYear('created_at', 2024)->get();

// WHERE TIME(column) = value
$users = User::query()->whereTime('created_at', '14:30:00')->get();
```

---

## Query Builder - Advanced

### 20. SELECT Specific Columns
```php
// Select specific columns
$users = User::query()->select(['id', 'name', 'email'])->get();

// Select with alias
$users = User::query()->select(['id', 'name', 'email as user_email'])->get();
```

### 21. ORDER BY
```php
// Single column ascending
$users = User::query()->orderBy('name')->get();

// Single column descending
$users = User::query()->orderBy('created_at', 'DESC')->get();

// Multiple columns
$users = User::query()
    ->orderBy('status')
    ->orderBy('name', 'ASC')
    ->get();
```

### 22. LIMIT and OFFSET
```php
// Limit results
$users = User::query()->limit(10)->get();

// Offset and limit (pagination)
$users = User::query()->offset(20)->limit(10)->get();
```

### 23. First Record
```php
// Get first matching record
$user = User::query()->where('email', 'john@example.com')->first();

// Get first or fail
$user = User::query()->where('email', 'john@example.com')->firstOrFail();
```

### 24. Count Records
```php
// Count all
$total = User::query()->count();

// Count with conditions
$activeUsers = User::query()->where('status', 'active')->count();
```

### 25. Exists Check
```php
// Check if any records match
$hasActiveUsers = User::query()->where('status', 'active')->exists();

if ($hasActiveUsers) {
    echo "We have active users";
}
```

### 26. Chunk Processing (Memory Efficient)
```php
// Process large datasets in chunks
User::query()->chunk(100, function($users) {
    foreach ($users as $user) {
        // Process each user
        echo $user->name . "\n";
    }
});

// Stop processing by returning false
User::query()->chunk(100, function($users) {
    foreach ($users as $user) {
        if ($user->status === 'banned') {
            return false; // Stop processing
        }
    }
});
```

### 27. Find by SQL (Raw Query)
```php
// Execute custom SQL and get models
$users = User::findBySql("SELECT * FROM users WHERE age > ? AND status = ?", [18, 'active']);

// Returns Collection of User models
foreach ($users as $user) {
    echo $user->name;
}
```

### 28. With Trashed (Include Soft Deleted)
```php
// Include soft deleted records
$users = User::query()->withTrashed()->get();

// Only soft deleted records
$users = User::query()->onlyTrashed()->get();
```

---

## Query Result Formats

### 29. As Array (Instead of Models)
```php
// Get results as plain arrays
$users = User::query()
    ->select(['id', 'name', 'email'])
    ->asArray()
    ->get();

// Result: Collection of arrays
// [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']]
```

### 30. Scalar (Single Value)
```php
// Get single scalar value from first row
$email = User::query()
    ->where('id', 1)
    ->scalar('email');

// Returns: 'john@example.com'
```

### 31. Column (Array of Values)
```php
// Get array of single column values
$emails = User::query()
    ->where('status', 'active')
    ->column('email');

// Returns: ['john@example.com', 'jane@example.com', ...]
```

---

## Relationships

### 32. Has Many Relationship
```php
// In User model
public function posts() {
    return $this->hasMany(Post::class, 'user_id', 'id');
}

// Usage
$user = User::find(1);
$posts = $user->posts; // Get all posts for user

// With conditions
$posts = $user->posts()->where('status', 'published')->get();
```

### 33. Belongs To Relationship
```php
// In Post model
public function user() {
    return $this->belongsTo(User::class, 'user_id', 'id');
}

// Usage
$post = Post::find(1);
$author = $post->user; // Get the post's author
```

### 34. Has One Relationship
```php
// In User model
public function profile() {
    return $this->hasOne(Profile::class, 'user_id', 'id');
}

// Usage
$user = User::find(1);
$profile = $user->profile; // Get user's profile
```

### 35. Belongs To Many (Many-to-Many)
```php
// In User model
public function roles() {
    return $this->belongsToMany(
        Role::class,        // Related model
        'user_roles',       // Pivot table
        'user_id',          // Foreign key in pivot
        'role_id'           // Related key in pivot
    );
}

// Usage
$user = User::find(1);
$roles = $user->roles; // Get all roles for user

// Access pivot data
foreach ($user->roles as $role) {
    echo $role->name;
    echo $role->pivot->created_at; // Pivot table data
}
```

### 36. Eager Loading (Avoid N+1)
```php
// Load relationships efficiently
$users = User::query()->with('posts')->get();

// Multiple relationships
$users = User::query()->with(['posts', 'profile'])->get();

// Nested relationships
$users = User::query()->with('posts.comments')->get();
```

---

## Collections

The ORM returns `Collection` objects instead of arrays, providing powerful manipulation methods.

### 37. Map (Transform)
```php
$users = User::all();

// Transform each item
$names = $users->map(function($user) {
    return strtoupper($user->name);
});
```

### 38. Filter
```php
$users = User::all();

// Filter collection
$activeUsers = $users->filter(function($user) {
    return $user->status === 'active';
});
```

### 39. Pluck (Extract Column)
```php
$users = User::all();

// Get array of emails
$emails = $users->pluck('email');
// Returns: ['john@example.com', 'jane@example.com', ...]

// Pluck with keys
$emailsById = $users->pluck('email', 'id');
// Returns: [1 => 'john@example.com', 2 => 'jane@example.com']
```

### 40. Group By
```php
$users = User::all();

// Group by status
$grouped = $users->groupBy('status');
// Returns: ['active' => Collection, 'pending' => Collection]

// Access groups
foreach ($grouped['active'] as $user) {
    echo $user->name;
}
```

### 41. Sort By
```php
$users = User::all();

// Sort by name ascending
$sorted = $users->sortBy('name');

// Sort by multiple fields
$sorted = $users->sortBy('status')->sortBy('name');
```

### 42. First / Last
```php
$users = User::all();

// Get first item
$first = $users->first();

// Get last item
$last = $users->last();

// First with callback
$firstActive = $users->first(function($user) {
    return $user->status === 'active';
});
```

### 43. Chunk Collection
```php
$users = User::all();

// Split into chunks
$chunks = $users->chunk(10);

foreach ($chunks as $chunk) {
    // Process each chunk of 10 users
}
```

### 44. Sum / Avg / Max / Min
```php
$orders = Order::all();

// Sum column
$total = $orders->sum('amount');

// Average
$average = $orders->avg('amount');

// Maximum
$highest = $orders->max('amount');

// Minimum
$lowest = $orders->min('amount');
```

### 45. Contains
```php
$users = User::all();

// Check if collection contains value
$hasJohn = $users->contains('name', 'John Doe');

// Check with callback
$hasAdmin = $users->contains(function($user) {
    return $user->role === 'admin';
});
```

### 46. Unique
```php
$users = User::all();

// Get unique values by column
$uniqueStatuses = $users->unique('status');
```

### 47. Where on Collection
```php
$users = User::all();

// Filter collection (similar to filter)
$active = $users->where('status', 'active');

// Where in
$filtered = $users->whereIn('role', ['admin', 'moderator']);

// Where not in
$filtered = $users->whereNotIn('status', ['banned', 'deleted']);
```

### 48. Take / Slice
```php
$users = User::all();

// Take first N items
$first10 = $users->take(10);

// Take last N items
$last10 = $users->take(-10);

// Slice (offset, length)
$slice = $users->slice(5, 10); // Skip 5, take 10
```

### 49. Implode
```php
$users = User::all();

// Join column values
$names = $users->implode('name', ', ');
// Returns: "John, Jane, Bob"
```

### 50. To Array
```php
$users = User::all();

// Convert collection to array
$array = $users->toArray();

// Convert to JSON
$json = $users->toJson();
```

---

## Transactions

### 51. Transaction Method (Auto-Rollback)
```php
// Automatic commit/rollback
$result = User::transaction(function() {
    $user = new User(['name' => 'John']);
    $user->save();
    
    $profile = new Profile(['user_id' => $user->id]);
    $profile->save();
    
    return $user;
});

// If exception thrown, automatically rolls back
```

### 52. Manual Transaction Control
```php
$db = User::getConnection();

try {
    $db->beginTransaction();
    
    $user = new User(['name' => 'John']);
    $user->save();
    
    $profile = new Profile(['user_id' => $user->id]);
    $profile->save();
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

---

## Soft Deletes

### 53. Enable Soft Deletes
```php
class User extends Orm {
    protected static $softDelete = true; // Enable soft deletes
    protected static $deletedAtColumn = 'deleted_at'; // Column name
}

// Delete (soft)
$user = User::find(1);
$user->delete(); // Sets deleted_at timestamp

// Force delete (permanent)
$user->forceDelete(); // Actually removes from database
```

### 54. Restore Soft Deleted
```php
// Restore soft deleted record
$user = User::query()->withTrashed()->find(1);
$user->restore(); // Sets deleted_at to NULL
```

### 55. Query Soft Deleted Records
```php
// Exclude soft deleted (default)
$users = User::all();

// Include soft deleted
$users = User::query()->withTrashed()->get();

// Only soft deleted
$users = User::query()->onlyTrashed()->get();
```

---

## Timestamps

### 56. Auto Timestamps
```php
class User extends Orm {
    protected static $timestamps = true; // Enable auto timestamps
    protected static $createdAtColumn = 'created_at';
    protected static $updatedAtColumn = 'updated_at';
}

// Creates: sets created_at and updated_at
$user = new User(['name' => 'John']);
$user->save(); // Automatically sets timestamps

// Updates: only updates updated_at
$user->name = 'Jane';
$user->save(); // Updates updated_at
```

### 57. Disable Timestamps Temporarily
```php
// Disable for one operation
$user = User::find(1);
User::$timestamps = false;
$user->name = 'Changed';
$user->save(); // Won't update updated_at
User::$timestamps = true;
```

---

## Model Events

### 58. Before Save Event
```php
class User extends Orm {
    protected function beforeSave() {
        // Validate before saving
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false; // Cancel save
        }
        
        // Sanitize data
        $this->name = trim($this->name);
        
        return true; // Allow save
    }
}
```

### 59. After Save Event
```php
class User extends Orm {
    protected function afterSave() {
        // Clear cache
        Cache::forget("user.{$this->id}");
        
        // Log activity
        Log::info("User {$this->id} saved");
    }
}
```

### 60. Before Insert Event
```php
class User extends Orm {
    protected function beforeInsert() {
        // Set default values
        if (!isset($this->status)) {
            $this->status = 'pending';
        }
        
        // Generate unique code
        $this->verification_code = bin2hex(random_bytes(16));
        
        return true;
    }
}
```

### 61. After Insert Event
```php
class User extends Orm {
    protected function afterInsert() {
        // Send welcome email
        Mail::to($this->email)->send(new WelcomeEmail($this));
        
        // Create related records
        Profile::create(['user_id' => $this->id]);
    }
}
```

### 62. Before Update Event
```php
class User extends Orm {
    protected function beforeUpdate($changedAttributes) {
        // $changedAttributes contains only changed fields
        
        // Prevent status downgrade
        if (isset($changedAttributes['status'])) {
            $old = $this->getOriginal('status');
            if ($old === 'premium' && $changedAttributes['status'] !== 'premium') {
                return false; // Cancel update
            }
        }
        
        return true;
    }
}
```

### 63. After Update Event
```php
class User extends Orm {
    protected function afterUpdate($changedAttributes) {
        // Log changes
        foreach ($changedAttributes as $field => $newValue) {
            $oldValue = $this->getOriginal($field);
            AuditLog::create([
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue
            ]);
        }
    }
}
```

### 64. Before Delete Event
```php
class User extends Orm {
    protected function beforeDelete() {
        // Prevent deletion of admin users
        if ($this->role === 'admin') {
            return false; // Cancel deletion
        }
        
        // Backup data
        UserBackup::create(['data' => json_encode($this->attributes)]);
        
        return true;
    }
}
```

### 65. After Delete Event
```php
class User extends Orm {
    protected function afterDelete() {
        // Delete related files
        Storage::deleteDirectory("users/{$this->id}");
        
        // Notify admins
        Mail::to('admin@example.com')->send(new UserDeletedEmail($this));
    }
}
```

---

## Mass Assignment

### 66. Fillable Attributes
```php
class User extends Orm {
    protected $fillable = ['name', 'email', 'status'];
}

// Only fillable attributes can be mass assigned
$user = new User([
    'name' => 'John',
    'email' => 'john@example.com',
    'status' => 'active',
    'admin' => true // Ignored (not in fillable)
]);
```

### 67. Hidden Attributes (JSON)
```php
class User extends Orm {
    protected $hidden = ['password', 'secret_token'];
}

$user = User::find(1);
$json = $user->toJson(); // password and secret_token excluded
```

---

## Attribute Casting

### 68. Type Casting
```php
class User extends Orm {
    protected $casts = [
        'is_admin' => 'bool',
        'age' => 'int',
        'settings' => 'json',
        'created_at' => 'datetime'
    ];
}

$user = User::find(1);
$user->is_admin; // Returns boolean, not string
$user->settings; // Returns array, not JSON string
```

---

## Scopes

### 69. Local Scopes (Custom Query Methods)
```php
class User extends Orm {
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }
    
    public function scopeAdmins($query) {
        return $query->where('role', 'admin');
    }
}

// Usage
$activeUsers = User::query()->active()->get();
$admins = User::query()->admins()->get();

// Chain scopes
$activeAdmins = User::query()->active()->admins()->get();
```

---

## Utility Methods

### 70. Get Original Attributes
```php
$user = User::find(1);
$user->name = 'Changed';

// Get original value before changes
$original = $user->getOriginal('name');
$allOriginal = $user->getOriginal(); // All original attributes
```

### 71. Get Dirty Attributes (Changed)
```php
$user = User::find(1);
$user->name = 'New Name';
$user->email = 'new@example.com';

// Get only changed attributes
$dirty = $user->getDirty();
// Returns: ['name' => 'New Name', 'email' => 'new@example.com']

// Check if attribute is dirty
if ($user->isDirty('name')) {
    echo "Name was changed";
}
```

### 72. Fill Attributes
```php
$user = User::find(1);

// Mass assign attributes
$user->fill([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

$user->save();
```

### 73. To Array / To JSON
```php
$user = User::find(1);

// Convert to array
$array = $user->toArray();

// Convert to JSON
$json = $user->toJson();

// Direct JSON encoding
echo json_encode($user);
```

### 74. Get Table Name
```php
// Get model's table name
$table = User::getTable(); // Returns 'users'
```

### 75. Get Connection
```php
// Get database connection
$db = User::getConnection();

// Use for raw queries
$result = $db->query("SELECT * FROM users WHERE id = ?", [1]);
```

---

## Summary

The EasyAPP ORM provides **75+ features** covering:

- ✅ Full CRUD operations
- ✅ Powerful query builder with 15+ WHERE clause types
- ✅ Relationship support (hasMany, belongsTo, hasOne, belongsToMany)
- ✅ Collection class with 30+ manipulation methods
- ✅ Transaction support with auto-rollback
- ✅ Soft deletes and timestamps
- ✅ Comprehensive event system (before/after save, insert, update, delete)
- ✅ Mass assignment protection
- ✅ Attribute casting
- ✅ Query scopes
- ✅ Memory-efficient chunk processing
- ✅ Multiple result formats (models, arrays, scalars)
- ✅ Eager loading to prevent N+1 queries

**Perfect for building modern PHP applications!** 🚀
