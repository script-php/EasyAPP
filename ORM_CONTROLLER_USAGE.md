# Using ORM Models in Controllers

## 🎯 Two Ways to Access Models

Your framework now supports **TWO approaches** for using models in controllers:

1. **Direct Static Calls** (New ORM way - cleaner!)
2. **Traditional Loading** (Classic way - still works!)

---

## 📋 Method 1: Direct Static Calls (Recommended for ORM)

### How It Works

ORM models are **automatically loaded via PSR-4** when you use them. No need to call `$this->load->model()`!

**No `composer dump-autoload` needed when adding new models!** 🎉

```php
use App\Model\Post;
use App\Model\User;

class ControllerBlog extends Controller {
    
    public function index() {
        // Just use the model directly!
        $posts = Post::with('user')
            ->where('status', 'published')
            ->paginate(20);
        
        $this->response->setOutput($this->load->view('blog/index', [
            'posts' => $posts
        ]));
    }
    
    public function show() {
        $postId = $this->request->get['id'] ?? 0;
        
        // Direct access - no loading needed!
        $post = Post::with(['user', 'comments'])
            ->find($postId);
        
        if (!$post) {
            $this->response->redirect('/404');
            return;
        }
        
        $this->response->setOutput($this->load->view('blog/show', [
            'post' => $post
        ]));
    }
    
}
```

### ✅ Advantages
- Cleaner code
- Less typing
- **Auto-discovery** - no `composer dump-autoload` needed!
- IDE autocomplete works better
- More modern syntax
- Can chain methods easily

---

## 📋 Method 2: Traditional Loading (Works for Everything)

### Old Models (Non-ORM)

```php
class ControllerUser extends Controller {
    
    public function profile() {
        // Load model the traditional way
        $this->load->model('user');
        
        // Access via $this->model_user
        $user = $this->model_user->getUserById(5);
        
        $this->response->setOutput($this->load->view('user/profile', [
            'user' => $user
        ]));
    }
    
}
```

**Model File: `app/model/user.php`**
```php
class ModelUser extends Model {
    
    public function getUserById($user_id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->query($sql, [$user_id]);
    }
    
    public function getAllUsers() {
        return $this->db->query("SELECT * FROM users");
    }
}
```

### ORM Models (Both Ways Work!)

```php
class ControllerBlog extends Controller {
    
    public function mixed_example() {
        // Traditional way
        $this->load->model('post');
        
        // Use ORM methods through loaded model
        $posts = $this->model_post::all();
        
        // OR use direct static call
        $posts = Post::all();
        
        // Both work the same!
    }
    
}
```

---

## 🔥 Real-World Examples

### Example 1: User Authentication

```php
use App\Model\User;

class ControllerAccount extends Controller {
    
    public function login() {
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $email = $this->request->post['email'] ?? '';
            $password = $this->request->post['password'] ?? '';
            
            // Direct ORM call - super clean!
            $user = User::where('email', $email)->first();
            
            if ($user && password_verify($password, $user->password)) {
                // Login success
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->name;
                
                $this->response->redirect('/dashboard');
                return;
            }
            
            $error = 'Invalid credentials';
        }
        
        $this->response->setOutput($this->load->view('account/login', [
            'error' => $error ?? ''
        ]));
    }
    
    public function register() {
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            // Validate input
            $data = [
                'name' => $this->request->post['name'] ?? '',
                'email' => $this->request->post['email'] ?? '',
                'password' => password_hash($this->request->post['password'], PASSWORD_DEFAULT),
            ];
            
            // Check if email exists
            if (User::where('email', $data['email'])->exists()) {
                $error = 'Email already registered';
            } else {
                // Create new user with ORM
                $user = User::create($data);
                
                // Auto-login
                $_SESSION['user_id'] = $user->id;
                $this->response->redirect('/dashboard');
                return;
            }
        }
        
        $this->response->setOutput($this->load->view('account/register', [
            'error' => $error ?? ''
        ]));
    }
    
}
```

---

### Example 2: Blog with Comments

