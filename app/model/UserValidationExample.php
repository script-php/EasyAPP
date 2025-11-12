<?php

/**
 * Example: User Model with Validation
 * 
 * This demonstrates how to use validation rules in your ORM models
 */

namespace App\Model;

use System\Framework\Orm;

class UserValidationExample extends Orm {
    
    protected static $table = 'users';
    
    protected static $fillable = ['name', 'email', 'password', 'age', 'phone', 'website'];
    
    /**
     * Define validation rules
     * These rules are automatically applied when save() is called
     */
    public function rules() {
        return [
            // Basic validation
            ['name', 'required|string|minLength:3|maxLength:50'],
            ['email', 'required|email|unique'],
            ['password', 'required|string|minLength:8', 'on' => ['register']],
            
            // Optional fields
            ['age', 'optional|integer|between:18,120'],
            ['phone', 'optional|phone'],
            ['website', 'optional|url'],
        ];
    }
    
    /**
     * Define scenarios for different contexts
     */
    public function scenarios() {
        return [
            'register' => ['name', 'email', 'password', 'age', 'phone'],
            'update' => ['name', 'email', 'age', 'phone', 'website'],
            'login' => ['email', 'password'],
        ];
    }
    
    /**
     * Custom validation logic (optional)
     * This runs before save() after rule validation passes
     */
    protected function beforeSave() {
        // Example: Hash password if it changed
        if (!empty($this->password) && !password_verify($this->password, $this->password ?? '')) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
        
        // Example: Custom business rule
        if ($this->age < 18 && $this->email_notifications) {
            $this->addError('Users under 18 cannot receive email notifications');
            return false; // Stop save
        }
        
        return true; // Continue save
    }
}

// ========================================
// USAGE EXAMPLES
// ========================================

// Example 1: Registration with validation
function registerUser() {
    $user = new UserValidationExample();
    $user->setScenario('register');
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->age = $_POST['age'] ?? null;
    
    if ($user->save()) {
        echo "User registered successfully!";
        return true;
    } else {
        echo "Validation errors:\n";
        foreach ($user->getErrors() as $error) {
            echo "- $error\n";
        }
        return false;
    }
}

// Example 2: Update profile (password not required)
function updateProfile($userId) {
    $user = UserValidationExample::find($userId);
    $user->setScenario('update');
    $user->name = $_POST['name'];
    $user->phone = $_POST['phone'] ?? null;
    
    if ($user->save()) {
        echo "Profile updated!";
    } else {
        echo "Errors: " . implode(', ', $user->getErrors());
    }
}

// Example 3: Using fill() with validation
function quickRegister() {
    $user = new UserValidationExample();
    $user->setScenario('register');
    $user->fill($_POST); // Mass assignment
    
    if ($user->save()) {
        return ['success' => true, 'user_id' => $user->id];
    } else {
        return ['success' => false, 'errors' => $user->getErrors()];
    }
}

// Example 4: Manual validation without saving
function validateBeforeProcessing() {
    $user = new UserValidationExample();
    $user->fill($_POST);
    
    if ($user->validate()) {
        // Valid - do some processing
        // ... complex logic ...
        
        // Save later (skip re-validation)
        $user->save(false);
    } else {
        echo "First error: " . $user->getFirstError();
    }
}

// Example 5: Skip validation for admin operations
function adminCreateUser($data) {
    $user = new UserValidationExample();
    $user->fill($data);
    
    // Skip validation - admin knows what they're doing
    $user->save(false);
}

// Example 6: Check specific errors
function handleValidation() {
    $user = new UserValidationExample();
    $user->fill($_POST);
    
    if (!$user->validate()) {
        $errors = $user->getErrors();
        
        // Group errors by field for display
        $formErrors = [];
        foreach ($errors as $error) {
            if (str_contains($error, 'email')) {
                $formErrors['email'][] = $error;
            } elseif (str_contains($error, 'name')) {
                $formErrors['name'][] = $error;
            }
            // etc...
        }
        
        return $formErrors;
    }
}
