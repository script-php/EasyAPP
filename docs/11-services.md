# Services

Services contain business logic that can be reused across multiple controllers. They help keep controllers thin and promote code reusability.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Creating Services](#creating-services)
3. [Loading Services](#loading-services)
4. [Service Types](#service-types)
5. [Best Practices](#best-practices)
6. [Real-World Examples](#real-world-examples)

---

## Introduction

Services in EasyAPP are PHP classes that encapsulate business logic, external API integrations, complex calculations, or any functionality that needs to be shared across different parts of your application.

### Benefits of Services

- **Reusability**: Share logic across multiple controllers
- **Maintainability**: Single location for business logic
- **Testability**: Easier to unit test
- **Separation of Concerns**: Keep controllers thin

### Service Location

Services are stored in the `app/service/` directory:

```
app/service/
├── EmailService.php
├── PaymentService.php
├── UserService.php
└── ReportService.php
```

---

## Creating Services

### Basic Service Structure

All services extend the `Service` base class:

**File:** `app/service/EmailService.php`

```php
<?php

class ServiceEmailService extends Service {
    
    /**
     * Send a welcome email to a new user
     */
    public function sendWelcomeEmail($user) {
        $subject = 'Welcome to ' . CONFIG_APP_NAME;
        
        $data = [];
        $data['user'] = $user;
        $data['app_name'] = CONFIG_APP_NAME;
        
        // Load email template
        $body = $this->load->view('email/welcome.html', $data);
        
        // Send email using Mail helper
        return $this->mail->send($user['email'], $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($user, $resetToken) {
        $subject = 'Password Reset Request';
        
        $data = [];
        $data['user'] = $user;
        $data['reset_link'] = CONFIG_APP_URL . '/reset-password?token=' . $resetToken;
        $data['expires_in'] = '24 hours';
        
        $body = $this->load->view('email/password_reset.html', $data);
        
        return $this->mail->send($user['email'], $subject, $body);
    }
    
    /**
     * Send notification email
     */
    public function sendNotification($email, $message) {
        $subject = 'Notification from ' . CONFIG_APP_NAME;
        
        $data = [];
        $data['message'] = $message;
        $data['app_name'] = CONFIG_APP_NAME;
        
        $body = $this->load->view('email/notification.html', $data);
        
        return $this->mail->send($email, $subject, $body);
    }
}
```

### Naming Convention

Service class names follow the pattern: `Service[Name]Service`

```php
ServiceEmailService      // app/service/EmailService.php
ServicePaymentService    // app/service/PaymentService.php
ServiceUserService       // app/service/UserService.php
```

When loading: Use lowercase filename without extension

```php
$this->load->service('EmailService');  // Loads ServiceEmailService
$this->load->service('PaymentService'); // Loads ServicePaymentService
```

---

## Loading Services

### From Controllers

```php
class ControllerUser extends Controller {
    
    public function register() {
        // Get POST data
        $userData = $this->request->post;
        
        // Load model and create user
        $userModel = $this->load->model('user');
        $userId = $userModel->create($userData);
        
        // Load service and send welcome email
        $this->load->service('EmailService');
        $this->EmailService->sendWelcomeEmail($userData);
        
        $this->response->redirect('/user/profile');
    }
}
```

### From Models

```php
class ModelUser extends Model {
    
    public function register($data) {
        // Insert user
        $userId = $this->insertUser($data);
        
        // Load service
        $this->load->service('EmailService');
        
        // Send welcome email
        $user = $this->getById($userId);
        $this->EmailService->sendWelcomeEmail($user);
        
        return $userId;
    }
    
    public function getUserWithOrders($userId) {
        // Models can load other models - both styles work
        
        // Style 1: Capture instance
        $orderModel = $this->load->model('order');
        $orders = $orderModel->getByUserId($userId);
        
        // Style 2: Magic access
        // $this->load->model('order');
        // $orders = $this->model_order->getByUserId($userId);
        
        return [
            'user' => $this->getById($userId),
            'orders' => $orders
        ];
    }
}
```

### From Other Services

```php
class ServiceUserService extends Service {
    
    public function registerUser($data) {
        // Load model
        $userModel = $this->load->model('user');
        $userId = $userModel->create($data);
        
        // Load another service
        $this->load->service('EmailService');
        $this->EmailService->sendWelcomeEmail($data);
        
        // Load analytics service
        $this->load->service('AnalyticsService');
        $this->AnalyticsService->trackUserRegistration($userId);
        
        return $userId;
    }
}
```

### Accessing Framework Services

Services have access to all framework components through the `$registry` property:

```php
class ServiceExampleService extends Service {
    
    public function example() {
        // Access database
        $db = $this->db;
        
        // Access request
        $postData = $this->request->post;
        
        // Access session
        $userId = $this->request->session['user_id'];
        
        // Access cache
        $data = $this->cache->get('key');
        
        // Load resources
        $model = $this->load->model('user');
        $view = $this->load->view('template.html', []);
        $library = $this->load->library('MyLib');
    }
}
```

---

## Service Types

### 1. Email Services

```php
class ServiceEmailService extends Service {
    
    public function sendOrderConfirmation($order) {
        $user = $this->load->model('user')->getById($order['user_id']);
        
        $data = [
            'order' => $order,
            'user' => $user,
            'items' => $this->load->model('order')->getItems($order['id'])
        ];
        
        $body = $this->load->view('email/order_confirmation.html', $data);
        
        return $this->mail->send($user['email'], 'Order Confirmation', $body);
    }
}
```

### 2. Payment Services

```php
class ServicePaymentService extends Service {
    
    private $apiKey;
    private $apiUrl;
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->apiKey = CONFIG_PAYMENT_API_KEY;
        $this->apiUrl = CONFIG_PAYMENT_API_URL;
    }
    
    /**
     * Process payment
     */
    public function processPayment($amount, $currency, $cardToken) {
        // Prepare payment data
        $paymentData = [
            'amount' => $amount * 100, // Convert to cents
            'currency' => $currency,
            'source' => $cardToken,
            'description' => 'Payment for order'
        ];
        
        // Call payment API
        $response = $this->callPaymentApi('POST', '/charges', $paymentData);
        
        if ($response['success']) {
            // Log successful payment
            $this->logPayment($response['transaction_id'], $amount, 'success');
            return $response;
        } else {
            // Log failed payment
            $this->logPayment(null, $amount, 'failed', $response['error']);
            throw new Exception('Payment failed: ' . $response['error']);
        }
    }
    
    /**
     * Refund payment
     */
    public function refundPayment($transactionId, $amount = null) {
        $refundData = ['charge' => $transactionId];
        
        if ($amount !== null) {
            $refundData['amount'] = $amount * 100;
        }
        
        return $this->callPaymentApi('POST', '/refunds', $refundData);
    }
    
    /**
     * Call payment gateway API
     */
    private function callPaymentApi($method, $endpoint, $data = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true] + $result;
        } else {
            return ['success' => false, 'error' => $result['error'] ?? 'Unknown error'];
        }
    }
    
    /**
     * Log payment transaction
     */
    private function logPayment($transactionId, $amount, $status, $error = null) {
        $this->db->query(
            "INSERT INTO payments (transaction_id, amount, status, error, created_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$transactionId, $amount, $status, $error]
        );
    }
}
```

### 3. User Management Services

```php
class ServiceUserService extends Service {
    
    /**
     * Register a new user
     */
    public function register($data) {
        // Validate data
        if (!$this->validateRegistration($data)) {
            throw new Exception('Invalid registration data');
        }
        
        // Check if email already exists
        $userModel = $this->load->model('user');
        if ($userModel->emailExists($data['email'])) {
            throw new Exception('Email already registered');
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        $userId = $userModel->create($data);
        
        // Send welcome email
        $this->load->service('EmailService');
        $this->EmailService->sendWelcomeEmail($data);
        
        // Log event
        $this->logEvent('user_registered', $userId);
        
        return $userId;
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $userModel = $this->load->model('user');
        $user = $userModel->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password'])) {
            $this->logFailedLogin($user['id']);
            return false;
        }
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            throw new Exception('Account is not active');
        }
        
        // Update last login
        $userModel->updateLastLogin($user['id']);
        
        // Log successful login
        $this->logEvent('user_login', $user['id']);
        
        return $user;
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        $userModel = $this->load->model('user');
        $user = $userModel->getByEmail($email);
        
        if (!$user) {
            // Don't reveal if email exists
            return true;
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Save token
        $userModel->saveResetToken($user['id'], $token, $expires);
        
        // Send email
        $this->load->service('EmailService');
        $this->EmailService->sendPasswordReset($user, $token);
        
        return true;
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration($data) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log user event
     */
    private function logEvent($event, $userId) {
        $this->db->query(
            "INSERT INTO user_events (user_id, event, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, NOW())",
            [$userId, $event, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
        );
    }
    
    /**
     * Log failed login attempt
     */
    private function logFailedLogin($userId) {
        $this->logEvent('failed_login', $userId);
    }
}
```

### 4. API Integration Services

```php
class ServiceWeatherService extends Service {
    
    private $apiKey;
    private $apiUrl = 'https://api.openweathermap.org/data/2.5';
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->apiKey = CONFIG_WEATHER_API_KEY;
    }
    
    /**
     * Get current weather for a city
     */
    public function getCurrentWeather($city) {
        $cacheKey = 'weather_' . md5($city);
        
        // Check cache (5 minutes)
        $weather = $this->cache->get($cacheKey);
        if ($weather !== null) {
            return $weather;
        }
        
        // Call API
        $url = $this->apiUrl . '/weather?q=' . urlencode($city) . '&appid=' . $this->apiKey;
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (!isset($data['main'])) {
            throw new Exception('Failed to fetch weather data');
        }
        
        $weather = [
            'city' => $data['name'],
            'temperature' => $data['main']['temp'],
            'description' => $data['weather'][0]['description'],
            'humidity' => $data['main']['humidity'],
            'wind_speed' => $data['wind']['speed']
        ];
        
        // Cache for 5 minutes
        $this->cache->set($cacheKey, $weather, 300);
        
        return $weather;
    }
}
```

### 5. Report Generation Services

```php
class ServiceReportService extends Service {
    
    /**
     * Generate sales report
     */
    public function generateSalesReport($startDate, $endDate) {
        // Get data
        $orders = $this->load->model('order')->getByDateRange($startDate, $endDate);
        
        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'total_orders' => count($orders),
            'total_revenue' => 0,
            'average_order_value' => 0,
            'orders_by_status' => [],
            'top_products' => []
        ];
        
        // Calculate totals
        foreach ($orders as $order) {
            $report['total_revenue'] += $order['total'];
            
            if (!isset($report['orders_by_status'][$order['status']])) {
                $report['orders_by_status'][$order['status']] = 0;
            }
            $report['orders_by_status'][$order['status']]++;
        }
        
        // Calculate average
        if ($report['total_orders'] > 0) {
            $report['average_order_value'] = $report['total_revenue'] / $report['total_orders'];
        }
        
        // Get top products
        $report['top_products'] = $this->load->model('product')->getTopSelling($startDate, $endDate, 10);
        
        return $report;
    }
    
    /**
     * Export report to PDF
     */
    public function exportToPdf($report) {
        // Load PDF library
        $this->load->library('PdfGenerator');
        
        // Generate HTML
        $html = $this->load->view('reports/sales_pdf.html', ['report' => $report]);
        
        // Convert to PDF
        return $this->PdfGenerator->generate($html);
    }
    
    /**
     * Export report to CSV
     */
    public function exportToCsv($report) {
        $csv = "Period,Total Orders,Total Revenue,Average Order Value\n";
        $csv .= "{$report['period']['start']} to {$report['period']['end']},";
        $csv .= "{$report['total_orders']},";
        $csv .= "{$report['total_revenue']},";
        $csv .= "{$report['average_order_value']}\n";
        
        return $csv;
    }
}
```

---

## Best Practices

### 1. Keep Services Focused

```php
// Good: Focused on email functionality
class ServiceEmailService extends Service {
    public function sendWelcomeEmail($user) { }
    public function sendPasswordReset($user, $token) { }
    public function sendNotification($email, $message) { }
}

// Bad: Too many responsibilities
class ServiceUserService extends Service {
    public function register($data) { }
    public function authenticate($email, $password) { }
    public function sendEmail($user) { }  // Should be in EmailService
    public function processPayment($amount) { }  // Should be in PaymentService
}
```

### 2. Use Dependency Injection

```php
class ServiceOrderService extends Service {
    
    private $emailService;
    private $paymentService;
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Load dependencies
        $this->load->service('EmailService');
        $this->load->service('PaymentService');
        
        $this->emailService = $this->EmailService;
        $this->paymentService = $this->PaymentService;
    }
    
    public function createOrder($data) {
        // Process payment
        $payment = $this->paymentService->processPayment($data['amount'], 'USD', $data['card_token']);
        
        // Create order
        $orderId = $this->load->model('order')->create($data);
        
        // Send confirmation
        $this->emailService->sendOrderConfirmation($orderId);
        
        return $orderId;
    }
}
```

### 3. Handle Errors Properly

```php
class ServicePaymentService extends Service {
    
    public function processPayment($amount, $currency, $cardToken) {
        try {
            $response = $this->callPaymentApi('POST', '/charges', [
                'amount' => $amount * 100,
                'currency' => $currency,
                'source' => $cardToken
            ]);
            
            if (!$response['success']) {
                throw new Exception('Payment declined: ' . $response['error']);
            }
            
            return $response;
            
        } catch (Exception $e) {
            // Log error
            $this->logger->error('Payment processing failed', [
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            // Re-throw with user-friendly message
            throw new Exception('Payment processing failed. Please try again.');
        }
    }
}
```

### 4. Use Caching When Appropriate

```php
class ServiceProductService extends Service {
    
    public function getFeaturedProducts() {
        $cacheKey = 'featured_products';
        
        // Try to get from cache
        $products = $this->cache->get($cacheKey);
        
        if ($products === null) {
            // Get from database
            $products = $this->load->model('product')->getFeatured();
            
            // Cache for 1 hour
            $this->cache->set($cacheKey, $products, 3600);
        }
        
        return $products;
    }
    
    public function clearCache() {
        $this->cache->delete('featured_products');
    }
}
```

### 5. Make Services Testable

```php
class ServiceCalculatorService extends Service {
    
    /**
     * Calculate order total
     * Pure function - easy to test
     */
    public function calculateTotal($items, $taxRate = 0, $shippingCost = 0) {
        $subtotal = array_sum(array_column($items, 'price'));
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax + $shippingCost;
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shippingCost,
            'total' => $total
        ];
    }
    
    /**
     * Calculate discount
     * Pure function - easy to test
     */
    public function calculateDiscount($amount, $discountPercent) {
        return $amount * ($discountPercent / 100);
    }
}
```

---

## Real-World Examples

### Complete Order Processing Service

```php
class ServiceOrderService extends Service {
    
    public function processOrder($orderData) {
        // Start transaction
        $this->db->query('START TRANSACTION');
        
        try {
            // 1. Validate inventory
            foreach ($orderData['items'] as $item) {
                if (!$this->checkInventory($item['product_id'], $item['quantity'])) {
                    throw new Exception('Product out of stock');
                }
            }
            
            // 2. Process payment
            $this->load->service('PaymentService');
            $payment = $this->PaymentService->processPayment(
                $orderData['total'],
                $orderData['currency'],
                $orderData['card_token']
            );
            
            // 3. Create order
            $orderId = $this->load->model('order')->create([
                'user_id' => $orderData['user_id'],
                'total' => $orderData['total'],
                'status' => 'pending',
                'payment_id' => $payment['transaction_id']
            ]);
            
            // 4. Add order items
            foreach ($orderData['items'] as $item) {
                $this->load->model('order_item')->create([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }
            
            // 5. Update inventory
            foreach ($orderData['items'] as $item) {
                $this->updateInventory($item['product_id'], -$item['quantity']);
            }
            
            // 6. Send confirmation email
            $this->load->service('EmailService');
            $this->EmailService->sendOrderConfirmation($orderId);
            
            // Commit transaction
            $this->db->query('COMMIT');
            
            return $orderId;
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->query('ROLLBACK');
            
            // Log error
            $this->logger->error('Order processing failed', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
            
            throw $e;
        }
    }
    
    private function checkInventory($productId, $quantity) {
        $product = $this->load->model('product')->getById($productId);
        return $product && $product['stock'] >= $quantity;
    }
    
    private function updateInventory($productId, $quantityChange) {
        $this->db->query(
            "UPDATE products SET stock = stock + ? WHERE id = ?",
            [$quantityChange, $productId]
        );
    }
}
```

---

## Related Documentation

- **[Controllers](07-controllers.md)** - Loading and using services in controllers
- **[Models](08-models-traditional.md)** - Data layer accessed by services
- **[Libraries](12-libraries.md)** - Reusable components vs services
- **[Testing](28-testing.md)** - Unit testing services

---

**Previous:** [Views](10-views.md)  
**Next:** [Libraries](12-libraries.md)
