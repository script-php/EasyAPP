<?php

/**
 * @package      Unit Test Example
 * @author       EasyAPP Framework
 * @description  Example unit test - tests individual components in isolation
 */

require_once 'system/TestCase.php';

/**
 * UserValidationUnitTest - Tests individual validation methods without dependencies
 * 
 * This is a UNIT TEST because it:
 * - Tests individual methods in isolation
 * - Doesn't connect to database
 * - Doesn't load external dependencies
 * - Runs very fast (milliseconds)
 * - Focuses on pure logic validation
 */
class UserValidationUnitTest extends TestCase {
    
    public function testEmailValidation() {
        // Test valid email addresses
        $this->assertTrue($this->isValidEmail('user@example.com'));
        $this->assertTrue($this->isValidEmail('test.email+tag@domain.co.uk'));
        
        // Test invalid email addresses
        $this->assertFalse($this->isValidEmail('invalid-email'));
        $this->assertFalse($this->isValidEmail(''));
        $this->assertFalse($this->isValidEmail('@domain.com'));
    }
    
    public function testPasswordStrength() {
        // Test strong passwords
        $this->assertTrue($this->isStrongPassword('SecurePass123!'));
        $this->assertTrue($this->isStrongPassword('MyP@ssw0rd'));
        
        // Test weak passwords
        $this->assertFalse($this->isStrongPassword('weak'));
        $this->assertFalse($this->isStrongPassword('123456'));
        $this->assertFalse($this->isStrongPassword(''));
    }
    
    public function testUsernameValidation() {
        // Test valid usernames
        $this->assertTrue($this->isValidUsername('john_doe'));
        $this->assertTrue($this->isValidUsername('user123'));
        $this->assertTrue($this->isValidUsername('testuser'));
        
        // Test invalid usernames
        $this->assertFalse($this->isValidUsername(''));
        $this->assertFalse($this->isValidUsername('a'));  // too short
        $this->assertFalse($this->isValidUsername('user name')); // has space
        $this->assertFalse($this->isValidUsername('user@name')); // special char
    }
    
    public function testDataSanitization() {
        // Test HTML tag removal
        $this->assertEquals('alert("xss")Hello World', $this->sanitizeInput('<script>alert("xss")</script>Hello World'));
        $this->assertEquals('Clean Text', $this->sanitizeInput('Clean Text'));
        
        // Test SQL injection prevention
        $this->assertEquals("Don''t hack me", $this->sanitizeInput("Don't hack me"));
    }
    
    public function testArrayOperations() {
        $users = ['john', 'jane', 'bob'];
        
        $this->assertCount(3, $users);
        $this->assertContains('jane', $users);
        $this->assertTrue(in_array('bob', $users));
    }
    
    // ============================================================================
    // HELPER METHODS (Simulating application logic)
    // These would normally be in your actual application classes
    // ============================================================================
    
    private function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    private function isStrongPassword($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;  // uppercase
        if (!preg_match('/[a-z]/', $password)) return false;  // lowercase
        if (!preg_match('/[0-9]/', $password)) return false;  // number
        if (!preg_match('/[!@#$%^&*]/', $password)) return false;  // special char
        
        return true;
    }
    
    private function isValidUsername($username) {
        if (empty($username) || strlen($username) < 3) return false;
        if (strlen($username) > 20) return false;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) return false;
        
        return true;
    }
    
    private function sanitizeInput($input) {
        // Remove HTML tags
        $clean = strip_tags($input);
        
        // Escape single quotes for SQL safety
        $clean = str_replace("'", "''", $clean);
        
        return $clean;
    }
}