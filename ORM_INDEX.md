# 📚 ORM Documentation Index

Welcome to the complete ORM documentation for EasyAPP Framework!

## 🚀 Quick Start

1. **New to ORM?** Start here → [`ORM_USAGE.md`](ORM_USAGE.md)
2. **Need quick reference?** → [`ORM_QUICK_REFERENCE.md`](ORM_QUICK_REFERENCE.md)
3. **Migrating from traditional models?** → [`ORM_MIGRATION_GUIDE.md`](ORM_MIGRATION_GUIDE.md)

## 📖 Documentation Files

### 1. [ORM_USAGE.md](ORM_USAGE.md)
**Complete usage guide for beginners**
- Basic CRUD operations
- Query builder examples
- Model configuration
- Accessors & Mutators
- Complete controller examples
- Tips & best practices

**Start with this file if you're new to the ORM.**

### 2. [ORM_FEATURES.md](ORM_FEATURES.md)
**Advanced features in-depth**
- Relationships (hasMany, belongsTo, hasOne, belongsToMany)
- Soft Deletes
- Pagination
- Query Helpers (firstOrCreate, pluck, increment, etc.)
- Events & Hooks
- Advanced Queries (groupBy, having)
- Bulk Operations
- Real-world examples

**Read this after mastering the basics.**

### 3. [ORM_QUICK_REFERENCE.md](ORM_QUICK_REFERENCE.md)
**Quick lookup cheat sheet**
- All methods at a glance
- Code snippets for common tasks
- Configuration options
- Comparison table
- Perfect for daily reference

**Keep this open while coding.**

### 4. [ORM_MIGRATION_GUIDE.md](ORM_MIGRATION_GUIDE.md)
**Step-by-step migration from traditional models**
- Before/After comparisons
- Gradual migration strategy
- Code reduction metrics
- Troubleshooting guide
- Compatibility notes

**Use this when converting existing code.**

### 5. [ORM_SUMMARY.md](ORM_SUMMARY.md)
**Project overview and implementation summary**
- Complete feature list
- Files created/modified
- Code statistics
- Production readiness checklist
- Performance tips

**Read this to understand what you got.**

## 📂 Example Models

Real-world model implementations:

### [app/model/User.php](app/model/User.php)
Basic user model with:
- Authentication fields
- Password hashing (mutator)
- Relationships (posts, comments, profile, roles)
- Active users scope

### [app/model/Post.php](app/model/Post.php)
Blog post model with:
- Soft deletes enabled
- Relationships (author, comments, tags)
- Scopes (published, popular, recent)
- Events (auto-generate slug)
- Helper methods (getExcerpt, getReadingTime)

### [app/model/Comment.php](app/model/Comment.php)
Comment model with:
- Nested comments (parent/replies)
- Status management
- Multiple relationships
- Approval workflow

## 🗄️ Database Schema

### [database_schema_orm_examples.sql](database_schema_orm_examples.sql)
Complete database schema including:
- Users, Posts, Comments tables
- Profiles (one-to-one)
- Tags and Post-Tag pivot (many-to-many)
- Roles and User-Role pivot (many-to-many)
- Sample data included

**Import this file to get started quickly.**

## 🎯 Learning Path

### For Beginners
1. Read `ORM_USAGE.md` (sections: Introduction, Quick Start, Basic Operations)
2. Import `database_schema_orm_examples.sql`
3. Try examples in `ORM_QUICK_REFERENCE.md`
4. Experiment with example models

### For Intermediate Users
1. Skim `ORM_USAGE.md` for basics
2. Deep dive into `ORM_FEATURES.md` (Relationships, Query Helpers)
3. Study example models (Post.php, Comment.php)
4. Build a small CRUD application

### For Advanced Users
1. Review `ORM_FEATURES.md` (Events, Advanced Queries, Bulk Operations)
2. Use `ORM_QUICK_REFERENCE.md` as reference
3. Optimize queries with eager loading
4. Implement complex relationships

### For Migration Projects
1. Read `ORM_MIGRATION_GUIDE.md` completely
2. Plan migration strategy (gradual vs complete)
3. Start with new features first
4. Migrate high-traffic areas
5. Test thoroughly before deploying

## 🔍 Find What You Need

### I want to...

**Create a new record**
→ See "Creating Records" in `ORM_USAGE.md`
→ Quick: `User::create(['name' => 'John'])`

**Query the database**
→ See "Query Builder" in `ORM_USAGE.md`
→ Quick: `User::where('status', 1)->get()`

**Setup relationships**
→ See "Relationships" in `ORM_FEATURES.md`
→ Example: `app/model/Post.php`

