# Views

Views are responsible for presenting data to users. They contain the HTML, CSS, and presentation logic for your application.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Creating Views](#creating-views)
3. [Loading Views](#loading-views)
4. [Passing Data to Views](#passing-data-to-views)
5. [Template Inheritance](#template-inheritance)
6. [Partial Views](#partial-views)
7. [Best Practices](#best-practices)
8. [Security Considerations](#security-considerations)

---

## Introduction

Views in EasyAPP use native PHP templates. This provides maximum flexibility and performance without the overhead of a template engine.

### View Location

Views are stored in the `app/view/` directory:

```
app/view/
├── base.html
├── home/
│   └── index.html
├── user/
│   ├── list.html
│   ├── view.html
│   └── form.html
└── partials/
    ├── header.html
    └── footer.html
```

---

## Creating Views

### Basic View Template

**File:** `app/view/home/index.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($heading); ?></h1>
    
    <div class="content">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
    
    <script src="/assets/js/app.js"></script>
</body>
</html>
```

### View with PHP Logic

```html
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
</head>
<body>
    <h1>User List</h1>
    
    <?php if (!empty($users)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <a href="/user/view?id=<?php echo $user['id']; ?>">View</a>
                            <a href="/user/edit?id=<?php echo $user['id']; ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</body>
</html>
```

---

## Loading Views

### From Controller

```php
class ControllerUser extends Controller {
    
    public function index() {
        $data = [];
        $data['title'] = 'User List';
        
        // Load model and get data
        $userModel = $this->load->model('user');
        $data['users'] = $userModel->getAll();
        
        // Load view and set as response
        $this->response->setOutput(
            $this->load->view('user/list.html', $data)
        );
    }
}
```

### From Model

```php
class ModelReport extends Model {
    
    public function generateReport($data) {
        // Models can load views for generating reports, emails, etc.
        return $this->load->view('reports/monthly.html', $data);
    }
    
    public function getUserStats($userId) {
        // Models can load other models
        $userModel = $this->load->model('user');
        return $userModel->getStats($userId);
    }
}
```

### Nested View Loading

```php
public function index() {
    $data = [];
    $data['title'] = 'Dashboard';
    
    // Load sub-views
    $data['sidebar'] = $this->load->view('partials/sidebar.html', [
        'user' => $this->getUser()
    ]);
    
    $data['content'] = $this->load->view('dashboard/widgets.html', [
        'stats' => $this->getStats()
    ]);
    
    // Load main view with sub-views
    $this->response->setOutput(
        $this->load->view('dashboard/index.html', $data)
    );
}
```

---

## Passing Data to Views

### Basic Data Passing

```php
public function view() {
    $data = [];
    $data['title'] = 'Page Title';
    $data['content'] = 'Page content...';
    $data['number'] = 42;
    $data['active'] = true;
    
    $this->response->setOutput(
        $this->load->view('page.html', $data)
    );
}
```

### Passing Complex Data

```php
public function dashboard() {
    $data = [];
    
    // Array of data
    $data['users'] = [
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
    ];
    
    // Nested arrays
    $data['stats'] = [
        'users' => 100,
        'orders' => 50,
        'revenue' => [
            'today' => 1000,
            'month' => 30000
        ]
    ];
    
    // Objects
    $data['user'] = $this->load->model('user')->getById(1);
    
    $this->response->setOutput(
        $this->load->view('dashboard.html', $data)
    );
}
```

### Accessing Data in Views

```html
<!-- Simple variables -->
<h1><?php echo $title; ?></h1>
<p><?php echo $content; ?></p>

<!-- Arrays -->
<ul>
    <?php foreach ($users as $user): ?>
        <li><?php echo $user['name']; ?></li>
    <?php endforeach; ?>
</ul>

<!-- Nested arrays -->
<p>Revenue today: $<?php echo $stats['revenue']['today']; ?></p>

<!-- Conditional display -->
<?php if ($active): ?>
    <span class="badge active">Active</span>
<?php endif; ?>

<!-- Default values -->
<p><?php echo $message ?? 'No message'; ?></p>
```

---

## Template Inheritance

### Base Template

**File:** `app/view/base.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'My Application'; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    
    <!-- Custom head content -->
    <?php if (isset($head_content)): ?>
        <?php echo $head_content; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <a href="/" class="logo">My App</a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </nav>
    </header>
    
    <!-- Main content -->
    <main class="container">
        <?php if (isset($content)): ?>
            <?php echo $content; ?>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> My Application. All rights reserved.</p>
    </footer>
    
    <!-- JavaScript -->
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/app.js"></script>
    
    <!-- Custom scripts -->
    <?php if (isset($footer_scripts)): ?>
        <?php echo $footer_scripts; ?>
    <?php endif; ?>
</body>
</html>
```

### Using Base Template

**Controller:**

```php
public function index() {
    $data = [];
    $data['title'] = 'Home Page';
    
    // Load page content
    $data['content'] = $this->load->view('home/content.html', [
        'message' => 'Welcome to our website!'
    ]);
    
    // Load base template with content
    $this->response->setOutput(
        $this->load->view('base.html', $data)
    );
}
```

**Content Template (`app/view/home/content.html`):**

```html
<div class="home-page">
    <h1>Welcome</h1>
    <p><?php echo $message; ?></p>
    
    <div class="features">
        <div class="feature">
            <h3>Feature 1</h3>
            <p>Description of feature 1</p>
        </div>
        <div class="feature">
            <h3>Feature 2</h3>
            <p>Description of feature 2</p>
        </div>
    </div>
</div>
```

---

## Partial Views

### Creating Partials

**File:** `app/view/partials/user_card.html`

```html
<div class="user-card">
    <img src="<?php echo $user['avatar'] ?? '/assets/images/default-avatar.png'; ?>" alt="Avatar">
    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
    <p><?php echo htmlspecialchars($user['email']); ?></p>
    <a href="/user/view?id=<?php echo $user['id']; ?>" class="btn">View Profile</a>
</div>
```

### Including Partials

```html
<div class="user-list">
    <?php foreach ($users as $user): ?>
        <?php echo $this->load->view('partials/user_card.html', ['user' => $user]); ?>
    <?php endforeach; ?>
</div>
```

### Reusable Components

**Navigation (`app/view/partials/navigation.html`):**

```html
<nav class="navbar">
    <a href="/" class="logo">My App</a>
    <ul class="nav-links">
        <li><a href="/" class="<?php echo $active_page === 'home' ? 'active' : ''; ?>">Home</a></li>
        <li><a href="/about" class="<?php echo $active_page === 'about' ? 'active' : ''; ?>">About</a></li>
        <li><a href="/contact" class="<?php echo $active_page === 'contact' ? 'active' : ''; ?>">Contact</a></li>
    </ul>
</nav>
```

**Sidebar (`app/view/partials/sidebar.html`):**

```html
<aside class="sidebar">
    <div class="user-info">
        <img src="<?php echo $current_user['avatar']; ?>" alt="Avatar">
        <h4><?php echo htmlspecialchars($current_user['name']); ?></h4>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="/dashboard">Dashboard</a></li>
        <li><a href="/profile">Profile</a></li>
        <li><a href="/settings">Settings</a></li>
        <li><a href="/logout">Logout</a></li>
    </ul>
</aside>
```

---

## Best Practices

### 1. Always Escape Output

```html
<!-- Good: Prevents XSS attacks -->
<h1><?php echo htmlspecialchars($title); ?></h1>
<p><?php echo htmlspecialchars($user_input); ?></p>

<!-- Bad: Vulnerable to XSS -->
<h1><?php echo $title; ?></h1>
<p><?php echo $user_input; ?></p>

<!-- Exception: When you need HTML (be very careful) -->
<div><?php echo $trusted_html_content; ?></div>
```

### 2. Use Alternative Syntax for Control Structures

```html
<!-- Good: Cleaner in templates -->
<?php if ($condition): ?>
    <p>Content</p>
<?php endif; ?>

<?php foreach ($items as $item): ?>
    <li><?php echo $item; ?></li>
<?php endforeach; ?>

<!-- Avoid: Harder to read -->
<?php if ($condition) { ?>
    <p>Content</p>
<?php } ?>
```

### 3. Keep Logic Minimal

```html
<!-- Good: Minimal logic in view -->
<div class="status <?php echo $status_class; ?>">
    <?php echo $status_text; ?>
</div>

<!-- Avoid: Complex logic in view -->
<div class="status <?php echo $user['status'] === 1 ? 'active' : ($user['status'] === 2 ? 'pending' : 'inactive'); ?>">
    <?php echo $user['status'] === 1 ? 'Active' : ($user['status'] === 2 ? 'Pending' : 'Inactive'); ?>
</div>
```

**Instead, prepare data in controller:**

```php
public function view() {
    $user = $this->load->model('user')->getById(1);
    
    $data['status_class'] = $user['status'] === 1 ? 'active' : 
                           ($user['status'] === 2 ? 'pending' : 'inactive');
    $data['status_text'] = ucfirst($data['status_class']);
    
    $this->response->setOutput($this->load->view('user/view.html', $data));
}
```

### 4. Organize Views by Feature

```
app/view/
├── user/
│   ├── list.html
│   ├── view.html
│   ├── form.html
│   └── partials/
│       ├── profile_card.html
│       └── stats.html
├── product/
│   ├── list.html
│   ├── view.html
│   └── form.html
└── partials/
    ├── header.html
    ├── footer.html
    └── sidebar.html
```

### 5. Use Consistent Naming

```
list.html      - List/index view
view.html      - Single item view
form.html      - Create/edit form
_partial.html  - Partial template (optional underscore prefix)
```

### 6. Provide Default Values

```html
<!-- Good: Handles missing data -->
<h1><?php echo $title ?? 'Untitled'; ?></h1>
<p><?php echo $description ?? 'No description available'; ?></p>

<!-- Alternative with isset -->
<?php if (isset($message)): ?>
    <div class="alert"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
```

### 7. Use Helper Functions

Create view helpers in `app/helper.php`:

```php
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function asset($path) {
    return '/assets/' . ltrim($path, '/');
}

function url($path) {
    return rtrim(CONFIG_APP_URL, '/') . '/' . ltrim($path, '/');
}
```

Use in views:

```html
<h1><?php echo escape($title); ?></h1>
<img src="<?php echo asset('images/logo.png'); ?>">
<a href="<?php echo url('/user/profile'); ?>">Profile</a>
```

---

## Security Considerations

### XSS Prevention

```html
<!-- Always escape user input -->
<p><?php echo htmlspecialchars($user_comment); ?></p>

<!-- For HTML attributes -->
<input type="text" value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>">

<!-- For URLs -->
<a href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>">Link</a>
```

### CSRF Protection

```html
<form method="POST" action="/user/save">
    <!-- Include CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <input type="text" name="name" value="<?php echo escape($name); ?>">
    <button type="submit">Save</button>
</form>
```

**In controller:**

```php
public function save() {
    // Verify CSRF token
    if (!$this->request->csrf('post')) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Process form...
}
```

### Safe HTML Output

```php
// In controller - sanitize HTML
require_once 'path/to/htmlpurifier/library/HTMLPurifier.auto.php';

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);
$clean_html = $purifier->purify($user_html);

$data['content'] = $clean_html;
```

```html
<!-- In view - output sanitized HTML -->
<div class="user-content">
    <?php echo $content; ?>
</div>
```

---

## Advanced Techniques

### View Composition

**Create a layout manager:**

```php
class ControllerBase extends Controller {
    
    protected function render($view, $data = []) {
        // Add common data
        $data['current_user'] = $this->getCurrentUser();
        $data['notifications'] = $this->getNotifications();
        
        // Load main content
        $data['content'] = $this->load->view($view, $data);
        
        // Load layout
        return $this->load->view('layouts/main.html', $data);
    }
    
    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    protected function getNotifications() {
        // Load user notifications
        return [];
    }
}
```

**Use in controllers:**

```php
class ControllerUser extends ControllerBase {
    
    public function index() {
        $data['users'] = $this->load->model('user')->getAll();
        $this->response->setOutput($this->render('user/list.html', $data));
    }
}
```

### View Caching

```php
public function generateReport() {
    $cache_key = 'report_' . date('Y-m-d');
    
    $html = $this->cache->get($cache_key);
    
    if ($html === null) {
        $data = $this->load->model('report')->getData();
        $html = $this->load->view('reports/daily.html', $data);
        
        // Cache for 1 hour
        $this->cache->set($cache_key, $html, 3600);
    }
    
    $this->response->setOutput($html);
}
```

---

## Related Documentation

- **[Controllers](07-controllers.md)** - Loading views from controllers
- **[Language Files](13-language.md)** - Internationalization in views
- **[Security](24-security.md)** - Security best practices
- **[Helpers](14-helpers.md)** - View helper functions

---

**Previous:** [Models (ORM)](09-models-orm.md)  
**Next:** [Services](11-services.md)
