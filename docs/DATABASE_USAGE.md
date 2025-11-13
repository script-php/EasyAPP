# Database Usage Examples

## Framework Installation Without Database

The framework now works perfectly without database credentials. During initial setup:

1. **No database required**: The framework will start without errors
2. **Database is optional**: Only connects when credentials are provided
3. **Lazy loading**: Connection is established only when needed

## Configuration Examples

### 1. No Database (Initial Setup)
```env
# .env file - leave database settings empty or don't include them
DB_HOSTNAME=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

### 2. With Default Database
```env
# .env file - framework will auto-connect
DB_HOSTNAME=localhost
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret
DB_PORT=3306
```

## Usage Examples

### 1. Using Default Database Connection
```php
// If database credentials are configured, this will work
try {
    $db = db(); // Get default connection from registry
    $result = $db->query("SELECT * FROM users WHERE id = ?", [1]);
    echo "User: " . $result->row['name'];
} catch (Exception $e) {
    echo "Database not available: " . $e->getMessage();
}
```

### 2. Creating New Database Connections
```php
// Connect to different database
$secondDb = createDbConnection(
    'mysql',           // driver
    'external-server', // hostname
    'external_db',     // database
    'user',           // username
    'pass',           // password
    '3306',           // port
    'utf8',           // encoding
    []                // options
);

$result = $secondDb->query("SELECT * FROM external_table");
```

### 3. Multiple Database Connections
```php
// Main application database
$mainDb = createDbConnection('mysql', 'localhost', 'app_db', 'app_user', 'app_pass');

// Analytics database
$analyticsDb = createDbConnection('mysql', 'analytics-server', 'analytics', 'analytics_user', 'analytics_pass');

// Logging database
$logDb = createDbConnection('mysql', 'log-server', 'logs', 'log_user', 'log_pass');

// Use them independently
$users = $mainDb->query("SELECT * FROM users");
$stats = $analyticsDb->query("SELECT * FROM user_stats");
$logs = $logDb->query("INSERT INTO access_log (user_id, action) VALUES (?, ?)", [1, 'login']);
```

### 4. Conditional Database Usage
```php
// Check if default database is available
$registry = System\Framework\Registry::getInstance();
if ($registry->has('db')) {
    $db = $registry->get('db');
    // Use database
} else {
    // Work without database (use file storage, APIs, etc.)
    echo "Working in database-free mode";
}
```

## Benefits

✅ **No setup errors**: Framework starts without database  
✅ **Flexible connections**: Connect to multiple databases  
✅ **Lazy loading**: Only connects when needed  
✅ **Easy migration**: Gradual database setup  
✅ **Development friendly**: Work offline or with different databases  