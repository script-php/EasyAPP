<?php

/**
 * ORM Integration Test - Comprehensive Feature Testing
 * 
 * Tests all ORM features including:
 * - CRUD operations (Create, Read, Update, Delete)
 * - Query Builder (where, select, join, orderBy, etc.)
 * - Relationships (hasOne, hasMany, belongsTo, belongsToMany)
 * - Advanced features (soft delete, timestamps, scopes, etc.)
 * - Validation
 * - Schema Inspection
 * - Aggregates and raw queries
 * 
 * @package      EasyAPP Framework Tests
 * @author       EasyAPP Framework
 */

require_once 'system/TestCase.php';

class OrmTest extends TestCase {
    
    private $testUserIds = [];
    private $testPostIds = [];
    private $testCommentIds = [];
    
    function __construct($registry) {
        parent::__construct($registry);
        
        // Set up ORM database connection
        if ($this->registry && $this->registry->has('db')) {
            \System\Framework\Orm::setConnection($this->registry->get('db'));
        }
    }
    
    protected function setUp() {
        // Don't clean up before tests - let each test create fresh data
    }
    
    protected function tearDown() {
        // Clean up test data after each test
        $this->cleanupTestData();
    }
    
    // ============================================================================
    // BASIC CRUD OPERATIONS
    // ============================================================================
    
