<?php

/**
 * ORM Feature Test - Simplified isolated tests
 * 
 * Each test is independent and tests a specific feature
 * 
 * @package      EasyAPP Framework Tests
 * @author       EasyAPP Framework
 */

require_once 'system/TestCase.php';

class OrmFeatureTest extends TestCase {
    
    function __construct($registry) {
        parent::__construct($registry);
        
        // Set up ORM database connection
        if ($this->registry && $this->registry->has('db')) {
            \System\Framework\Orm::setConnection($this->registry->get('db'));
        }
    }
    
    protected function setUp() {
        // Each test is isolated
    }
    
    protected function tearDown() {
        // Cleanup handled in each test
    }
    
    // ============================================================================
    // TEST: CRUD CREATE
    // ============================================================================
    
    public function testOrmCreate() {
        $uniqueEmail = 'test-create-' . time() . '@example.com';
        
        $user = \App\Model\User::create([
            'name' => 'Test Create User',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        // Debug validation errors
        if (!$user || !$user->id) {
            echo "Validation errors: ";
            if ($user) {
                print_r($user->getErrors());
            }
        }
        
        $this->assertNotNull($user, 'User should be created');
        $this->assertNotNull($user->id, 'User should have an ID');
        $this->assertEquals('Test Create User', $user->name);
        
        // Cleanup
        if ($user && $user->id) {
            $db = $this->registry->get('db');
            $db->query("DELETE FROM users WHERE id = ?", [$user->id]);
        }
    }
    
    // ============================================================================
    // TEST: CRUD READ
    // ============================================================================
    
    public function testOrmFind() {
        $uniqueEmail = 'test-find-' . time() . '@example.com';
        
        // Create
        $user = \App\Model\User::create([
            'name' => 'Test Find User',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $userId = $user->id;
        
        // Find
        $found = \App\Model\User::find($userId);
        $this->assertNotNull($found, 'User should be found');
        $this->assertEquals($userId, $found->id);
        $this->assertEquals('Test Find User', $found->name);
        
        // Cleanup
        $db = $this->registry->get('db');
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    // ============================================================================
    // TEST: CRUD UPDATE
    // ============================================================================
    
    public function testOrmUpdate() {
        $uniqueEmail = 'test-update-' . time() . '@example.com';
        
        // Create
        $user = \App\Model\User::create([
            'name' => 'Original Name',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $userId = $user->id;
        
        // Update
        $user->name = 'Updated Name';
        $saved = $user->save();
        $this->assertTrue($saved, 'Update should succeed');
        
        // Verify
        $updated = \App\Model\User::find($userId);
        $this->assertEquals('Updated Name', $updated->name);
        
        // Cleanup
        $db = $this->registry->get('db');
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    // ============================================================================
    // TEST: CRUD DELETE (SOFT)
    // ============================================================================
    
    public function testOrmSoftDelete() {
        $uniqueEmail = 'test-softdelete-' . time() . '@example.com';
        
        // Create
        $user = \App\Model\User::create([
            'name' => 'Test Soft Delete',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        $userId = $user->id;
        
        // Soft delete
        $user->delete();
        
        // Should not be found
        $found = \App\Model\User::find($userId);
        $this->assertNull($found, 'Soft deleted user should not be found');
        
        // Should be found with withTrashed
        $trashed = \App\Model\User::query()->withTrashed()->where('id', $userId)->first();
        $this->assertNotNull($trashed, 'Should find with withTrashed');
        
        // Cleanup - force delete
        $db = $this->registry->get('db');
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    // ============================================================================
    // TEST: QUERY WHERE
    // ============================================================================
    
    public function testOrmWhereQuery() {
        $uniqueEmail = 'test-where-' . time() . '@example.com';
        
        // Create test user
        $user = \App\Model\User::create([
            'name' => 'Test Where User',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
        $userId = $user->id;
        
        // Query with where
        $results = \App\Model\User::query()
            ->where('email', $uniqueEmail)
            ->get();
        
        $this->assertTrue(count($results) === 1, 'Should find exactly 1 user');
        $this->assertEquals($uniqueEmail, $results[0]->email);
        
        // Cleanup
        $db = $this->registry->get('db');
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    // ============================================================================
    // TEST: RELATIONSHIPS - BELONGS TO
    // ============================================================================
    
    public function testOrmBelongsTo() {
        $uniqueEmail = 'test-belongsto-' . time() . '@example.com';
        $uniqueSlug = 'test-post-' . time();
        
        // Create user
        $user = \App\Model\User::create([
            'name' => 'Post Author',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        // Create post
        $post = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'slug' => $uniqueSlug,
            'content' => 'Test content',
        ]);
        
        // Test relationship
        $author = $post->user();
        $this->assertNotNull($author, 'Post should have a user');
        $this->assertEquals($user->id, $author->id);
        $this->assertEquals($user->name, $author->name);
        
        // Cleanup
        $db = $this->registry->get('db');
        $db->query("DELETE FROM posts WHERE id = ?", [$post->id]);
        $db->query("DELETE FROM users WHERE id = ?", [$user->id]);
    }
    
    // ============================================================================
    // TEST: RELATIONSHIPS - HAS MANY
    // ============================================================================
    
    public function testOrmHasMany() {
        $uniqueEmail = 'test-hasmany-' . time() . '@example.com';
        $timestamp = time();
        
        // Create user
        $user = \App\Model\User::create([
            'name' => 'Prolific Author',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        // Create posts
        $postIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $post = \App\Model\Post::create([
                'user_id' => $user->id,
                'title' => "Post $i",
                'slug' => "post-$i-$timestamp",
                'content' => "Content $i",
            ]);
            $postIds[] = $post->id;
        }
        
        // Test relationship
        $posts = $user->posts()->get();
        $this->assertNotNull($posts, 'User should have posts');
        $this->assertEquals(3, count($posts), 'User should have exactly 3 posts');
        
        // Cleanup
        $db = $this->registry->get('db');
        foreach ($postIds as $postId) {
            $db->query("DELETE FROM posts WHERE id = ?", [$postId]);
        }
        $db->query("DELETE FROM users WHERE id = ?", [$user->id]);
    }
    
    // ============================================================================
    // TEST: AGGREGATES
    // ============================================================================
    
    public function testOrmAggregates() {
        $timestamp = time();
        $uniqueEmail = 'test-aggregates-' . time() . '@example.com';
        
        // Create user
        $user = \App\Model\User::create([
            'name' => 'Stats User',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        // Create posts with specific views
        $post1 = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Post 1',
            'slug' => "agg-post-1-$timestamp",
            'content' => 'Content',
            'views' => 100,
        ]);
        $post2 = \App\Model\Post::create([
            'user_id' => $user->id,
            'title' => 'Post 2',
            'slug' => "agg-post-2-$timestamp",
            'content' => 'Content',
            'views' => 200,
        ]);
        
        // Test aggregates on our specific posts
        $maxViews = \App\Model\Post::query()
            ->whereIn('id', [$post1->id, $post2->id])
            ->max('views');
        $this->assertEquals(200, (int)$maxViews, 'Max should be 200');
        
        $minViews = \App\Model\Post::query()
            ->whereIn('id', [$post1->id, $post2->id])
            ->min('views');
        $this->assertEquals(100, (int)$minViews, 'Min should be 100');
        
        $sumViews = \App\Model\Post::query()
            ->whereIn('id', [$post1->id, $post2->id])
            ->sum('views');
        $this->assertEquals(300, (int)$sumViews, 'Sum should be 300');
        
        $avgViews = \App\Model\Post::query()
            ->whereIn('id', [$post1->id, $post2->id])
            ->avg('views');
        $this->assertEquals(150, (int)$avgViews, 'Average should be 150');
        
        $count = \App\Model\Post::query()
            ->whereIn('id', [$post1->id, $post2->id])
            ->count();
        $this->assertEquals(2, $count, 'Count should be 2');
        
        // Cleanup
        $db = $this->registry->get('db');
        $db->query("DELETE FROM posts WHERE id IN (?, ?)", [$post1->id, $post2->id]);
        $db->query("DELETE FROM users WHERE id = ?", [$user->id]);
    }
    
    // ============================================================================
    // TEST: SCHEMA INSPECTION
    // ============================================================================
    
    public function testOrmSchemaInspection() {
        // Test getTableSchema
        $schema = \App\Model\User::getTableSchema();
        $this->assertNotNull($schema, 'Schema should not be null');
        $this->assertTrue(isset($schema['table']), 'Schema should have table');
        $this->assertTrue(isset($schema['columns']), 'Schema should have columns');
        
        // Test getColumns
        $columns = \App\Model\User::getColumns();
        $this->assertTrue(is_array($columns), 'Columns should be array');
        $this->assertTrue(count($columns) > 0, 'Should have columns');
        
        // Test hasColumn
        $this->assertTrue(\App\Model\User::hasColumn('email'), 'Should have email column');
        $this->assertFalse(\App\Model\User::hasColumn('nonexistent'), 'Should not have fake column');
        
        // Test getIndexes
        $indexes = \App\Model\User::getIndexes();
        $this->assertTrue(is_array($indexes), 'Indexes should be array');
        
        // Test getForeignKeys
        $fks = \App\Model\Post::getForeignKeys();
        $this->assertTrue(is_array($fks), 'Foreign keys should be array');
    }
    
    // ============================================================================
    // TEST: TIMESTAMPS
    // ============================================================================
    
    public function testOrmTimestamps() {
        $uniqueEmail = 'test-timestamps-' . time() . '@example.com';
        
        // Create
        $user = \App\Model\User::create([
            'name' => 'Timestamp Test',
            'email' => $uniqueEmail,
            'password' => password_hash('password123', PASSWORD_DEFAULT),
        ]);
        
        $this->assertNotNull($user->created_at, 'Should have created_at');
        
        // Update
        sleep(1);
        $user->name = 'Updated Name';
        $user->save();
        
        $updated = \App\Model\User::find($user->id);
        $this->assertNotNull($updated->updated_at, 'Should have updated_at');
        
        // Cleanup
        $db = $this->registry->get('db');
        $db->query("DELETE FROM users WHERE id = ?", [$user->id]);
    }
}