**Add pagination**
→ See "Pagination" in `ORM_FEATURES.md`
→ Quick: `User::paginate(20)`

**Use soft deletes**
→ See "Soft Deletes" in `ORM_FEATURES.md`
→ Quick: Set `protected static $softDelete = true;`

**Hook into save events**
→ See "Events & Hooks" in `ORM_FEATURES.md`
→ Example: `app/model/Post.php` (creating, created methods)

**Migrate existing code**
→ Read `ORM_MIGRATION_GUIDE.md`
→ See before/after examples

**Quick reference**
→ Use `ORM_QUICK_REFERENCE.md`
→ All methods in one place

## 📊 Feature Checklist

✅ Basic CRUD (Create, Read, Update, Delete)  
✅ Query Builder (where, orderBy, limit, join, etc.)  
✅ Relationships (hasMany, belongsTo, hasOne, belongsToMany)  
✅ Eager Loading (with() to prevent N+1 queries)  
✅ Soft Deletes (delete, restore, forceDelete)  
✅ Pagination (paginate() with metadata)  
✅ Query Helpers (firstOrCreate, pluck, increment, etc.)  
✅ Events & Hooks (creating, created, updating, etc.)  
✅ Accessors & Mutators (transform on get/set)  
✅ Attribute Casting (int, bool, json, datetime)  
✅ Mass Assignment Protection (fillable/guarded)  
✅ Hidden Attributes (hide from JSON/Array)  
✅ Timestamps (created_at/updated_at)  
✅ Custom Scopes (reusable query methods)  
✅ Bulk Operations (insert multiple records)  
✅ Advanced Queries (groupBy, having)  

## 🎓 Code Examples

### Quick Example 1: Simple CRUD
```php
// Create
$user = User::create(['name' => 'John', 'email' => 'john@example.com']);

// Read
$user = User::find(1);

// Update
$user->name = 'Jane';
$user->save();

// Delete
$user->delete();
```

### Quick Example 2: Relationships
```php
// Get user's posts
$user = User::find(1);
$posts = $user->posts()->get();

// Eager loading (prevent N+1)
$users = User::with('posts', 'comments')->get();
```

### Quick Example 3: Advanced Query
```php
$posts = Post::where('status', 'published')
    ->where('views', '>', 100)
    ->with('author')
    ->orderBy('created_at', 'DESC')
    ->paginate(20);
```

## 🛠️ File Structure

```
iziapp/
├── system/
│   ├── Framework/
│   │   └── Orm.php                    [Core ORM class]
│   └── Helper.php                     [Contains db() helper]
├── app/
│   └── model/
│       ├── User.php                   [Example: User model]
│       ├── Post.php                   [Example: Blog post model]
│       └── Comment.php                [Example: Comment model]
├── ORM_USAGE.md                       [Complete usage guide]
├── ORM_FEATURES.md                    [Advanced features guide]
├── ORM_QUICK_REFERENCE.md             [Quick reference cheat sheet]
├── ORM_MIGRATION_GUIDE.md             [Migration from traditional models]
├── ORM_SUMMARY.md                     [Implementation summary]
└── database_schema_orm_examples.sql   [Database schema + sample data]
```

## 💡 Tips for Success

1. **Start Small** - Begin with one model and expand
2. **Use Eager Loading** - Prevent N+1 queries with `with()`
3. **Define Fillable** - Always set `$fillable` for security
4. **Hide Sensitive Data** - Use `$hidden` for passwords, tokens
5. **Cast Attributes** - Use `$casts` for proper data types
6. **Create Scopes** - Reusable query methods save time
7. **Use Events** - Hook into save/delete for automation
8. **Read Examples** - Study the example models
9. **Keep Reference Handy** - Use Quick Reference while coding
10. **Test Thoroughly** - Verify queries in development first

## 🆘 Getting Help

### Common Issues

**"No database connection"**
→ Configure database credentials in `.env` or `config.php`

**"Table not found"**
→ Set `protected static $table = 'table_name';` in your model

**"Mass assignment exception"**
→ Define `protected static $fillable = ['column1', 'column2'];`

**"Relationship not working"**
→ Check foreign key names and relationship method definitions

### Where to Look

- Syntax help → `ORM_QUICK_REFERENCE.md`
- How-to guides → `ORM_USAGE.md`
- Advanced topics → `ORM_FEATURES.md`
- Migration help → `ORM_MIGRATION_GUIDE.md`
- Examples → `app/model/*.php`

## 🎉 You're Ready!

You now have access to a complete, production-ready ORM system. Pick a documentation file based on your needs and start building!

**Happy coding!** 🚀

---

**EasyAPP ORM** - Simple. Powerful. Production-Ready.
