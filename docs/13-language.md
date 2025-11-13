# Language Files

Language files enable internationalization (i18n) in your application, allowing you to support multiple languages and locales.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Directory Structure](#directory-structure)
3. [Creating Language Files](#creating-language-files)
4. [Loading Language Files](#loading-language-files)
5. [Using Translations](#using-translations)
6. [Setting Default Language](#setting-default-language)
7. [Dynamic Language Switching](#dynamic-language-switching)
8. [Best Practices](#best-practices)
9. [Advanced Usage](#advanced-usage)

---

## Introduction

EasyAPP uses a simple yet powerful language system that allows you to:

- Support multiple languages
- Organize translations by module
- Use placeholders for dynamic content
- Switch languages at runtime

---

## Directory Structure

Language files are stored in `app/language/` with subdirectories for each locale:

```
app/language/
├── en-gb/              # English (Great Britain)
│   ├── home.php
│   ├── user.php
│   ├── product.php
│   └── error.php
├── fr-fr/              # French (France)
│   ├── home.php
│   ├── user.php
│   ├── product.php
│   └── error.php
└── ro-ro/              # Romanian (Romania)
    ├── home.php
    ├── user.php
    ├── product.php
    └── error.php
```

### Locale Format

Use lowercase locale codes with hyphen separator:

- `en-gb` - English (Great Britain)
- `en-us` - English (United States)
- `fr-fr` - French (France)
- `de-de` - German (Germany)
- `es-es` - Spanish (Spain)
- `ro-ro` - Romanian (Romania)

---

## Creating Language Files

### Basic Language File

**File:** `app/language/en-gb/home.php`

```php
<?php

// Simple text
$_['heading_title'] = 'Welcome to Our Website';
$_['text_welcome'] = 'Welcome';
$_['text_description'] = 'This is a description of our website.';

// Navigation
$_['text_home'] = 'Home';
$_['text_about'] = 'About';
$_['text_contact'] = 'Contact';
$_['text_login'] = 'Login';
$_['text_logout'] = 'Logout';

// Buttons
$_['button_submit'] = 'Submit';
$_['button_cancel'] = 'Cancel';
$_['button_save'] = 'Save';
$_['button_delete'] = 'Delete';

// Messages
$_['text_success'] = 'Success!';
$_['text_error'] = 'An error occurred.';
$_['text_no_results'] = 'No results found.';
```

**File:** `app/language/fr-fr/home.php`

```php
<?php

// Simple text
$_['heading_title'] = 'Bienvenue sur notre site Web';
$_['text_welcome'] = 'Bienvenue';
$_['text_description'] = 'Ceci est une description de notre site Web.';

// Navigation
$_['text_home'] = 'Accueil';
$_['text_about'] = 'À propos';
$_['text_contact'] = 'Contact';
$_['text_login'] = 'Connexion';
$_['text_logout'] = 'Déconnexion';

// Buttons
$_['button_submit'] = 'Soumettre';
$_['button_cancel'] = 'Annuler';
$_['button_save'] = 'Enregistrer';
$_['button_delete'] = 'Supprimer';

// Messages
$_['text_success'] = 'Succès!';
$_['text_error'] = 'Une erreur est survenue.';
$_['text_no_results'] = 'Aucun résultat trouvé.';
```

### Language File for User Module

**File:** `app/language/en-gb/user.php`

```php
<?php

// Headings
$_['heading_title'] = 'User Management';
$_['heading_create'] = 'Create User';
$_['heading_edit'] = 'Edit User';

// Text
$_['text_list'] = 'User List';
$_['text_no_users'] = 'No users found.';
$_['text_confirm_delete'] = 'Are you sure you want to delete this user?';

// Form labels
$_['label_name'] = 'Name';
$_['label_email'] = 'Email';
$_['label_password'] = 'Password';
$_['label_confirm_password'] = 'Confirm Password';
$_['label_status'] = 'Status';
$_['label_role'] = 'Role';

// Buttons
$_['button_add'] = 'Add User';
$_['button_save'] = 'Save User';
$_['button_cancel'] = 'Cancel';

// Success messages
$_['success_create'] = 'User created successfully.';
$_['success_update'] = 'User updated successfully.';
$_['success_delete'] = 'User deleted successfully.';

// Error messages
$_['error_name_required'] = 'Name is required.';
$_['error_email_required'] = 'Email is required.';
$_['error_email_invalid'] = 'Please enter a valid email address.';
$_['error_email_exists'] = 'This email is already registered.';
$_['error_password_required'] = 'Password is required.';
$_['error_password_length'] = 'Password must be at least 8 characters.';
$_['error_password_mismatch'] = 'Passwords do not match.';
```

### Naming Conventions

Use consistent prefixes to organize translations:

```php
// Headings
$_['heading_*']

// General text
$_['text_*']

// Form labels
$_['label_*']

// Buttons
$_['button_*']

// Success messages
$_['success_*']

// Error messages
$_['error_*']

// Warnings
$_['warning_*']
```

---

## Loading Language Files

### Load in Controller

```php
class ControllerHome extends Controller {
    
    public function index() {
        // Load language file
        $this->load->language('home');
        
        // Use translations
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_welcome'] = $this->language->get('text_welcome');
        
        $this->response->setOutput($this->load->view('home/index.html', $data));
    }
}
```

### Load Multiple Language Files

```php
public function userProfile() {
    // Load multiple language files
    $this->load->language('user');
    $this->load->language('common');
    
    $data['heading_title'] = $this->language->get('heading_title');
    $data['button_save'] = $this->language->get('button_save');
    
    $this->response->setOutput($this->load->view('user/profile.html', $data));
}
```

### Load in Model

```php
class ModelUser extends Model {
    
    public function sendWelcomeEmail($userId) {
        // Load language file
        $this->load->language('email/welcome');
        
        $user = $this->getById($userId);
        
        $subject = $this->language->get('subject');
        $message = sprintf(
            $this->language->get('message_body'),
            $user['name']
        );
        
        $this->mail->send($user['email'], $subject, $message);
    }
}
```

---

## Using Translations

### In Controllers

```php
public function create() {
    $this->load->language('user');
    
    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        // Validate
        if (empty($this->request->post['name'])) {
            $data['error'] = $this->language->get('error_name_required');
        }
        
        // Success message
        if ($success) {
            $data['success'] = $this->language->get('success_create');
        }
    }
    
    // Pass translations to view
    $data['heading_title'] = $this->language->get('heading_title');
    $data['button_save'] = $this->language->get('button_save');
    
    $this->response->setOutput($this->load->view('user/form.html', $data));
}
```

### In Views

```html
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $heading_title; ?></title>
</head>
<body>
    <h1><?php echo $heading_title; ?></h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <label><?php echo $label_name; ?></label>
        <input type="text" name="name">
        
        <label><?php echo $label_email; ?></label>
        <input type="email" name="email">
        
        <button type="submit"><?php echo $button_save; ?></button>
        <a href="/user"><?php echo $button_cancel; ?></a>
    </form>
</body>
</html>
```

### With Placeholders

**Language file:**

```php
$_['text_welcome_user'] = 'Welcome back, %s!';
$_['text_items_found'] = 'Found %d items.';
$_['text_user_registered'] = '%s registered on %s.';
```

**Controller:**

```php
$this->load->language('user');

// Single placeholder
$data['welcome'] = sprintf(
    $this->language->get('text_welcome_user'),
    $user['name']
);

// Multiple placeholders
$data['items'] = sprintf(
    $this->language->get('text_items_found'),
    count($items)
);

// Multiple placeholders with different types
$data['info'] = sprintf(
    $this->language->get('text_user_registered'),
    $user['name'],
    date('F j, Y', strtotime($user['created_at']))
);
```

---

## Setting Default Language

### In Configuration

**File:** `config.php`

```php
// Set default language
define('CONFIG_LANGUAGE', 'en-gb');
```

### In Controller

```php
// Change language for current request
$this->language->setLanguage('fr-fr');
$this->load->language('home');
```

---

## Dynamic Language Switching

### URL-based Language Switching

**Controller:**

```php
class ControllerCommon extends Controller {
    
    public function setLanguage() {
        $lang = $this->request->get['lang'] ?? 'en-gb';
        
        // Validate language
        $availableLanguages = ['en-gb', 'fr-fr', 'ro-ro'];
        if (!in_array($lang, $availableLanguages)) {
            $lang = 'en-gb';
        }
        
        // Store in session
        $this->request->session['language'] = $lang;
        
        // Redirect back
        $redirect = $this->request->get['redirect'] ?? '/';
        $this->response->redirect($redirect);
    }
}
```

### Base Controller with Language Detection

```php
class ControllerBase extends Controller {
    
    protected function detectLanguage() {
        // Priority 1: User session
        if (isset($this->request->session['language'])) {
            return $this->request->session['language'];
        }
        
        // Priority 2: User profile (if logged in)
        if (isset($this->request->session['user_id'])) {
            $user = $this->load->model('user')->getById($this->request->session['user_id']);
            if ($user && !empty($user['language'])) {
                return $user['language'];
            }
        }
        
        // Priority 3: Browser language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $langMap = [
                'en' => 'en-gb',
                'fr' => 'fr-fr',
                'ro' => 'ro-ro'
            ];
            if (isset($langMap[$browserLang])) {
                return $langMap[$browserLang];
            }
        }
        
        // Priority 4: Default
        return CONFIG_LANGUAGE;
    }
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Set language
        $language = $this->detectLanguage();
        $this->language->setLanguage($language);
    }
}
```

### Language Switcher in View

```html
<div class="language-switcher">
    <select onchange="changeLanguage(this.value)">
        <option value="en-gb" <?php echo $current_language === 'en-gb' ? 'selected' : ''; ?>>English</option>
        <option value="fr-fr" <?php echo $current_language === 'fr-fr' ? 'selected' : ''; ?>>Français</option>
        <option value="ro-ro" <?php echo $current_language === 'ro-ro' ? 'selected' : ''; ?>>Română</option>
    </select>
</div>

<script>
function changeLanguage(lang) {
    var currentUrl = window.location.href;
    window.location.href = '/common/setLanguage?lang=' + lang + '&redirect=' + encodeURIComponent(currentUrl);
}
</script>
```

---

## Best Practices

### 1. Use Descriptive Keys

```php
// Good: Clear and descriptive
$_['error_email_invalid'] = 'Please enter a valid email address.';
$_['success_user_created'] = 'User created successfully.';

// Avoid: Generic or unclear
$_['error_1'] = 'Invalid email.';
$_['msg'] = 'Done.';
```

### 2. Organize by Prefix

```php
// Group related translations
$_['heading_title'] = 'Users';
$_['heading_create'] = 'Create User';
$_['heading_edit'] = 'Edit User';

$_['button_add'] = 'Add';
$_['button_save'] = 'Save';
$_['button_cancel'] = 'Cancel';

$_['error_name_required'] = 'Name is required.';
$_['error_email_invalid'] = 'Invalid email.';
```

### 3. Separate Common Translations

**File:** `app/language/en-gb/common.php`

```php
<?php

// Buttons used everywhere
$_['button_save'] = 'Save';
$_['button_cancel'] = 'Cancel';
$_['button_delete'] = 'Delete';
$_['button_edit'] = 'Edit';
$_['button_view'] = 'View';
$_['button_back'] = 'Back';

// Common messages
$_['text_success'] = 'Operation completed successfully.';
$_['text_error'] = 'An error occurred.';
$_['text_confirm'] = 'Are you sure?';
$_['text_loading'] = 'Loading...';

// Common form labels
$_['label_name'] = 'Name';
$_['label_email'] = 'Email';
$_['label_status'] = 'Status';
$_['label_created'] = 'Created';
$_['label_updated'] = 'Updated';
```

### 4. Handle Missing Translations

```php
public function getTranslation($key, $default = null) {
    $text = $this->language->get($key);
    
    // If translation not found, return default or key
    if ($text === $key) {
        return $default ?? $key;
    }
    
    return $text;
}
```

### 5. Use Pluralization

**Language file:**

```php
$_['text_item_count_zero'] = 'No items';
$_['text_item_count_one'] = '1 item';
$_['text_item_count_many'] = '%d items';
```

**Helper function:**

```php
function pluralize($count, $key) {
    if ($count == 0) {
        return $this->language->get($key . '_zero');
    } elseif ($count == 1) {
        return $this->language->get($key . '_one');
    } else {
        return sprintf($this->language->get($key . '_many'), $count);
    }
}

// Usage
$text = $this->pluralize(5, 'text_item_count'); // "5 items"
```

---

## Advanced Usage

### Date and Time Formatting

**Language file:**

```php
$_['date_format'] = 'F j, Y';  // January 1, 2024
$_['time_format'] = 'g:i A';   // 3:30 PM
$_['datetime_format'] = 'F j, Y g:i A';
```

**Controller:**

```php
$this->load->language('common');

$data['created'] = date(
    $this->language->get('date_format'),
    strtotime($user['created_at'])
);
```

### Currency Formatting

**Language file:**

```php
$_['currency_symbol'] = '$';
$_['currency_position'] = 'left';  // left or right
$_['decimal_separator'] = '.';
$_['thousand_separator'] = ',';
```

**Helper function:**

```php
function formatCurrency($amount) {
    $this->load->language('common');
    
    $symbol = $this->language->get('currency_symbol');
    $position = $this->language->get('currency_position');
    
    $formatted = number_format(
        $amount,
        2,
        $this->language->get('decimal_separator'),
        $this->language->get('thousand_separator')
    );
    
    if ($position === 'left') {
        return $symbol . $formatted;
    } else {
        return $formatted . $symbol;
    }
}
```

### Language-Specific Validation Messages

```php
public function validateEmail($email) {
    $this->load->language('validation');
    
    if (empty($email)) {
        return $this->language->get('error_email_required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->language->get('error_email_invalid');
    }
    
    return null; // Valid
}
```

### Email Templates

**File:** `app/language/en-gb/email/welcome.php`

```php
<?php

$_['subject'] = 'Welcome to %s';
$_['greeting'] = 'Hello %s,';
$_['body'] = 'Thank you for registering at our website. Your account has been created successfully.';
$_['login_link'] = 'Click here to login: %s';
$_['footer'] = 'Best regards,<br>The %s Team';
```

**Controller:**

```php
public function sendWelcomeEmail($user) {
    $this->load->language('email/welcome');
    
    $subject = sprintf(
        $this->language->get('subject'),
        CONFIG_APP_NAME
    );
    
    $body = $this->language->get('greeting') . '<br><br>';
    $body .= $this->language->get('body') . '<br><br>';
    $body .= sprintf(
        $this->language->get('login_link'),
        CONFIG_APP_URL . '/login'
    );
    $body .= '<br><br>';
    $body .= sprintf(
        $this->language->get('footer'),
        CONFIG_APP_NAME
    );
    
    $this->mail->send($user['email'], $subject, $body);
}
```

---

## Related Documentation

- **[Controllers](07-controllers.md)** - Loading language files in controllers
- **[Views](10-views.md)** - Using translations in views
- **[Configuration](02-configuration.md)** - Setting default language

---

**Previous:** [Libraries](12-libraries.md)  
**Next:** [Helpers](14-helpers.md)
