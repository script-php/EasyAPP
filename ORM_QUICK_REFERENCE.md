# ORM Quick Reference

## Basic CRUD Operations

```php
// CREATE
$user = new User();
$user->name = 'John';
$user->save();

$user = User::create(['name' => 'John', 'email' => 'john@example.com']);

// READ
$user = User::find(1);
$users = User::all();
$user = User::where('email', 'john@example.com')->first();

// UPDATE
$user = User::find(1);
$user->name = 'Jane';
$user->save();

User::where('status', 0)->update(['status' => 1]);

// DELETE
$user = User::find(1);
$user->delete();

User::where('status', 0)->delete();
```

## Query Builder

```php
// WHERE
User::where('status', 1)->get();
User::where('age', '>', 18)->get();
User::where('role', 'admin')->orWhere('role', 'mod')->get();

// WHERE IN
User::whereIn('id', [1, 2, 3])->get();

// WHERE NULL
User::whereNull('deleted_at')->get();
User::whereNotNull('email_verified_at')->get();

// SELECT
User::select('id', 'name', 'email')->get();

// ORDER BY
User::orderBy('created_at', 'DESC')->get();

// LIMIT & OFFSET
User::limit(10)->offset(20)->get();

// COUNT
$count = User::where('status', 1)->count();

// JOINS
User::join('profiles', 'users.id', '=', 'profiles.user_id')->get();
User::leftJoin('orders', 'users.id', '=', 'orders.user_id')->get();

// GROUP BY & HAVING
User::select('role', 'COUNT(*) as total')
    ->groupBy('role')
    ->having('total', '>', 5)
    ->get();
```

## Relationships

```php
// Define in Model
class User extends Orm {
    public function posts() {
        return $this->hasMany(Post::class);
    }
    
    public function profile() {
        return $this->hasOne(Profile::class);
    }
}

class Post extends Orm {
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function tags() {
        return $this->belongsToMany(Tag::class, 'post_tag')->get();
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts()->get();

// Eager Loading
$users = User::with('posts')->get();
```

## Query Helpers

```php
// EXISTS
if (User::where('email', 'test@example.com')->exists()) { }

// FIRST OR CREATE
$user = User::firstOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe']
);

// UPDATE OR CREATE
$user = User::updateOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Updated']
);

// FIND OR NEW
$user = User::findOrNew(999);

// PLUCK
$names = User::pluck('name');
$emails = User::pluck('email', 'id');

// INCREMENT / DECREMENT
Post::where('id', 1)->increment('views');
Post::where('id', 1)->decrement('stock', 5);

// BULK INSERT
User::insert([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
]);
```

## Pagination

```php
// Basic pagination
$users = User::paginate(15);

// With filters
$users = User::where('status', 1)->paginate(20);

// Access pagination data
foreach ($users->data as $user) {
    echo $user->name;
}

echo $users->total;
echo $users->current_page;
echo $users->last_page;
```

## Soft Deletes

```php
// Enable in Model
class User extends Orm {
    protected static $softDelete = true;
}

// Usage
$user->delete();                    // Soft delete
$users = User::all();              // Excludes soft deleted
$users = User::withTrashed()->get(); // Include soft deleted
$users = User::onlyTrashed()->get(); // Only soft deleted
$user->restore();                   // Restore soft deleted
$user->forceDelete();              // Permanent delete
```

## Events & Hooks

```php
class User extends Orm {
    protected function creating() {
        // Before creating
    }
    
    protected function created() {
        // After creating
    }
    
    protected function updating() {
        // Before updating
    }
    
    protected function updated() {
        // After updating
    }
    
    protected function saving() {
        // Before saving (create or update)
    }
    
    protected function saved() {
        // After saving (create or update)
    }
    
    protected function deleting() {
        // Before deleting
    }
    
    protected function deleted() {
        // After deleting
    }
}
```

## Model Configuration

```php
class User extends Orm {
    // Table name (optional - auto-detected)
    protected static $table = 'users';
    
    // Primary key (default: 'id')
    protected static $primaryKey = 'id';
    
    // Timestamps (default: true)
    protected static $timestamps = true;
    protected static $createdAtColumn = 'created_at';
    protected static $updatedAtColumn = 'updated_at';
    
    // Soft deletes (default: false)
    protected static $softDelete = false;
    protected static $deletedAtColumn = 'deleted_at';
    
    // Mass assignment (whitelist)
    protected static $fillable = ['name', 'email'];
    
    // Mass assignment (blacklist)
    protected static $guarded = ['id', 'is_admin'];
    
    // Hidden from JSON/Array
    protected static $hidden = ['password'];
    
    // Attribute casting
    protected static $casts = [
        'id' => 'int',
        'is_active' => 'bool',
        'settings' => 'json',
        'created_at' => 'datetime'
    ];
}
```

## Accessors & Mutators

```php
class User extends Orm {
    // Accessor (getter)
    public function getFullNameAttribute() {
        return $this->attributes['first_name'] . ' ' . $this->attributes['last_name'];
    }
    
    // Mutator (setter)
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }
}

// Usage
echo $user->full_name; // Calls getFullNameAttribute()
$user->password = 'secret'; // Calls setPasswordAttribute()
```

## Scopes

```php
class User extends Orm {
    public static function active() {
        return static::query()->where('status', 1);
    }
    
    public static function admins() {
        return static::query()->where('role', 'admin');
    }
}

// Usage
$activeUsers = User::active()->get();
$activeAdmins = User::active()->where('role', 'admin')->get();
```

## Helper Functions

```php
// Get database connection
$db = db();

// Execute raw query
$result = $db->query("SELECT * FROM users WHERE id = ?", [1]);
```

## Cheat Sheet Summary

| Operation | Code |
|-----------|------|
| Find by ID | `User::find(1)` |
| Get all | `User::all()` |
| Create | `User::create([...])` |
| Update | `$user->save()` |
| Delete | `$user->delete()` |
| Where | `User::where('status', 1)->get()` |
| Order | `User::orderBy('name')->get()` |
| Limit | `User::limit(10)->get()` |
| Count | `User::count()` |
| Paginate | `User::paginate(15)` |
| With relations | `User::with('posts')->get()` |
| Soft delete | `$user->delete()` (with softDelete=true) |
| Restore | `$user->restore()` |
| Increment | `Post::where('id',1)->increment('views')` |
| Exists | `User::where(...)->exists()` |
| Pluck | `User::pluck('email')` |
| Bulk insert | `User::insert([...])` |

---

**EasyAPP ORM** - Complete & Production-Ready 🚀
