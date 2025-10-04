<?php

/**
 * @package      Integration Test Example
 * @author       EasyAPP Framework
 * @description  Example integration test - tests component integration with real dependencies
 */

// Set up framework integration
if (!defined('PATH')) {
    define('PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
}

require_once PATH . 'system/Framework.php';
require_once PATH . 'system/TestCase.php';

use System\Framework\Tables;

/**
 * UserSystemIntegrationTest - Tests complete user workflows with real database
 * 
 * This is an INTEGRATION TEST because it:
 * - Tests multiple components working together
 * - Uses real database connections
 * - Tests actual data persistence
 * - Runs slower (seconds) due to I/O operations
 * - Verifies end-to-end functionality
 */
class UserSystemIntegrationTest extends TestCase {
    private $db;
    private $tables;
    
    protected function setUp() {
        // Initialize framework with real database connection
        $this->registry = initializeFramework();
        
        // Set up database connection
        $this->db = new System\Framework\Db(
            CONFIG_DB_DRIVER,
            CONFIG_DB_HOSTNAME, 
            CONFIG_DB_DATABASE,
            CONFIG_DB_USERNAME,
            CONFIG_DB_PASSWORD,
            CONFIG_DB_PORT,
            '',  // encoding
            ''   // options
        );
        
        $this->registry->set('db', $this->db);
        $this->tables = new Tables($this->registry);
        
        // Create test table
        $this->createTestTable();
    }
    
    protected function tearDown() {
        // Clean up test data
        try {
            $this->db->query("DROP TABLE IF EXISTS `test_users`");
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }
    
    public function testUserRegistrationWorkflow() {
        // Test complete user registration workflow
        
        // 1. Create user data
        $userData = [
            'username' => 'integrationtest_' . time(),
            'email' => 'test' . time() . '@example.com',
            'password' => password_hash('TestPassword123!', PASSWORD_DEFAULT)
        ];
        
        // 2. Insert user into database
        $result = $this->db->query(
            "INSERT INTO `test_users` (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
            [$userData['username'], $userData['email'], $userData['password']]
        );
        
        $this->assertTrue($result !== false, 'User insertion should succeed');
        
        // 3. Verify user was created
        $user = $this->db->query(
            "SELECT * FROM `test_users` WHERE username = ? AND email = ?",
            [$userData['username'], $userData['email']]
        );
        
        $this->assertNotNull($user->row, 'User should be found in database');
        $this->assertEquals($userData['username'], $user->row['username']);
        $this->assertEquals($userData['email'], $user->row['email']);
    }
    
    public function testUserAuthenticationFlow() {
        // Test user authentication with database validation
        
        $testPassword = 'AuthTest123!';
        $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        
        // Create test user
        $username = 'authtest_' . time();
        $this->db->query(
            "INSERT INTO `test_users` (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
            [$username, $username . '@test.com', $hashedPassword]
        );
        
        // Test successful authentication
        $user = $this->db->query(
            "SELECT * FROM `test_users` WHERE username = ?",
            [$username]
        );
        
        $this->assertNotNull($user->row, 'User should exist');
        $this->assertTrue(
            password_verify($testPassword, $user->row['password']),
            'Password verification should succeed'
        );
        
        // Test failed authentication
        $this->assertFalse(
            password_verify('WrongPassword', $user->row['password']),
            'Wrong password should fail verification'
        );
    }
    
    public function testDatabaseSchemaIntegration() {
        // Test that Tables class integration works correctly
        
        // Create a new table using Tables class
        $this->tables->clearTables();
        $this->tables->table('integration_test_posts')
            ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
            ->column('user_id')->type('INT(11)')->notNull(true)
            ->column('title')->type('VARCHAR(255)')->notNull(true)
            ->column('content')->text()
            ->column('created_at')->timestamp(true)
            ->foreign('user_id', 'test_users', 'id', true); // CASCADE delete
        
        $this->tables->create();
        
        // Verify table was created
        $tables = $this->db->query("SHOW TABLES LIKE 'integration_test_posts'");
        $this->assertTrue($tables->num_rows > 0, 'Posts table should be created');
        
        // Test foreign key relationship
        $user = $this->db->query("SELECT id FROM `test_users` LIMIT 1");
        if ($user->num_rows > 0) {
            $userId = $user->row['id'];
            
            // Insert post
            $this->db->query(
                "INSERT INTO `integration_test_posts` (user_id, title, content) VALUES (?, ?, ?)",
                [$userId, 'Test Post', 'This is a test post content']
            );
            
            // Verify relationship
            $post = $this->db->query(
                "SELECT p.*, u.username FROM `integration_test_posts` p 
                 JOIN `test_users` u ON p.user_id = u.id 
                 WHERE p.user_id = ?",
                [$userId]
            );
            
            $this->assertTrue($post->num_rows > 0, 'Post should be linked to user');
        }
        
        // Clean up
        $this->db->query("DROP TABLE IF EXISTS `integration_test_posts`");
    }
    
    public function testDataConsistency() {
        // Test data consistency across multiple operations
        
        $testData = [
            ['username' => 'user1_' . time(), 'email' => 'user1@test.com'],
            ['username' => 'user2_' . time(), 'email' => 'user2@test.com'],
            ['username' => 'user3_' . time(), 'email' => 'user3@test.com']
        ];
        
        // Insert multiple users
        foreach ($testData as $data) {
            $this->db->query(
                "INSERT INTO `test_users` (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
                [$data['username'], $data['email'], 'dummy_password']
            );
        }
        
        // Verify all users were inserted
        $count = $this->db->query("SELECT COUNT(*) as total FROM `test_users`");
        $this->assertTrue($count->row['total'] >= 3, 'At least 3 users should exist');
        
        // Test batch operations
        $usernames = array_column($testData, 'username');
        $placeholders = str_repeat('?,', count($usernames) - 1) . '?';
        
        $batchResult = $this->db->query(
            "SELECT COUNT(*) as found FROM `test_users` WHERE username IN ({$placeholders})",
            $usernames
        );
        
        $this->assertEquals(3, $batchResult->row['found'], 'All test users should be found');
    }
    
    public function testTransactionIntegrity() {
        // Test database transaction handling
        
        try {
            // Start transaction
            $this->db->query("START TRANSACTION");
            
            // Insert user
            $this->db->query(
                "INSERT INTO `test_users` (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
                ['transaction_test', 'transaction@test.com', 'password']
            );
            
            // Verify user exists in transaction
            $user = $this->db->query("SELECT * FROM `test_users` WHERE username = 'transaction_test'");
            $this->assertTrue($user->num_rows > 0, 'User should exist in transaction');
            
            // Rollback transaction
            $this->db->query("ROLLBACK");
            
            // Verify user was rolled back
            $userAfterRollback = $this->db->query("SELECT * FROM `test_users` WHERE username = 'transaction_test'");
            $this->assertTrue($userAfterRollback->num_rows === 0, 'User should not exist after rollback');
            
        } catch (Exception $e) {
            // Ensure rollback on error
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }
    
    // ============================================================================
    // HELPER METHODS
    // ============================================================================
    
    private function createTestTable() {
        // Create test users table for integration testing
        $this->tables->clearTables();
        $this->tables->table('test_users')
            ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
            ->column('username')->type('VARCHAR(50)')->notNull(true)->unique('username')
            ->column('email')->type('VARCHAR(100)')->notNull(true)->unique('email')
            ->column('password')->type('VARCHAR(255)')->notNull(true)
            ->column('created_at')->timestamp(true);
        
        try {
            $this->tables->create();
        } catch (Exception $e) {
            // Table might already exist, that's okay
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
}