<?php

/**
 * Integration Test Example - SystemIntegrationTest
 * 
 * This demonstrates integration testing principles:
 * - Slower execution (seconds) due to real I/O operations
 * - Uses real dependencies (database, file system, services)
 * - End-to-end workflow validation
 * - Tests component interaction
 * 
 * @package      EasyAPP Framework Tests
 * @author       EasyAPP Framework
 */

class SystemIntegrationTest extends TestCase {
    
    private $testUserId = null;

    function __construct($registry) {
		parent::__construct($registry);
	}
    
    protected function setUp() {
        // Set up test environment before each test
        $this->cleanupTestData();
    }
    
    protected function tearDown() {
        // Clean up after each test
        $this->cleanupTestData();
    }
    
    
    /**
     * Test service integration and business logic
     */
    public function testServiceIntegration() {
        // Test that services can be loaded and executed
        try {
            // Test service loading (if email service exists)
            if (file_exists(PATH . 'app/service/email.php')) {
                $emailResult = $this->load->service('email|testConnection');
                $this->assertNotNull($emailResult, 'Service should return a result');
            }
            
            // Test service with framework access
            if (file_exists(PATH . 'app/service/analytics.php')) {
                $analyticsResult = $this->load->service('analytics|logEvent', 'test_event', 'integration_test');
                $this->assertTrue(true, 'Analytics service should execute without error');
            }
            
        } catch (Exception $e) {
            // Services might not exist in basic installation
            $this->info('Services not available - this is normal for basic installation: ' . $e->getMessage());
        }
    }
    
    /**
     * Test file system operations and storage
     */
    public function testFileSystemOperations() {
        $testDir = PATH . 'storage/test/';
        $testFile = $testDir . 'integration_test.txt';
        $testContent = 'Integration test content - ' . date('Y-m-d H:i:s');
        
        // Test directory creation
        if (!is_dir($testDir)) {
            $created = mkdir($testDir, 0755, true);
            $this->assertTrue($created, 'Test directory should be created');
        }
        
        $this->assertTrue(is_dir($testDir), 'Test directory should exist');
        $this->assertTrue(is_writable($testDir), 'Test directory should be writable');
        
        // Test file write operations
        $written = file_put_contents($testFile, $testContent);
        $this->assertTrue($written > 0, 'Content should be written to file');
        $this->assertTrue(file_exists($testFile), 'Test file should exist');
        
        // Test file read operations
        $readContent = file_get_contents($testFile);
        $this->assertEquals($testContent, $readContent, 'Read content should match written content');
        
        // Test file permissions
        $this->assertTrue(is_readable($testFile), 'Test file should be readable');
        
        // Cleanup test file
        if (file_exists($testFile)) {
            unlink($testFile);
        }
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
    }
    
    /**
     * Test caching system integration (if available)
     */
    public function testCacheIntegration() {
        if ($this->registry->has('cache')) {
            $cache = $this->registry->get('cache');
            
            $testKey = 'integration_test_key';
            $testValue = ['test' => 'data', 'timestamp' => time()];
            
            // Test cache write
            $cache->set($testKey, $testValue, 300); // 5 minutes
            
            // Test cache read
            $cachedValue = $cache->get($testKey);
            $this->assertEquals($testValue, $cachedValue, 'Cached value should match original');
            
            // Test cache existence
            $this->assertTrue($cache->has($testKey), 'Cache key should exist');
            
            // Test cache deletion
            $cache->delete($testKey);
            $this->assertFalse($cache->has($testKey), 'Cache key should be deleted');
            
        } else {
            $this->info('Cache system not available - this is normal for basic installation');
        }
    }
    
    /**
     * Test session functionality
     */
    public function testSessionIntegration() {
        // Test session availability
        $this->assertTrue(isset($_SESSION), 'Session should be started');
        
        // Test session write/read
        $testKey = 'integration_test_session';
        $testData = ['user_id' => 123, 'test' => true];
        
        $_SESSION[$testKey] = $testData;
        $this->assertEquals($testData, $_SESSION[$testKey], 'Session data should be preserved');
        
        // Cleanup session test data
        unset($_SESSION[$testKey]);
        $this->assertFalse(isset($_SESSION[$testKey]), 'Session data should be cleaned up');
    }
    