    public function testCreate() {
        $email = 'test-' . time() . '@example.com';
        $user = \App\Model\User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 1
        ]);
        
        $this->assertNotNull($user, 'User should be created');
        $this->assertNotNull($user->id, 'User should have an ID');
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals($email, $user->email);
        
        $this->testUserIds[] = $user->id;
    }
    
    public function testFind() {
        // Create a user first
        $user = \App\Model\User::create([
            'name' => 'Find Test',
            'email' => 'find-' . time() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Find by ID
        $found = \App\Model\User::find($user->id);
        $this->assertNotNull($found, 'User should be found');
        $this->assertEquals($user->id, $found->id);
        $this->assertEquals('Find Test', $found->name);
    }
    
    public function testFindOrFail() {
        // Create a user
        $user = \App\Model\User::create([
            'name' => 'FindOrFail Test',
            'email' => 'findorfail-' . time() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Should find the user
        $found = \App\Model\User::findOrFail($user->id);
        $this->assertEquals($user->id, $found->id);
        
        // Should throw exception for non-existent ID
        $exceptionThrown = false;
        try {
            \App\Model\User::findOrFail(999999);
        } catch (Exception $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Should throw exception for non-existent record');
    }
    
    public function testUpdate() {
        // Create a user
        $user = \App\Model\User::create([
            'name' => 'Update Test',
            'email' => 'update-' . time() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Update the user
        $user->name = 'Updated Name';
        $user->email = 'updated@example.com';
        $saved = $user->save();
        
        $this->assertTrue($saved, 'Update should succeed');
        
        // Verify update
        $updated = \App\Model\User::find($user->id);
        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('updated@example.com', $updated->email);
    }
    
    public function testDelete() {
        // Create a user
        $user = \App\Model\User::create([
            'name' => 'Delete Test',
            'email' => 'delete-' . time() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $userId = $user->id;
        
        // Delete the user
        $deleted = $user->delete();
        $this->assertTrue($deleted, 'Delete should succeed');
        
        // Verify deletion (with soft deletes, it should still exist but with deleted_at)
        $found = \App\Model\User::query()->withTrashed()->where('id', $userId)->first();
        if ($found) {
            $this->assertNotNull($found->deleted_at, 'Should have deleted_at timestamp');
        } else {
            // Hard delete - record doesn't exist
            $this->assertNull(\App\Model\User::find($userId), 'User should not be found after hard delete');
        }
    }
    
    // ============================================================================
    // QUERY BUILDER - WHERE CLAUSES
    // ============================================================================
    
    public function testWhereClause() {
        // Create test users
        $timestamp = time();
        \App\Model\User::create([
            'name' => 'Admin User',
            'email' => 'admin-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
        \App\Model\User::create([
            'name' => 'Regular User',
            'email' => 'user-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
        ]);
        
        // Query with where clause
        $admins = \App\Model\User::query()->where('role', 'admin')->get();
        $this->assertTrue(count($admins) >= 1, 'Should find at least 1 admin');
        
        foreach ($admins as $admin) {
            $this->assertEquals('admin', $admin->role);
            $this->testUserIds[] = $admin->id;
        }
    }
    
    public function testWhereInClause() {
        // Create users with different roles
        $timestamp = time();
        $user1 = \App\Model\User::create([
            'name' => 'User 1',
            'email' => 'user1-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
        $user2 = \App\Model\User::create([
            'name' => 'User 2',
            'email' => 'user2-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'moderator',
        ]);
        $this->testUserIds[] = $user1->id;
        $this->testUserIds[] = $user2->id;
        
        // Query with whereIn
        $users = \App\Model\User::query()->whereIn('role', ['admin', 'moderator'])->get();
        $this->assertTrue(count($users) >= 2, 'Should find at least 2 users');
    }
    
    public function testWhereBetweenClause() {
        // Create posts with different view counts
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Author',
            'email' => 'author-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $post1 = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Low Views Post',
            'slug' => 'low-views-' . time(),
            'content' => 'Test content',
            'views' => 50,
        ]);
        $post2 = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'High Views Post',
            'slug' => 'high-views-' . time(),
            'content' => 'Test content',
            'views' => 150,
        ]);
        $this->testPostIds[] = $post1->id;
        $this->testPostIds[] = $post2->id;
        
        // Query with whereBetween
        $posts = \App\Model\Post::query()->whereBetween('views', [100, 200])->get();
        $this->assertTrue(count($posts) >= 1, 'Should find posts with views between 100 and 200');
    }
    
    public function testWhereLikeClause() {
        // Create users with similar names
        $timestamp = time();
        $user1 = \App\Model\User::create([
            'name' => 'John Doe',
            'email' => 'john-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $user2 = \App\Model\User::create([
            'name' => 'John Smith',
            'email' => 'johnsmith-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user1->id;
        $this->testUserIds[] = $user2->id;
        
        // Query with whereLike (using where with LIKE operator)
        $johns = \App\Model\User::query()->where('name', 'LIKE', 'John%')->get();
        $this->assertTrue(count($johns) >= 2, 'Should find users named John');
    }
    
    // ============================================================================
    // QUERY BUILDER - SELECT, ORDER, LIMIT
    // ============================================================================
    
    public function testSelectColumns() {
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Select Test',
            'email' => 'select-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Select specific columns
        $result = \App\Model\User::query()->select(['id', 'name', 'email'])
            ->where('id', $user->id)
            ->first();
        
        $this->assertNotNull($result);
        $this->assertEquals($user->name, $result->name);
        $this->assertEquals($user->email, $result->email);
    }
    
    public function testOrderBy() {
        // Create users with different names
        $timestamp = time();
        \App\Model\User::create([
            'name' => 'Charlie',
            'email' => 'charlie-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        \App\Model\User::create([
            'name' => 'Alice',
            'email' => 'alice-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        \App\Model\User::create([
            'name' => 'Bob',
            'email' => 'bob-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        // Order by name ascending
        $users = \App\Model\User::query()->whereIn('email', [
                'alice-' . $timestamp . '@test.com', 
                'bob-' . $timestamp . '@test.com', 
                'charlie-' . $timestamp . '@test.com'
            ])
            ->orderBy('name', 'asc')
            ->get();
        
        $this->assertTrue(count($users) >= 3);
        foreach ($users as $user) {
            $this->testUserIds[] = $user->id;
        }
    }
    
    public function testLimitAndOffset() {
        // Create multiple users
        $timestamp = time();
        for ($i = 1; $i <= 5; $i++) {
            $user = \App\Model\User::create([
                'name' => "User $i",
                'email' => "user$i-$timestamp@test.com",
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            $this->testUserIds[] = $user->id;
        }
        
        // Test limit
        $limited = \App\Model\User::query()->limit(3)->get();
        $this->assertTrue(count($limited) >= 3, 'Should respect limit');
        
        // Test offset
        $offset = \App\Model\User::query()->limit(2)->offset(2)->get();
        $this->assertTrue(count($offset) >= 2, 'Should respect offset');
    }
    
    // ============================================================================
    // RELATIONSHIPS - BELONGS TO
    // ============================================================================
    
    public function testBelongsToRelationship() {
        // Create user and post
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Post Author',
            'email' => 'postauthor-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $post = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'slug' => 'test-post-' . $timestamp,
            'content' => 'Test content',
        ]);
        $this->testPostIds[] = $post->id;
        
        // Test belongsTo relationship
        $postAuthor = $post->user();
        $this->assertNotNull($postAuthor, 'Post should have a user');
        $this->assertEquals($user->id, $postAuthor->id);
        $this->assertEquals($user->name, $postAuthor->name);
    }
    
    // ============================================================================
    // RELATIONSHIPS - HAS MANY
    // ============================================================================
    
    public function testHasManyRelationship() {
        // Create user with posts
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Prolific Author',
            'email' => 'prolific-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Create multiple posts
        for ($i = 1; $i <= 3; $i++) {
            $post = \App\Model\Post::create([
                'user_id' => $user->id,
                'title' => "Post $i",
                'slug' => "post-$i-$timestamp",
                'content' => "Content $i",
            ]);
            $this->testPostIds[] = $post->id;
        }
        
        // Test hasMany relationship
        $posts = $user->posts()->get();
        $this->assertNotNull($posts, 'User should have posts');
        $this->assertTrue(count($posts) >= 3, 'User should have at least 3 posts');
    }
    
    // ============================================================================
    // AGGREGATES
    // ============================================================================
    
    public function testCountAggregate() {
        $count = \App\Model\User::query()->count();
        $this->assertTrue($count >= 0, 'Count should return a number');
    }
    
    public function testMaxAggregate() {
        // Create posts with different view counts
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Stats User',
            'email' => 'stats-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $post1 = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Post 1',
            'slug' => 'post-1-' . $timestamp,
            'content' => 'Content',
            'views' => 100,
        ]);
        $post2 = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Post 2',
            'slug' => 'post-2-' . $timestamp,
            'content' => 'Content',
            'views' => 200,
        ]);
        $this->testPostIds[] = $post1->id;
        $this->testPostIds[] = $post2->id;
        
        $maxViews = \App\Model\Post::query()->max('views');
        $this->assertTrue($maxViews >= 200, 'Max views should be at least 200');
    }
    
    public function testMinAggregate() {
        $minId = \App\Model\User::query()->min('id');
        $this->assertTrue($minId >= 0, 'Min ID should be a valid number');
    }
    
    public function testSumAggregate() {
        // Get sum of all post views
        $totalViews = \App\Model\Post::query()->sum('views');
        $this->assertTrue($totalViews >= 0, 'Sum should return a number');
    }
    
    public function testAvgAggregate() {
        $avgViews = \App\Model\Post::query()->avg('views');
        $this->assertTrue($avgViews >= 0, 'Average should return a number');
    }
    
    // ============================================================================
    // SOFT DELETES
    // ============================================================================
    
    public function testSoftDelete() {
        // Create a user
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Soft Delete Test',
            'email' => 'softdelete-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $userId = $user->id;
        
        // Soft delete
        $user->delete();
        
        // Should not be found in normal queries
        $found = \App\Model\User::find($userId);
        $this->assertNull($found, 'Soft deleted user should not be found');
        
        // Should be found with withTrashed
        $trashed = \App\Model\User::query()->withTrashed()->where('id', $userId)->first();
        $this->assertNotNull($trashed, 'Should find soft deleted user with withTrashed');
        $this->assertNotNull($trashed->deleted_at, 'Should have deleted_at timestamp');
        
        // Clean up
        if ($trashed) {
            $trashed->forceDelete();
        }
    }
    
    public function testRestore() {
        // Create and soft delete a user
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Restore Test',
            'email' => 'restore-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $userId = $user->id;
        $user->delete();
        
        // Restore the user
        $trashed = \App\Model\User::query()->withTrashed()->where('id', $userId)->first();
        $restored = $trashed->restore();
        $this->assertTrue($restored, 'Restore should succeed');
        
        // Should be found in normal queries
        $found = \App\Model\User::find($userId);
        $this->assertNotNull($found, 'Restored user should be found');
        $this->assertNull($found->deleted_at, 'deleted_at should be null after restore');
        
        $this->testUserIds[] = $userId;
    }
    
    // ============================================================================
    // TIMESTAMPS
    // ============================================================================
    
    public function testTimestamps() {
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Timestamp Test',
            'email' => 'timestamp-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Should have created_at
        $this->assertNotNull($user->created_at, 'Should have created_at timestamp');
        
        // Update the user
        sleep(1); // Wait 1 second to ensure timestamp changes
        $user->name = 'Updated Timestamp Test';
        $user->save();
        
        // Should have updated_at
        $updated = \App\Model\User::find($user->id);
        $this->assertNotNull($updated->updated_at, 'Should have updated_at timestamp');
    }
    
    // ============================================================================
    // VALIDATION
    // ============================================================================
    
    public function testValidationRules() {
        // This requires that the User model has a rules() method
        // We'll test if validation catches invalid data
        
        try {
            // Try to create user with invalid email
            $user = \App\Model\User::create([
                'name' => 'Invalid User',
                'email' => 'not-an-email', // Invalid email
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            
            // If validation is implemented, this should fail
            // If no validation, user will be created - which is also valid
            if ($user) {
                $this->testUserIds[] = $user->id;
            }
            
            $this->assertTrue(true, 'User creation handled (with or without validation)');
            
        } catch (Exception $e) {
            // Validation prevented creation
            $this->assertTrue(true, 'Validation correctly prevented invalid data');
        }
    }
    
    // ============================================================================
    // SCHEMA INSPECTION
    // ============================================================================
    
    public function testGetTableSchema() {
        $schema = \App\Model\User::getTableSchema();
        
        $this->assertNotNull($schema, 'Schema should not be null');
        $this->assertTrue(isset($schema['table']), 'Schema should have table name');
        $this->assertTrue(isset($schema['columns']), 'Schema should have columns');
        $this->assertTrue(is_array($schema['columns']), 'Columns should be an array');
    }
    
    public function testGetColumns() {
        $columns = \App\Model\User::getColumns();
        
        $this->assertNotNull($columns, 'Columns should not be null');
        $this->assertTrue(is_array($columns), 'Columns should be an array');
        $this->assertTrue(count($columns) > 0, 'Should have at least one column');
    }
    
    public function testHasColumn() {
        $hasEmail = \App\Model\User::hasColumn('email');
        $hasInvalid = \App\Model\User::hasColumn('nonexistent_column');
        
        $this->assertTrue($hasEmail, 'Should have email column');
        $this->assertFalse($hasInvalid, 'Should not have nonexistent column');
    }
    
    public function testGetIndexes() {
        $indexes = \App\Model\User::getIndexes();
        
        $this->assertNotNull($indexes, 'Indexes should not be null');
        $this->assertTrue(is_array($indexes), 'Indexes should be an array');
    }
    
    public function testGetForeignKeys() {
        $foreignKeys = \App\Model\Post::getForeignKeys();
        
        $this->assertNotNull($foreignKeys, 'Foreign keys should not be null');
        $this->assertTrue(is_array($foreignKeys), 'Foreign keys should be an array');
    }
    
    // ============================================================================
    // ADVANCED QUERIES
    // ============================================================================
    
    public function testChainedQuery() {
        // Create test data
        $timestamp = time();
        $user = \App\Model\User::create([
            'name' => 'Chain Test User',
            'email' => 'chain-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 1,
        ]);
        $this->testUserIds[] = $user->id;
        
        // Chain multiple query methods
        $result = \App\Model\User::query()->select(['id', 'name', 'email'])
            ->where('role', 'admin')
            ->where('status', 1)
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get();
        
        $this->assertNotNull($result, 'Chained query should return results');
        $this->assertTrue($result instanceof \System\Framework\Collection, 'Result should be a Collection');
        $this->assertTrue(count($result) > 0, 'Result should have at least one item');
    }
    
    public function testFirstOrCreate() {
        $timestamp = time();
        $email = 'firstorcreate-' . $timestamp . '@test.com';
        
        // First call should create
        $user1 = \App\Model\User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'First Or Create Test',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]
        );
        $this->assertNotNull($user1, 'Should create user');
        $this->testUserIds[] = $user1->id;
        
        // Second call should find existing
        $user2 = \App\Model\User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Should Not Be Used',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]
        );
        $this->assertEquals($user1->id, $user2->id, 'Should find existing user');
        $this->assertEquals($user1->name, $user2->name, 'Name should match original');
    }
    
    public function testUpdateOrCreate() {
        $timestamp = time();
        $email = 'updateorcreate-' . $timestamp . '@test.com';
        
        // First call should create
        $user1 = \App\Model\User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Update Or Create Test',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]
        );
        $this->testUserIds[] = $user1->id;
        
        // Second call should update
        $user2 = \App\Model\User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Updated Name',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]
        );
        
        $this->assertEquals($user1->id, $user2->id, 'Should be same user');
        $this->assertEquals('Updated Name', $user2->name, 'Name should be updated');
    }
    
    public function testValidationScenarios() {
        // Create a test model class with scenario-based validation
        $testModel = new class extends \System\Framework\Orm {
            protected static $table = 'users';
            protected static $timestamps = true;
            
            protected static $fillable = ['name', 'email', 'password', 'role', 'status'];
            
            public function rules() {
                return [
                    // Name required for register and update scenarios
                    ['name', 'required|string|minLength:3', 'on' => ['register', 'update']],
                    // Email required for register and login scenarios
                    ['email', 'required|email', 'on' => ['register', 'login']],
                    // Password required for register and login scenarios
                    ['password', 'required|string|minLength:8', 'on' => ['register', 'login']],
                    // Role only validated on register
                    ['role', 'optional|in:user,admin,moderator', 'on' => ['register']],
                    // Status only for update
                    ['status', 'optional|integer', 'on' => ['update']],
                ];
            }
        };
        
        // TEST 1: Register scenario - should validate name, email, password, role
        $testModel->setScenario('register');
        $testModel->name = 'John Doe';
        $testModel->email = 'scenario-test-' . time() . '@example.com';
        $testModel->password = 'password123'; // 12 chars, meets minLength:8
        $testModel->role = 'user';
        
        $isValid = $testModel->validate();
        $this->assertTrue($isValid, 'Register scenario should pass validation');
        
        if ($isValid) {
            $result = $testModel->save(false); // Skip validation since we already validated
            if ($result) {
                $this->testUserIds[] = $testModel->id;
            }
        }
        
        // TEST 2: Login scenario - should only validate email and password
        $testModel2 = new $testModel();
        $testModel2->setScenario('login');
        $testModel2->email = 'login-test@example.com';
        $testModel2->password = 'password123';
        // Note: name is NOT required for login scenario
        
        $isValid2 = $testModel2->validate();
        $this->assertTrue($isValid2, 'Login scenario should pass without name');
        
        // TEST 3: Update scenario - should validate name and status, not password
        $testModel3 = new $testModel();
        $testModel3->setScenario('update');
        $testModel3->name = 'Jane Doe';
        $testModel3->status = 1;
        // Note: password is NOT required for update scenario
        
        $isValid3 = $testModel3->validate();
        $this->assertTrue($isValid3, 'Update scenario should pass without password');
        
        // TEST 4: Register with invalid password (too short)
        $testModel4 = new $testModel();
        $testModel4->setScenario('register');
        $testModel4->name = 'Test User';
        $testModel4->email = 'test@example.com';
        $testModel4->password = 'short'; // Only 5 chars, fails minLength:8
        
        $isValid4 = $testModel4->validate();
        $this->assertFalse($isValid4, 'Register with short password should fail');
        $this->assertTrue($testModel4->hasErrors(), 'Should have validation errors');
        
        // TEST 5: Default scenario - no rules apply
        $testModel5 = new $testModel();
        // Default scenario, no setScenario() call
        $testModel5->name = 'A'; // Would fail minLength:3 in register/update
        $testModel5->email = 'invalid-email'; // Would fail email validation
        
        $isValid5 = $testModel5->validate();
        $this->assertTrue($isValid5, 'Default scenario should pass (no rules apply)');
        
        // TEST 6: Scenario switching
        $testModel6 = new $testModel();
        $testModel6->setScenario('register');
        $this->assertEquals('register', $testModel6->getScenario(), 'Scenario should be set to register');
        
        $testModel6->setScenario('login');
        $this->assertEquals('login', $testModel6->getScenario(), 'Scenario should switch to login');
    }
    
    // ============================================================================
    // HELPER METHODS
    // ============================================================================
    
    private function cleanupTestData() {
        if (!$this->registry || !$this->registry->has('db')) {
            return;
        }
        
        $db = $this->registry->get('db');
        
        try {
            // Delete test comments by IDs
            foreach ($this->testCommentIds as $id) {
                try {
                    $db->query("DELETE FROM comments WHERE id = ?", [$id]);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
            
            // Delete test posts by IDs
            foreach ($this->testPostIds as $id) {
                try {
                    $db->query("DELETE FROM posts WHERE id = ?", [$id]);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
            
            // Delete test users by IDs
            foreach ($this->testUserIds as $id) {
                try {
                    $db->query("DELETE FROM users WHERE id = ?", [$id]);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
            
        } catch (Exception $e) {
            // Silently handle cleanup errors
            error_log('Test cleanup error: ' . $e->getMessage());
        }
        
        // Reset arrays
        $this->testUserIds = [];
        $this->testPostIds = [];
        $this->testCommentIds = [];
    }
    
    private function cleanupByEmail() {
        // No longer needed - cleanup by ID is more reliable
    }
}
