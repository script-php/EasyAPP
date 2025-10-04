<?php

/**
 * Test Bootstrap - EasyAPP Framework Testing Environment Setup
 * 
 * @package      EasyAPP Framework
 * @author       EasyAPP Framework
 * @copyright    Copyright (c) 2022, script-php.ro
 * @link         https://script-php.ro
 */

/**
 * Test Environment Bootstrap
 * 
 * This file provides a standardized way to set up the testing environment
 * for EasyAPP Framework applications. It should be included by test files
 * that need framework functionality.
 */

// Define test mode
if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

// Set up basic path if not already defined
if (!defined('PATH')) {
    define('PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// Initialize framework for testing if not already done
if (!function_exists('initializeFramework')) {
    require_once PATH . 'system/Framework.php';
}

// Load core testing components
require_once PATH . 'system/TestCase.php';
require_once PATH . 'system/TestRunner.php';

/**
 * Initialize test environment with framework
 */
function initializeTestEnvironment($registry = null) {
    if (!$registry) {
        $registry = initializeFramework();
    }
    
    // Set up test database if available
    if (defined('CONFIG_DB_DRIVER') && $registry) {
        try {
            // Check if database is already initialized
            $existingDb = null;
            try {
                $existingDb = $registry->get('db');
            } catch (Exception $e) {
                // Database not set, that's fine
            }
            
            if (!$existingDb) {
                $registry->set('db', new System\Framework\Db(
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
            // Silently handle database connection issues in tests
            error_log("Test database connection failed: " . $e->getMessage());
        }
    }
    
    return $registry;
}

/**
 * Helper function to run tests programmatically
 * 
 * @param string $type Test type (TEST_UNIT, TEST_INTEGRATION, TEST_ALL)
 * @param object $registry Framework registry instance
 * @return array Test results
 */
function runTests($type = TEST_ALL, $registry = null) {
    $registry = initializeTestEnvironment($registry);
    $runner = new TestRunner($registry);
    return $runner->runTests($type);
}

/**
 * Helper function to run unit tests only
 * 
 * @param object $registry Framework registry instance
 * @return array Test results
 */
function runUnitTests($registry = null) {
    return runTests(TEST_UNIT, $registry);
}

/**
 * Helper function to run integration tests only
 * 
 * @param object $registry Framework registry instance
 * @return array Test results
 */
function runIntegrationTests($registry = null) {
    return runTests(TEST_INTEGRATION, $registry);
}

/**
 * Test type detection helper
 * 
 * @param string $filename Path to test file
 * @return string Test type (unit|integration)
 */
function getTestType($filename) {
    $runner = new TestRunner();
    return $runner->determineTestType($filename);
}

/**
 * Create a test instance with proper framework initialization
 * 
 * @param string $testClass Test class name
 * @param object $registry Framework registry instance
 * @return object Test instance
 */
function createTestInstance($testClass, $registry = null) {
    $registry = initializeTestEnvironment($registry);
    return new $testClass($registry);
}

/**
 * Quick test execution for single test files
 * 
 * @param string $testFile Path to test file
 * @param object $registry Framework registry instance
 * @return boolean Success status
 */
function executeTestFile($testFile, $registry = null) {
    if (!file_exists($testFile)) {
        echo "Test file not found: {$testFile}\n";
        return false;
    }
    
    $registry = initializeTestEnvironment($registry);
    $runner = new TestRunner($registry);
    
    // Get class name from file
    $content = file_get_contents($testFile);
    if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
        $className = $matches[1];
        
        require_once $testFile;
        
        if (class_exists($className)) {
            $test = new $className($registry);
            if (method_exists($test, 'run')) {
                return $test->run();
            }
        }
    }
    
    return false;
}