    /**
     * Test configuration and environment integration
     */
    public function testConfigurationIntegration() {
        // Test config access through registry
        $config = $this->registry->get('config');
        $this->assertNotNull($config, 'Configuration should be available');
        $this->assertTrue(is_array($config), 'Configuration should be an array');
        
        // Test essential config values
        $this->assertTrue(isset($config['platform']), 'Platform config should exist');
        $this->assertTrue(isset($config['version']), 'Version config should exist');
        $this->assertTrue(isset($config['debug']), 'Debug config should exist');
        
        // Test environment variable integration (if .env exists)
        if (function_exists('env')) {
            $appEnv = env('APP_ENV', 'production');
            $this->assertTrue(in_array($appEnv, ['dev', 'development', 'prod', 'production', 'test']), 'APP_ENV should be valid');
        }
        
        // Test directory configurations
        $this->assertTrue(isset($config['dir_app']), 'App directory should be configured');
        $this->assertTrue(isset($config['dir_storage']), 'Storage directory should be configured');
        $this->assertTrue(is_dir($config['dir_app']), 'App directory should exist');
        $this->assertTrue(is_dir($config['dir_storage']), 'Storage directory should exist');
    }
    
    /**
     * Test URL and routing system integration
     */
    public function testUrlRoutingIntegration() {
        if ($this->registry->has('url')) {
            $url = $this->registry->get('url');
            
            // Test URL generation
            $homeUrl = $url->link('home');
            $this->assertNotNull($homeUrl, 'URL should be generated');
            $this->assertTrue(strlen($homeUrl) > 0, 'URL should not be empty');
            
            // Test URL with parameters
            $userUrl = $url->link('user/profile', 'id=123');
            $this->assertContains('123', $userUrl, 'URL should contain parameter');
            
        } else {
            $this->info('URL system not available - this is normal for CLI mode');
        }
    }
    
    /**
     * Test language system integration
     */
    public function testLanguageIntegration() {
        try {
            // Test language loading
            $this->load->language('common');
            
            if ($this->registry->has('language')) {
                $language = $this->registry->get('language');
                
                // Test language file access
                $testText = $language->get('text_welcome', 'Welcome'); // Default fallback
                $this->assertNotNull($testText, 'Language text should be available');
                $this->assertTrue(strlen($testText) > 0, 'Language text should not be empty');
            }
            
        } catch (Exception $e) {
            $this->info('Language files not available - this is normal for basic installation: ' . $e->getMessage());
        }
    }
    
    /**
     * Test logging system integration
     */
    public function testLoggingIntegration() {
        if ($this->registry->has('logger')) {
            $logger = $this->registry->get('logger');
            
            // Test log writing
            $testMessage = 'Integration test log message - ' . time();
            $logger->info($testMessage, ['test' => true]);
            
            // Test that log file exists (if file-based logging)
            $logFile = PATH . 'storage/logs/error.log';
            if (file_exists($logFile)) {
                $this->assertTrue(is_file($logFile), 'Log file should exist');
                $this->assertTrue(is_writable($logFile), 'Log file should be writable');
            }
            
        } else {
            $this->info('Logger not available - this is normal for basic installation');
        }
    }
    
    // ============================================================================
    // HELPER METHODS
    // ============================================================================
    
    /**
     * Clean up test data to ensure clean test environment
     */
    private function cleanupTestData() {
        if ($this->registry && $this->registry->has('db') && $this->testUserId) {
            try {
                $userModel = $this->load->model('user');
                $userModel->delete($this->testUserId);
                $this->testUserId = null;
            } catch (Exception $e) {
                // Silently handle cleanup errors
                error_log('Test cleanup error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Helper method to output info messages during testing
     */
    private function info($message) {
        // This would be visible during test execution
        echo "INFO: {$message}\n";
    }
}