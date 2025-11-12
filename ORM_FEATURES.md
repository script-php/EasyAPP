# ORM Advanced Features Guide

## Table of Contents
1. [Relationships](#relationships)
2. [Soft Deletes](#soft-deletes)
3. [Pagination](#pagination)
4. [Query Helpers](#query-helpers)
5. [Events & Hooks](#events--hooks)
6. [Advanced Queries](#advanced-queries)
7. [Bulk Operations](#bulk-operations)

---

## Relationships

### One-to-Many (hasMany)

**Database Structure:**
```sql
-- users table
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);

-- posts table
CREATE TABLE posts (
    id INT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255),
    content TEXT
);
```

**Model Definition:**
```php
class User extends Orm {
    protected static $table = 'users';
    
    // Define relationship
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

class Post extends Orm {
    protected static $table = 'posts';
    protected static $fillable = ['user_id', 'title', 'content'];
}
```

**Usage:**
```php
// Lazy loading
$user = User::find(1);
$posts = $user->posts()->get();

// Eager loading (prevents N+1 queries)
$users = User::with('posts')->get();
foreach ($users as $user) {
    foreach ($user->posts as $post) {
        echo $post->title;
    }
}

// Query the relationship
$popularPosts = $user->posts()
    ->where('views', '>', 1000)
    ->orderBy('created_at', 'DESC')
    ->get();
```

### Belongs-To (belongsTo)

**Model Definition:**
```php
class Post extends Orm {
    protected static $table = 'posts';
    
    // Define inverse relationship
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

**Usage:**
```php
$post = Post::find(1);
$author = $post->author(); // Returns User instance

echo "Written by: " . $author->name;
```

### One-to-One (hasOne)

**Database Structure:**
```sql
CREATE TABLE profiles (
    id INT PRIMARY KEY,
    user_id INT UNIQUE,
    bio TEXT,
    avatar VARCHAR(255)
);
```

**Model Definition:**
```php
class User extends Orm {
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Orm {
    protected static $fillable = ['user_id', 'bio', 'avatar'];
}
```

**Usage:**
```php
$user = User::find(1);
$profile = $user->profile();

if ($profile) {
    echo $profile->bio;
}
```

### Many-to-Many (belongsToMany)

**Database Structure:**
```sql
-- roles table
CREATE TABLE roles (
    id INT PRIMARY KEY,
    name VARCHAR(100)
);

-- user_role pivot table
CREATE TABLE role_user (
    user_id INT,
    role_id INT,
    PRIMARY KEY (user_id, role_id)
);
```

**Model Definition:**
```php
class User extends Orm {
    public function roles() {
        return $this->belongsToMany(Role::class, 'role_user')->get();
    }
}

class Role extends Orm {
    protected static $table = 'roles';
    
    public function users() {
        return $this->belongsToMany(User::class, 'role_user')->get();
    }
}
```

**Usage:**
```php
$user = User::find(1);
$roles = $user->roles();

foreach ($roles as $role) {
    echo $role->name;
}

// Check if user has specific role
$isAdmin = in_array('admin', array_column($roles, 'name'));
```

---

## Soft Deletes

Soft deletes mark records as deleted without removing them from the database.

**Migration:**
```sql
ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL;
```

**Model Definition:**
```php
class User extends Orm {
    protected static $softDelete = true; // Enable soft deletes
    protected static $deletedAtColumn = 'deleted_at'; // Default column name
}
```

**Usage:**
```php
// Soft delete (sets deleted_at timestamp)
$user = User::find(1);
$user->delete();

// Get only non-deleted records (default behavior)
$activeUsers = User::all();

// Include soft deleted records
$allUsers = User::withTrashed()->get();

// Get only soft deleted records
$deletedUsers = User::onlyTrashed()->get();

// Restore soft deleted record
$user = User::onlyTrashed()->where('id', 1)->first();
$user->restore();

// Permanently delete (hard delete)
$user->forceDelete();

// Soft delete via query
User::where('status', 0)->delete(); // All matching records soft deleted
```

---

## Pagination

**Basic Pagination:**
```php
// Paginate with 15 items per page (default)
$users = User::paginate();

// Custom per page
$users = User::paginate(20);

// With specific page
$users = User::paginate(10, 3); // 10 per page, page 3

// With filters
$users = User::where('status', 1)
    ->orderBy('created_at', 'DESC')
    ->paginate(25);
```

**Pagination Object:**
```php
$pagination = User::paginate(10);

echo $pagination->total;         // Total records
echo $pagination->per_page;      // Items per page
echo $pagination->current_page;  // Current page number
echo $pagination->last_page;     // Last page number
echo $pagination->from;          // First item number
echo $pagination->to;            // Last item number
echo $pagination->next_page;     // Next page number (or null)
echo $pagination->prev_page;     // Previous page number (or null)

if ($pagination->has_more_pages) {
    echo "More pages available";
}

// Access the data
foreach ($pagination->data as $user) {
    echo $user->name;
}
```

**View Example:**
```php
// In controller
$users = User::paginate(10);
$this->response->setOutput($this->load->view('users/index', [
    'pagination' => $users
]));
```

```html
<!-- In view -->
<div class="users">
    <?php foreach ($pagination->data as $user): ?>
        <div class="user"><?= $user->name ?></div>
    <?php endforeach; ?>
</div>

<div class="pagination">
    <?php if ($pagination->prev_page): ?>
        <a href="?page=<?= $pagination->prev_page ?>">Previous</a>
    <?php endif; ?>
    
    <span>Page <?= $pagination->current_page ?> of <?= $pagination->last_page ?></span>
    
    <?php if ($pagination->next_page): ?>
        <a href="?page=<?= $pagination->next_page ?>">Next</a>
    <?php endif; ?>
</div>
```

---

## Query Helpers

### exists()
Check if query returns any results:
```php
if (User::where('email', 'test@example.com')->exists()) {
    echo "Email already exists";
}
```

### firstOrCreate()
Find or create if not found:
```php
// Find by email, create if not exists
$user = User::firstOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe', 'status' => 1]
);
```

### updateOrCreate()
Update existing or create new:
```php
// Update if exists, create if not
$user = User::updateOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe Updated', 'status' => 1]
);
```

### findOrNew()
Find by ID or return new instance:
```php
$user = User::findOrNew(999);

if ($user->exists) {
    echo "User found";
} else {
    echo "New user instance";
    $user->name = 'New User';
    $user->save();
}
```

### pluck()
Get single column values:
```php
// Get array of names
$names = User::where('status', 1)->pluck('name');
// Result: ['John', 'Jane', 'Bob']

// Get key-value pairs
$userEmails = User::pluck('email', 'id');
// Result: [1 => 'john@example.com', 2 => 'jane@example.com']
```

### increment() / decrement()
Update numeric columns:
```php
// Increment view count
Post::where('id', 1)->increment('views');

// Increment by specific amount
Post::where('id', 1)->increment('views', 5);

// Decrement
Product::where('id', 1)->decrement('stock');
Product::where('id', 1)->decrement('stock', 10);
```

### Bulk Insert
Insert multiple records at once:
```php
User::insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com'],
]);
```

---

## Events & Hooks

Override these methods in your model to add custom logic:

**Available Events:**
- `creating` - Before creating new record
- `created` - After creating new record
- `updating` - Before updating record
- `updated` - After updating record
- `saving` - Before saving (create or update)
- `saved` - After saving (create or update)
- `deleting` - Before deleting record
- `deleted` - After deleting record

**Example:**
```php
class User extends Orm {
    protected static $fillable = ['name', 'email', 'password'];
    
    // Before creating
    protected function creating() {
        // Generate unique slug
        $this->attributes['slug'] = $this->generateSlug($this->attributes['name']);
    }
    
    // After creating
    protected function created() {
        // Send welcome email
        // mail($this->email, 'Welcome', 'Welcome to our site!');
    }
    
    // Before saving (create or update)
    protected function saving() {
        // Validate email
        if (!filter_var($this->attributes['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }
    }
    
    // After updating
    protected function updated() {
        // Log the update
        // Logger::log("User {$this->id} was updated");
    }
    
    // Before deleting
    protected function deleting() {
        // Delete related records
        // $this->posts()->delete();
    }
    
    private function generateSlug($name) {
        return strtolower(str_replace(' ', '-', $name));
    }
}
```

**Usage:**
```php
// Events fire automatically
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save(); // Fires: creating, saving, created, saved

$user->name = 'Jane Doe';
$user->save(); // Fires: updating, saving, updated, saved

$user->delete(); // Fires: deleting, deleted
```

---

## Advanced Queries

### GROUP BY and HAVING
```php
// Group by and count
$usersByRole = User::select('role', 'COUNT(*) as total')
    ->groupBy('role')
    ->get();

// With HAVING clause
$activeRoles = User::select('role', 'COUNT(*) as total')
    ->where('status', 1)
    ->groupBy('role')
    ->having('total', '>', 5)
    ->get();
```

### Complex WHERE Conditions
```php
// Multiple conditions
$users = User::where('status', 1)
    ->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// WHERE IN
$users = User::whereIn('id', [1, 2, 3, 4, 5])->get();

// WHERE NULL / NOT NULL
$unverified = User::whereNull('email_verified_at')->get();
$verified = User::whereNotNull('email_verified_at')->get();
```

### Joins with Aggregates
```php
// Count user posts
$usersWithPostCount = User::select('users.*', 'COUNT(posts.id) as post_count')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->groupBy('users.id')
    ->having('post_count', '>', 10)
    ->get();
```

---

## Bulk Operations

### Bulk Insert
```php
// Insert 1000 records efficiently
$records = [];
for ($i = 1; $i <= 1000; $i++) {
    $records[] = [
        'name' => "User {$i}",
        'email' => "user{$i}@example.com",
        'status' => 1
    ];
}

User::insert($records);
```

### Bulk Update
```php
// Update multiple records
User::where('status', 0)->update(['status' => 1]);

// Update with conditions
User::where('last_login', '<', '2024-01-01')
    ->update(['status' => 0]);
```

### Bulk Delete
```php
// Delete multiple records
User::where('status', 0)->delete();

// Soft delete multiple
User::where('last_login', '<', '2023-01-01')->delete();

// Force delete multiple (even with soft deletes)
User::where('id', '<', 100)->withTrashed()->forceDelete();
```

---

## Complete Real-World Example

```php
class Post extends Orm {
    protected static $table = 'posts';
    protected static $softDelete = true;
    
    protected static $fillable = [
        'user_id', 'title', 'slug', 'content', 'status', 'views'
    ];
    
    protected static $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'views' => 'int',
        'published_at' => 'datetime'
    ];
    
    // Relationships
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function comments() {
        return $this->hasMany(Comment::class);
    }
    
    public function tags() {
        return $this->belongsToMany(Tag::class, 'post_tag')->get();
    }
    
    // Scopes
    public static function published() {
        return static::query()->where('status', 'published');
    }
    
    public static function popular() {
        return static::query()->where('views', '>', 1000);
    }
    
    // Events
    protected function creating() {
        $this->attributes['slug'] = $this->generateSlug($this->attributes['title']);
    }
    
    protected function created() {
        // Clear cache, send notifications, etc.
    }
    
    // Helpers
    private function generateSlug($title) {
        return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $title));
    }
}

// Usage in Controller
class ControllerBlog extends Controller {
    
    public function index() {
        $posts = Post::published()
            ->with('author')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);
        
        $this->response->setOutput($this->load->view('blog/index', [
            'pagination' => $posts
        ]));
    }
    
    public function show() {
        $id = $this->request->get['id'];
        
        $post = Post::with('author', 'comments')
            ->findOrFail($id);
        
        // Increment views
        Post::where('id', $id)->increment('views');
        
        $this->response->setOutput($this->load->view('blog/show', [
            'post' => $post
        ]));
    }
    
    public function popular() {
        $posts = Post::published()
            ->popular()
            ->orderBy('views', 'DESC')
            ->limit(10)
            ->get();
        
        $this->response->setOutput($this->load->view('blog/popular', [
            'posts' => $posts
        ]));
    }
    
}
```

---

## Performance Tips

1. **Use Eager Loading** to prevent N+1 queries:
   ```php
   // BAD - N+1 queries
   $users = User::all();
   foreach ($users as $user) {
       echo $user->posts()->count(); // Query for each user
   }
   
   // GOOD - 2 queries total
   $users = User::with('posts')->get();
   foreach ($users as $user) {
       echo count($user->posts); // No additional query
   }
   ```

2. **Use Bulk Operations** for multiple records:
   ```php
   // BAD
   foreach ($data as $item) {
       User::create($item); // Separate query for each
   }
   
   // GOOD
   User::insert($data); // Single query
   ```

3. **Select Only Needed Columns**:
   ```php
   // BAD
   $users = User::all(); // Selects all columns
   
   // GOOD
   $users = User::select('id', 'name', 'email')->get();
   ```

4. **Use Pagination** for large datasets:
   ```php
   // BAD
   $users = User::all(); // Loads all records into memory
   
   // GOOD
   $users = User::paginate(50); // Loads 50 at a time
   ```

---

**EasyAPP ORM** - Complete, Powerful, Production-Ready 🚀