```php
use App\Model\Post;
use App\Model\Comment;
use App\Model\Category;

class ControllerBlog extends Controller {
    
    public function index() {
        // Get published posts with author info
        $posts = Post::with('user')
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->paginate(15);
        
        // Get categories for sidebar
        $categories = Category::withCount('posts')
            ->orderBy('name', 'ASC')
            ->get();
        
        $this->response->setOutput($this->load->view('blog/index', [
            'posts' => $posts,
            'categories' => $categories
        ]));
    }
    
    public function show() {
        $postId = $this->request->get['id'] ?? 0;
        
        // Get post with author and comments
        $post = Post::with(['user', 'comments.user'])
            ->find($postId);
        
        if (!$post) {
            $this->response->redirect('/404');
            return;
        }
        
        // Increment view count
        $post->increment('views');
        
        // Get related posts
        $relatedPosts = Post::where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->limit(5)
            ->get();
        
        $this->response->setOutput($this->load->view('blog/show', [
            'post' => $post,
            'related' => $relatedPosts
        ]));
    }
    
    public function create() {
        // Only for logged-in users
        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('/login');
            return;
        }
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            // Validate and create post
            $data = [
                'user_id' => $_SESSION['user_id'],
                'title' => $this->request->post['title'] ?? '',
                'content' => $this->request->post['content'] ?? '',
                'status' => 'draft'
            ];
            
            // Create post using ORM
            $post = Post::create($data);
            
            $this->response->redirect('/blog/edit?id=' . $post->id);
            return;
        }
        
        $this->response->setOutput($this->load->view('blog/create'));
    }
    
    public function addComment() {
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $this->load->json(['error' => 'Invalid request'], true);
            return;
        }
        
        $postId = $this->request->post['post_id'] ?? 0;
        $content = $this->request->post['content'] ?? '';
        
        // Validate
        if (!isset($_SESSION['user_id'])) {
            $this->load->json(['error' => 'Login required'], true);
            return;
        }
        
        if (empty($content)) {
            $this->load->json(['error' => 'Comment cannot be empty'], true);
            return;
        }
        
        // Check if post exists
        $post = Post::find($postId);
        if (!$post) {
            $this->load->json(['error' => 'Post not found'], true);
            return;
        }
        
        // Create comment
        $comment = Comment::create([
            'post_id' => $postId,
            'user_id' => $_SESSION['user_id'],
            'content' => $content
        ]);
        
        // Return comment with user info
        $comment->load('user');
        
        $this->load->json([
            'success' => true,
            'comment' => $comment
        ], true);
    }
    
}
```

---

### Example 3: Admin Dashboard (Mixed Approach)

```php
use App\Model\User;
use App\Model\Post;
use App\Model\Comment;

class ControllerAdmin extends Controller {
    
    public function dashboard() {
        // Check admin access
        if (!$this->isAdmin()) {
            $this->response->redirect('/');
            return;
        }
        
        // Get statistics using ORM
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_comments' => Comment::count(),
            'active_users' => User::where('status', 1)->count(),
            'published_posts' => Post::where('status', 'published')->count(),
        ];
        
        // Get recent activity
        $recentUsers = User::orderBy('created_at', 'DESC')->limit(5)->get();
        $recentPosts = Post::with('user')->orderBy('created_at', 'DESC')->limit(5)->get();
        
        // Use traditional model for complex SQL
        $this->load->model('analytics');
        $monthlyStats = $this->model_analytics->getMonthlyStats();
        
        $this->response->setOutput($this->load->view('admin/dashboard', [
            'stats' => $stats,
            'recent_users' => $recentUsers,
            'recent_posts' => $recentPosts,
            'monthly_stats' => $monthlyStats
        ]));
    }
    
    public function users() {
        // Get users with pagination
        $page = $this->request->get['page'] ?? 1;
        
        $users = User::withCount('posts')
            ->orderBy('created_at', 'DESC')
            ->paginate(20, $page);
        
        $this->response->setOutput($this->load->view('admin/users', [
            'users' => $users
        ]));
    }
    
    public function deleteUser() {
        if (!$this->isAdmin()) {
            $this->load->json(['error' => 'Access denied'], true);
            return;
        }
        
        $userId = $this->request->post['user_id'] ?? 0;
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->load->json(['error' => 'User not found'], true);
            return;
        }
        
        // Soft delete (if configured)
        $user->delete();
        
        $this->load->json(['success' => true], true);
    }
    
    private function isAdmin() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $user = User::find($_SESSION['user_id']);
        return $user && $user->role === 'admin';
    }
    
}
```

---

### Example 4: API Controller

