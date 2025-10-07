<?php
/**
 * Example: Standalone Test File
 * Demonstrates how to use the framework TestBootstrap for individual test execution
 */

// Include framework test bootstrap (permanent location)
require_once __DIR__ . '/../system/TestBootstrap.php';

class StandaloneTest extends TestCase {
    
    public function testFrameworkBootstrap() {
        // Test that framework is properly initialized
        $this->assertTrue(defined('TEST_MODE'));
        $this->assertTrue(defined('PATH'));
        $this->assertTrue(function_exists('initializeTestEnvironment'));
    }
    
    public function testHelperFunctions() {
        // Test that helper functions are available
        $this->assertTrue(function_exists('runTests'));
        $this->assertTrue(function_exists('runUnitTests'));
        $this->assertTrue(function_exists('runIntegrationTests'));
        $this->assertTrue(function_exists('getTestType'));
    }
    
    public function testEnvironmentSetup() {
        // Test that framework initialization works
        $this->assertTrue(function_exists('initializeFramework'));
        $this->assertTrue(class_exists('TestRunner'));
    }
}

// Run this test file directly
if (basename($_SERVER['SCRIPT_NAME']) === 'StandaloneTest.php') {
    echo "Running Standalone Test Example\n";
    echo str_repeat("=", 40) . "\n";
    
    $test = new StandaloneTest();
    $success = $test->run();
    
    if ($success) {
        echo "\n Framework TestBootstrap working correctly!\n";
        echo "This test file can run independently because:\n";
        echo "- TestBootstrap is in system/ (permanent framework location)\n";
        echo "- Not dependent on tests/ directory (can be deleted safely)\n";
        echo "- Provides all necessary framework initialization\n";
        echo "- Works in any deployment scenario\n";
    } else {
        echo "\n Test failed - check framework setup\n";
    }
}