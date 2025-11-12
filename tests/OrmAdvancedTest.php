<?php

/**
 * ORM Advanced Features Test - Testing Previously Untested Methods
 * 
 * Tests advanced ORM features including:
 * - Advanced WHERE clauses (orWhere, whereNull, whereNotIn, etc.)
 * - Date/time WHERE clauses
 * - Query helpers (pluck, chunk, paginate, scalar, column)
 * - Increment/Decrement operations
 * - Transactions
 * - Model state methods (refresh, exists, fill)
 * - Array/JSON conversion
 * - Many-to-many relationships (belongsToMany, attach, detach, sync)
 * - Bulk operations (insert, findOrNew)
 * - Advanced joins (leftJoin, rightJoin)
 * 
 * @package      EasyAPP Framework Tests
 * @author       EasyAPP Framework
 */

require_once 'system/TestCase.php';

class OrmAdvancedTest extends TestCase {
    
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
        // Clean setup
    }
    
    protected function tearDown() {
        // Clean up test data after each test
        $this->cleanupTestData();
    }
    
    // ============================================================================
    // ADVANCED WHERE CLAUSES
    // ============================================================================
    
    public function testOrWhere() {
        $timestamp = time();
        
        // Create test users
        $user1 = \App\Model\User::create([
            'name' => 'Alice Test',
            'email' => 'alice-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user1->id;
        
        $user2 = \App\Model\User::create([
            'name' => 'Bob Test',
            'email' => 'bob-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user2->id;
        
        // Test OR WHERE
        $users = \App\Model\User::query()
            ->where('name', 'Alice Test')
            ->orWhere('name', 'Bob Test')
            ->get();
        
        $this->assertTrue(count($users) >= 2, 'Should find at least 2 users with OR condition');
    }
    
    public function testWhereNull() {
        // Most users should NOT have null email, but we can test the query structure
        $users = \App\Model\User::query()
            ->whereNull('deleted_at')
            ->limit(5)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
        $this->assertTrue(is_array($users) || $users instanceof \System\Framework\Collection, 'Should return array or collection');
    }
    
    public function testWhereNotNull() {
        $users = \App\Model\User::query()
            ->whereNotNull('email')
            ->limit(5)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
        
        if (count($users) > 0) {
            $this->assertNotNull($users[0]->email, 'Email should not be null');
        }
    }
    
    public function testWhereNotIn() {
        $timestamp = time();
        
        $user1 = \App\Model\User::create([
            'name' => 'User1',
            'email' => 'user1-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user1->id;
        
        $user2 = \App\Model\User::create([
            'name' => 'User2',
            'email' => 'user2-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user2->id;
        
        // Find users NOT in the specified IDs
        $users = \App\Model\User::query()
            ->whereNotIn('id', [$user1->id])
            ->where('id', $user2->id)
            ->get();
        
        $this->assertTrue(count($users) >= 1, 'Should find users not in exclusion list');
        
        if (count($users) > 0) {
            $this->assertNotEquals($user1->id, $users[0]->id, 'Should not include excluded ID');
        }
    }
    
    public function testWhereNotBetween() {
        // Create users with known IDs
        $timestamp = time();
        
        $user1 = \App\Model\User::create([
            'name' => 'User A',
            'email' => 'usera-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user1->id;
        
        // Query for users NOT between ID 1 and ID 5
        $users = \App\Model\User::query()
            ->whereNotBetween('id', [1, 5])
            ->where('id', $user1->id)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
    }
    
    // ============================================================================
    // DATE/TIME WHERE CLAUSES
    // ============================================================================
    
    public function testWhereDate() {
        $today = date('Y-m-d');
        
        $users = \App\Model\User::query()
            ->whereDate('created_at', '>=', $today)
            ->limit(5)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
        $this->assertTrue(is_array($users) || $users instanceof \System\Framework\Collection, 'Should return collection');
    }
    
    public function testWhereYear() {
        $currentYear = date('Y');
        
        $users = \App\Model\User::query()
            ->whereYear('created_at', '=', $currentYear)
            ->limit(5)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
    }
    
    public function testWhereMonth() {
        $currentMonth = date('m');
        
        $users = \App\Model\User::query()
            ->whereMonth('created_at', '=', $currentMonth)
            ->limit(5)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
    }
    
    public function testWhereTime() {
        $users = \App\Model\User::query()
            ->whereTime('created_at', '>=', '00:00:00')
            ->limit(5)
            ->get();
        
        $this->assertNotNull($users, 'Query should execute successfully');
    }
    
    // ============================================================================
    // QUERY HELPERS
    // ============================================================================
    
    public function testPluck() {
        $timestamp = time();
        
        $user1 = \App\Model\User::create([
            'name' => 'Pluck Test 1',
            'email' => 'pluck1-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user1->id;
        
        $user2 = \App\Model\User::create([
            'name' => 'Pluck Test 2',
            'email' => 'pluck2-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user2->id;
        
        // Pluck emails
        $emails = \App\Model\User::query()
            ->whereIn('id', [$user1->id, $user2->id])
            ->pluck('email');
        
        $this->assertTrue(is_array($emails), 'Pluck should return array');
        $this->assertTrue(count($emails) >= 2, 'Should pluck at least 2 emails');
        $this->assertTrue(in_array($user1->email, $emails), 'Should contain first email');
    }
    
    public function testPluckWithKey() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Pluck Key Test',
            'email' => 'pluckkey-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Pluck name indexed by id
        $names = \App\Model\User::query()
            ->where('id', $user->id)
            ->pluck('name', 'id');
        
        $this->assertTrue(is_array($names), 'Pluck should return array');
        $this->assertTrue(isset($names[$user->id]), 'Array should be keyed by id');
        $this->assertEquals('Pluck Key Test', $names[$user->id], 'Should map correctly');
    }
    
    public function testScalar() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Scalar Test',
            'email' => 'scalar-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Get single scalar value
        $name = \App\Model\User::query()
            ->where('id', $user->id)
            ->select('name')
            ->scalar();
        
        $this->assertEquals('Scalar Test', $name, 'Should return single scalar value');
    }
    
    public function testColumn() {
        $timestamp = time();
        
        $user1 = \App\Model\User::create([
            'name' => 'Column Test 1',
            'email' => 'col1-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user1->id;
        
        $user2 = \App\Model\User::create([
            'name' => 'Column Test 2',
            'email' => 'col2-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user2->id;
        
        // Get column of values
        $names = \App\Model\User::query()
            ->whereIn('id', [$user1->id, $user2->id])
            ->select('name')
            ->column();
        
        $this->assertTrue(is_array($names), 'Column should return array');
        $this->assertTrue(in_array('Column Test 1', $names), 'Should contain first name');
    }
    
    public function testChunk() {
        $timestamp = time();
        
        // Create test data
        for ($i = 1; $i <= 5; $i++) {
            $user = \App\Model\User::create([
                'name' => "Chunk Test $i",
                'email' => "chunk$i-" . $timestamp . "@test.com",
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            $this->testUserIds[] = $user->id;
        }
        
        // Process in chunks
        $processedCount = 0;
        \App\Model\User::query()
            ->whereIn('id', $this->testUserIds)
            ->chunk(2, function($users) use (&$processedCount) {
                $processedCount += count($users);
            });
        
        $this->assertEquals(5, $processedCount, 'Should process all 5 users in chunks');
    }
    
    public function testPaginate() {
        $timestamp = time();
        
        // Create test users
        for ($i = 1; $i <= 10; $i++) {
            $user = \App\Model\User::create([
                'name' => "Paginate Test $i",
                'email' => "paginate$i-" . $timestamp . "@test.com",
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            $this->testUserIds[] = $user->id;
        }
        
        // Paginate
        $result = \App\Model\User::query()
            ->whereIn('id', $this->testUserIds)
            ->paginate(5, 1);
        
        $this->assertNotNull($result, 'Paginate should return result');
        $this->assertTrue(isset($result['data']), 'Should have data key');
        $this->assertTrue(isset($result['total']), 'Should have total key');
        $this->assertTrue(count($result['data']) <= 5, 'Should limit to 5 per page');
    }
    
    // ============================================================================
    // INCREMENT/DECREMENT
    // ============================================================================
    
    public function testIncrement() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Increment Test',
            'email' => 'increment-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'status' => 1,
        ]);
        $this->testUserIds[] = $user->id;
        
        // Increment status
        \App\Model\User::query()
            ->where('id', $user->id)
            ->increment('status', 2);
        
        // Reload and check
        $updated = \App\Model\User::find($user->id);
        $this->assertEquals(3, (int)$updated->status, 'Status should be incremented by 2');
    }
    
    public function testDecrement() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Decrement Test',
            'email' => 'decrement-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'status' => 10,
        ]);
        $this->testUserIds[] = $user->id;
        
        // Decrement status
        \App\Model\User::query()
            ->where('id', $user->id)
            ->decrement('status', 3);
        
        // Reload and check
        $updated = \App\Model\User::find($user->id);
        $this->assertEquals(7, (int)$updated->status, 'Status should be decremented by 3');
    }
    
    // ============================================================================
    // TRANSACTIONS
    // ============================================================================
    
    public function testTransaction() {
        $timestamp = time();
        
        $result = \App\Model\User::transaction(function() use ($timestamp) {
            $user = \App\Model\User::create([
                'name' => 'Transaction Test',
                'email' => 'transaction-' . $timestamp . '@test.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
            ]);
            
            $this->testUserIds[] = $user->id;
            
            return $user->id;
        });
        
        $this->assertNotNull($result, 'Transaction should return result');
        
        // Verify user was created
        $user = \App\Model\User::find($result);
        $this->assertNotNull($user, 'User should be created within transaction');
    }
    
    public function testTransactionRollback() {
        $timestamp = time();
        $userId = null;
        
        try {
            \App\Model\User::transaction(function() use ($timestamp, &$userId) {
                $user = \App\Model\User::create([
                    'name' => 'Rollback Test',
                    'email' => 'rollback-' . $timestamp . '@test.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                ]);
                
                $userId = $user->id;
                
                // Force an exception to trigger rollback
                throw new \Exception('Test rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }
        
        // Verify user was NOT created (rolled back)
        if ($userId) {
            $user = \App\Model\User::find($userId);
            $this->assertNull($user, 'User should not exist after rollback');
        }
        
        $this->assertTrue(true, 'Transaction rollback handled correctly');
    }
    
    public function testManualTransaction() {
        $timestamp = time();
        
        \App\Model\User::beginTransaction();
        
        $user = \App\Model\User::create([
            'name' => 'Manual Transaction',
            'email' => 'manual-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        \App\Model\User::commit();
        
        // Verify committed
        $found = \App\Model\User::find($user->id);
        $this->assertNotNull($found, 'User should be committed');
    }
    
    // ============================================================================
    // MODEL STATE METHODS
    // ============================================================================
    
    public function testExists() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Exists Test',
            'email' => 'exists-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Check if query has results
        $exists = \App\Model\User::query()
            ->where('id', $user->id)
            ->exists();
        
        $this->assertTrue($exists, 'Should return true when records exist');
        
        // Check non-existent
        $notExists = \App\Model\User::query()
            ->where('id', 999999999)
            ->exists();
        
        $this->assertFalse($notExists, 'Should return false when no records exist');
    }
    
    public function testRefresh() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Refresh Test',
            'email' => 'refresh-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Modify name in memory
        $user->name = 'Modified Name';
        $this->assertEquals('Modified Name', $user->name);
        
        // Refresh from database
        $user->refresh();
        
        $this->assertEquals('Refresh Test', $user->name, 'Should reload original value from database');
    }
    
    public function testFill() {
        $user = new \App\Model\User();
        
        $user->fill([
            'name' => 'Fill Test',
            'email' => 'fill@test.com',
            'password' => 'password123',
        ]);
        
        $this->assertEquals('Fill Test', $user->name, 'Fill should set name');
        $this->assertEquals('fill@test.com', $user->email, 'Fill should set email');
    }
    
    public function testForceDelete() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Force Delete Test',
            'email' => 'forcedelete-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        $userId = $user->id;
        
        // Force delete (permanent)
        $user->forceDelete();
        
        // Should not be found even with trashed
        $found = \App\Model\User::query()
            ->withTrashed()
            ->where('id', $userId)
            ->first();
        
        $this->assertNull($found, 'User should be permanently deleted');
    }
    
    public function testOnlyTrashed() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Only Trashed Test',
            'email' => 'onlytrashed-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        $userId = $user->id;
        
        // Soft delete
        $user->delete();
        
        // Find only trashed
        $trashed = \App\Model\User::query()
            ->onlyTrashed()
            ->where('id', $userId)
            ->first();
        
        $this->assertNotNull($trashed, 'Should find soft-deleted user');
        $this->assertNotNull($trashed->deleted_at, 'Should have deleted_at timestamp');
        
        // Clean up
        $trashed->forceDelete();
    }
    
    // ============================================================================
    // ARRAY/JSON CONVERSION
    // ============================================================================
    
    public function testToArray() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'ToArray Test',
            'email' => 'toarray-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $array = $user->toArray();
        
        $this->assertTrue(is_array($array), 'Should return array');
        $this->assertEquals('ToArray Test', $array['name'], 'Should contain name');
        $this->assertEquals($user->email, $array['email'], 'Should contain email');
        $this->assertFalse(isset($array['password']), 'Should hide password (in hidden array)');
    }
    
    public function testToJson() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'ToJson Test',
            'email' => 'tojson-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $json = $user->toJson();
        
        $this->assertTrue(is_string($json), 'Should return JSON string');
        
        $decoded = json_decode($json, true);
        $this->assertEquals('ToJson Test', $decoded['name'], 'JSON should contain name');
    }
    
    public function testAsArray() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'AsArray Test',
            'email' => 'asarray-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Get results as arrays instead of objects
        $users = \App\Model\User::query()
            ->where('id', $user->id)
            ->asArray()
            ->get();
        
        $this->assertTrue(is_array($users) || $users instanceof \System\Framework\Collection, 'Should return array or Collection');
        $this->assertTrue(count($users) > 0, 'Should have at least one result');
        
        // Access first item
        $firstUser = $users[0];
        $this->assertTrue(is_array($firstUser) || is_object($firstUser), 'Individual items should be arrays or objects');
        
        // Check if we can access the name
        if (is_array($firstUser)) {
            $this->assertEquals('AsArray Test', $firstUser['name'], 'Array should contain correct data');
        } else {
            $this->assertEquals('AsArray Test', $firstUser->name, 'Object should contain correct data');
        }
    }
    
    // ============================================================================
    // BULK OPERATIONS
    // ============================================================================
    
    public function testInsertBulk() {
        $timestamp = time();
        
        $records = [
            [
                'name' => 'Bulk Insert 1',
                'email' => 'bulk1-' . $timestamp . '@test.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Bulk Insert 2',
                'email' => 'bulk2-' . $timestamp . '@test.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        
        $result = \App\Model\User::insert($records);
        
        $this->assertTrue($result, 'Bulk insert should succeed');
        
        // Find and cleanup
        $users = \App\Model\User::query()
            ->where('email', 'LIKE', 'bulk%-' . $timestamp . '@test.com')
            ->get();
        
        foreach ($users as $user) {
            $this->testUserIds[] = $user->id;
        }
        
        $this->assertTrue(count($users) >= 2, 'Should insert multiple records');
    }
    
    public function testFindOrNew() {
        $timestamp = time();
        
        // Find existing
        $existing = \App\Model\User::query()->first();
        if ($existing) {
            $found = \App\Model\User::findOrNew($existing->id);
            $this->assertNotNull($found->id, 'Should find existing user');
        }
        
        // Find non-existent (returns new instance)
        $new = \App\Model\User::findOrNew(999999999);
        
        $this->assertNull($new->id, 'New instance should not have ID');
        $this->assertTrue(is_object($new), 'Should return User instance');
        $this->assertTrue(get_class($new) === 'App\Model\User', 'Should be User class');
    }
    
    public function testFindBySql() {
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'FindBySql Test',
            'email' => 'findbysql-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        // Find using raw SQL
        $users = \App\Model\User::findBySql(
            "SELECT * FROM users WHERE id = ?",
            [$user->id]
        );
        
        $this->assertTrue(count($users) > 0, 'Should find user with raw SQL');
        $this->assertEquals($user->id, $users[0]->id, 'Should return correct user');
    }
    
    // ============================================================================
    // ADVANCED JOINS
    // ============================================================================
    
    public function testLeftJoin() {
        // Create test user and post
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Join Test User',
            'email' => 'joinuser-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $post = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Join Test Post',
            'slug' => 'join-test-post-' . $timestamp,
            'content' => 'Test content',
        ]);
        $this->testPostIds[] = $post->id;
        
        // Left join with qualified column name in WHERE
        $results = \App\Model\User::query()
            ->select('users.id', 'users.name', 'posts.title')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->where('users.id', '=', $user->id)
            ->get();
        
        $this->assertNotNull($results, 'Left join should execute successfully');
        $this->assertTrue(count($results) >= 1, 'Should find at least one result');
        
        // Verify we got the right user
        if (count($results) > 0) {
            $this->assertEquals($user->id, $results[0]->id, 'Should return correct user');
        }
    }
    
    public function testRightJoin() {
        // Create test user and post
        $timestamp = time();
        
        $user = \App\Model\User::create([
            'name' => 'Right Join User',
            'email' => 'rightjoin-' . $timestamp . '@test.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $this->testUserIds[] = $user->id;
        
        $post = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Right Join Post',
            'slug' => 'right-join-post-' . $timestamp,
            'content' => 'Test content',
        ]);
        $this->testPostIds[] = $post->id;
        
        // Right join with qualified column name in WHERE
        $results = \App\Model\Post::query()
            ->select('posts.id', 'posts.title', 'users.name')
            ->rightJoin('users', 'posts.user_id', '=', 'users.id')
            ->where('posts.id', '=', $post->id)
            ->get();
        
        $this->assertNotNull($results, 'Right join should execute successfully');
        $this->assertTrue(count($results) >= 1, 'Should find at least one result');
        
        // Verify we got the right post
        if (count($results) > 0) {
            $this->assertEquals($post->id, $results[0]->id, 'Should return correct post');
        }
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
            // Delete test comments
            foreach ($this->testCommentIds as $id) {
                try {
                    $db->query("DELETE FROM comments WHERE id = ?", [$id]);
                } catch (Exception $e) {
                    // Ignore
                }
            }
            
            // Delete test posts
            foreach ($this->testPostIds as $id) {
                try {
                    $db->query("DELETE FROM posts WHERE id = ?", [$id]);
                } catch (Exception $e) {
                    // Ignore
                }
            }
            
            // Delete test users
            foreach ($this->testUserIds as $id) {
                try {
                    $db->query("DELETE FROM users WHERE id = ?", [$id]);
                } catch (Exception $e) {
                    // Ignore
                }
            }
            
        } catch (Exception $e) {
            error_log('Cleanup error: ' . $e->getMessage());
        }
        
        // Reset arrays
        $this->testUserIds = [];
        $this->testPostIds = [];
        $this->testCommentIds = [];
    }
}