```php
use App\Model\Post;
use App\Model\User;

class ControllerApi extends Controller {
    
    public function posts() {
        // Get query parameters
        $page = $this->request->get['page'] ?? 1;
        $limit = min($this->request->get['limit'] ?? 10, 100); // Max 100
        $status = $this->request->get['status'] ?? 'published';
        
        // Build query
        $query = Post::with('user')->where('status', $status);
        
        // Apply filters
        if (!empty($this->request->get['category'])) {
            $query->where('category_id', $this->request->get['category']);
        }
        
        if (!empty($this->request->get['search'])) {
            $search = $this->request->get['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }
        
        // Get paginated results
        $posts = $query->paginate($limit, $page);
        
        // Return JSON
        $this->load->json([
            'success' => true,
            'data' => $posts['data'],
            'pagination' => [
                'current_page' => $posts['current_page'],
                'total_pages' => $posts['total_pages'],
                'total_records' => $posts['total'],
                'per_page' => $posts['per_page']
            ]
        ], true);
    }
    
    public function createPost() {
        // Validate token
        if (!$this->validateApiToken()) {
            $this->load->json(['error' => 'Unauthorized'], true);
            http_response_code(401);
            return;
        }
        
        // Validate input
        $required = ['title', 'content', 'user_id'];
        foreach ($required as $field) {
            if (empty($this->request->post[$field])) {
                $this->load->json(['error' => "Field '{$field}' is required"], true);
                http_response_code(400);
                return;
            }
        }
        
        // Create post
        $post = Post::create([
            'user_id' => $this->request->post['user_id'],
            'title' => $this->request->post['title'],
            'content' => $this->request->post['content'],
            'status' => $this->request->post['status'] ?? 'draft',
        ]);
        
        // Load relationships
        $post->load('user');
        
        $this->load->json([
            'success' => true,
            'data' => $post
        ], true);
        http_response_code(201);
    }
    
    private function validateApiToken() {
        $token = $this->request->server['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        
        // Verify token against database
        $user = User::where('api_token', $token)->first();
        
        return $user !== null;
    }
    
}
```

---

## 🤔 Which Method Should I Use?

### Use Direct Static Calls When:
- ✅ Working with ORM models
- ✅ You want cleaner, more readable code
- ✅ Building new features
- ✅ You need method chaining

### Use Traditional Loading When:
- ✅ Working with old non-ORM models
- ✅ You have complex custom SQL methods
- ✅ Maintaining existing code
- ✅ You prefer explicit loading

---

## 💡 Pro Tips

### 1. Mix Both Approaches
```php
class ControllerReport extends Controller {
    public function sales() {
        // Use ORM for simple queries
        $users = User::where('status', 1)->get();
        
        // Use traditional model for complex SQL
        $this->load->model('sales');
        $report = $this->model_sales->getComplexReport();
        
        $this->response->setOutput($this->load->view('report', [
            'users' => $users,
            'report' => $report
        ]));
    }
}
```

### 2. Helper Functions
Create helper functions for common queries in `app/helper.php`:

```php
use App\Model\User;

function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return User::find($_SESSION['user_id']);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /login');
        exit;
    }
}
```

Use in controllers:
```php
use App\Model\User;

class ControllerDashboard extends Controller {
    public function index() {
        require_login();
        
        $user = current_user();
        $posts = $user->posts()->paginate(10);
        
        $this->response->setOutput($this->load->view('dashboard', [
            'user' => $user,
            'posts' => $posts
        ]));
    }
}
```

### 3. Base Controller for Common Logic
```php
use App\Model\User;

class BaseController extends Controller {
    
    protected $user = null;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Auto-load current user
        if (isset($_SESSION['user_id'])) {
            $this->user = User::find($_SESSION['user_id']);
        }
    }
    
    protected function requireLogin() {
        if (!$this->user) {
            $this->response->redirect('/login');
            exit;
        }
    }
    
    protected function requireAdmin() {
        $this->requireLogin();
        if ($this->user->role !== 'admin') {
            $this->response->redirect('/');
            exit;
        }
    }
}

// Use it
class ControllerDashboard extends BaseController {
    public function index() {
        $this->requireLogin();
        
        // $this->user is already loaded!
        $posts = $this->user->posts()->paginate(10);
        
        $this->response->setOutput($this->load->view('dashboard', [
            'user' => $this->user,
            'posts' => $posts
        ]));
    }
}
```

---

## 🎉 Summary

**Both approaches work perfectly!**

```php
use App\Model\User;
use App\Model\Post;

// ORM Way (Direct Static Calls)
$user = User::find(5);
$posts = Post::with('user')->paginate(20);

// Traditional Way (Load First)
$this->load->model('user');
$user = $this->model_user->find(5);

// Old Models (Traditional Only)
$this->load->model('user');
$user = $this->model_user->getUserById(5);
```

**Choose what feels best for your project!** 🚀

---

**Related Docs:**
- [ORM Usage Guide](ORM_USAGE.md)
- [ORM Features](ORM_FEATURES.md)
- [Quick Reference](ORM_QUICK_REFERENCE.md)
