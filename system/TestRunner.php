<?php

/**
 * Test Runner - Core Testing Framework Component
 * 
 * @package      EasyAPP Framework
 * @author       EasyAPP Framework
 * @copyright    Copyright (c) 2022, script-php.ro
 * @link         https://script-php.ro
 */

/**
 * Test Categories and Organization
 */
if (!defined('TEST_UNIT')) define('TEST_UNIT', 'unit');
if (!defined('TEST_INTEGRATION')) define('TEST_INTEGRATION', 'integration');
if (!defined('TEST_ALL')) define('TEST_ALL', 'all');

/**
 * Test Runner Class - Framework Component for Test Execution
 */
class TestRunner {
    private $registry;
    private $totalPassed = 0;
    private $totalFailed = 0;
    private $testResults = [];
    
    public function __construct($registry = null) {
        // Use provided registry or initialize framework
        if ($registry !== null) {
            $this->registry = $registry;
        } else {
            $this->registry = $this->initializeFrameworkForTesting();
        }
        
        // Set up test database connection if available
        if (defined('CONFIG_DB_DRIVER') && $this->registry) {
            try {
                if (!$this->registry->get('db')) {
                    $this->registry->set('db', new System\Framework\Db(
                        CONFIG_DB_DRIVER,
                        CONFIG_DB_HOSTNAME, 
                        CONFIG_DB_DATABASE,
                        CONFIG_DB_USERNAME,
                        CONFIG_DB_PASSWORD,
                        CONFIG_DB_PORT,
                        '',  // encoding
                        ''   // options
                    ));
                }
            } catch (Exception $e) {
                echo "Warning: Could not connect to test database: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Initialize framework for testing if not already done
     */
    private function initializeFrameworkForTesting() {
        if (function_exists('initializeFramework')) {
            return initializeFramework();
        }
        
        // Fallback for standalone usage
        if (class_exists('System\\Framework\\Registry')) {
            return new System\Framework\Registry();
        }
        
        return null;
    }
    
    /**
     * Run tests based on type and pattern
     */
    public function runTests($type = TEST_ALL, $pattern = '*Test.php') {
        echo $this->getTestHeader($type);
        
        $testFiles = $this->findTestFiles($type, $pattern);
        
        if (empty($testFiles)) {
            echo "No test files found for type: {$type}\n";
            return ['passed' => 0, 'failed' => 0, 'total' => 0];
        }
        
        echo "Found " . count($testFiles) . " test file(s)\n\n";
        
        foreach ($testFiles as $testFile) {
            $this->runTestFile($testFile);
        }
        
        $this->showSummary();
        
        return [
            'passed' => $this->totalPassed,
            'failed' => $this->totalFailed,
            'total' => $this->totalPassed + $this->totalFailed
        ];
    }
    
    /**
     * Find test files based on type
     */
    private function findTestFiles($type, $pattern = '*Test.php') {
        $testDir = PATH . 'tests' . DIRECTORY_SEPARATOR;
        $allFiles = glob($testDir . $pattern);
        
        if ($type === TEST_ALL) {
            return $allFiles;
        }
        
        // Filter files based on type
        $filteredFiles = [];
        foreach ($allFiles as $file) {
            $testType = $this->determineTestType($file);
            if ($testType === $type) {
                $filteredFiles[] = $file;
            }
        }
        
        return $filteredFiles;
    }
    
    /**
     * Determine test type based on file content or naming convention
     */
    public function determineTestType($file) {
        $fileName = basename($file);
        
        // Check naming conventions first
        if (strpos($fileName, 'Unit') !== false || strpos($fileName, 'unit') !== false) {
            return TEST_UNIT;
        }
        
        if (strpos($fileName, 'Integration') !== false || strpos($fileName, 'integration') !== false) {
            return TEST_INTEGRATION;
        }
        
        // Check file content for hints
        $content = file_get_contents($file);
        
        // Integration test indicators
        if (strpos($content, '$this->db') !== false ||
            strpos($content, 'database') !== false ||
            strpos($content, 'Integration') !== false ||
            strpos($content, 'require_once') !== false ||
            strpos($content, 'initializeFramework') !== false) {
            return TEST_INTEGRATION;
        }
        
        // Default to unit test for simple assertion-based tests
        return TEST_UNIT;
    }
    
    /**
     * Run a single test file
     */
    private function runTestFile($testFile) {
        $className = $this->getTestClassName($testFile);
        echo "Running: " . basename($testFile) . " ";
        
        try {
            // Include the test file
            require_once $testFile;
            
            // Check if class exists
            if (!class_exists($className)) {
                echo "[ERROR] Class {$className} not found\n";
                $this->totalFailed++;
                return;
            }
            
            // Create test instance
            $testInstance = new $className($this->registry);
            
            // Run the test
            if (method_exists($testInstance, 'run')) {
                $success = $testInstance->run();
            } else {
                // Fallback for simple test classes
                $success = $this->runSimpleTest($testInstance);
            }
            
            if ($success) {
                echo "[PASSED]\n";
                $this->totalPassed++;
            } else {
                echo "[FAILED]\n";
                $this->totalFailed++;
            }
            
        } catch (Exception $e) {
            echo "[ERROR] " . $e->getMessage() . "\n";
            $this->totalFailed++;
        }
    }
    
    /**
     * Get test class name from file
     */
    private function getTestClassName($testFile) {
        $fileName = basename($testFile, '.php');
        
        // Check if class name is in the file
        $content = file_get_contents($testFile);
        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }
        
        return $fileName;
    }
    
    /**
     * Run simple test (fallback method)
     */
    private function runSimpleTest($testInstance) {
        $methods = get_class_methods($testInstance);
        $testMethods = array_filter($methods, function($method) {
            return strpos($method, 'test') === 0;
        });
        
        $passed = 0;
        $failed = 0;
        
        foreach ($testMethods as $method) {
            try {
                $testInstance->$method();
                $passed++;
            } catch (Exception $e) {
                $failed++;
            }
        }
        
        return $failed === 0;
    }
    
    /**
     * Get test header based on type
     */
    private function getTestHeader($type) {
        switch ($type) {
            case TEST_UNIT:
                return "Running Unit Tests\n" . str_repeat("=", 50) . "\n";
            case TEST_INTEGRATION:
                return "Running Integration Tests\n" . str_repeat("=", 50) . "\n";
            default:
                return "Running All Tests\n" . str_repeat("=", 50) . "\n";
        }
    }
    
    /**
     * Show test summary
     */
    private function showSummary() {
        $total = $this->totalPassed + $this->totalFailed;
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test Results Summary\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$this->totalPassed}\n";
        echo "Failed: {$this->totalFailed}\n";
        
        if ($this->totalFailed === 0) {
            echo "\n✅ All tests passed!\n";
        } else {
            echo "\n❌ Some tests failed!\n";
        }
    }
}