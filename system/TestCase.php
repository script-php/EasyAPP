<?php

/**
* @package      Simple Test Framework
* @author       EasyAPP Framework
* @copyright    Copyright (c) 2022, script-php.ro
*/

abstract class TestCase {
    protected $registry;
    protected $passed = 0;
    protected $failed = 0;
    
    public function __construct($registry = null) {
        $this->registry = $registry;
    }
    
    public function run() {
        $methods = get_class_methods($this);
        $testMethods = array_filter($methods, function($method) {
            return strpos($method, 'test') === 0;
        });
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Running tests for " . get_class($this) . "\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($testMethods as $method) {
            try {
                $this->setUp();
                $this->$method();
                $this->tearDown();
                $this->passed++;
                echo "✓ {$method}\n";
            } catch (Exception $e) {
                $this->failed++;
                echo "✗ {$method}: " . $e->getMessage() . "\n";
            }
        }
        
        $total = $this->passed + $this->failed;
        echo "\nResults: {$this->passed}/{$total} tests passed";
        if ($this->failed > 0) {
            echo " ({$this->failed} failed)";
        }
        echo "\n";
        
        return $this->failed === 0;
    }
    
    protected function setUp() {
        // Override in test classes
    }
    
    protected function tearDown() {
        // Override in test classes
    }
    
    protected function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception($message ?: 'Assertion failed: Expected true');
        }
    }
    
    protected function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception($message ?: 'Assertion failed: Expected false');
        }
    }
    
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            $msg = $message ?: "Assertion failed: Expected '{$expected}', got '{$actual}'";
            throw new Exception($msg);
        }
    }
    
    protected function assertNotEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            $msg = $message ?: "Assertion failed: Expected not equals to '{$expected}'";
            throw new Exception($msg);
        }
    }
    
    protected function assertNull($value, $message = '') {
        if ($value !== null) {
            throw new Exception($message ?: 'Assertion failed: Expected null');
        }
    }
    
    protected function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception($message ?: 'Assertion failed: Expected not null');
        }
    }
    
    protected function assertCount($expectedCount, $array, $message = '') {
        $actualCount = is_array($array) ? count($array) : 0;
        if ($expectedCount !== $actualCount) {
            $msg = $message ?: "Assertion failed: Expected count {$expectedCount}, got {$actualCount}";
            throw new Exception($msg);
        }
    }
    
    protected function assertContains($needle, $haystack, $message = '') {
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack)) {
                throw new Exception($message ?: "Assertion failed: Array does not contain '{$needle}'");
            }
        } else {
            if (strpos($haystack, $needle) === false) {
                throw new Exception($message ?: "Assertion failed: String does not contain '{$needle}'");
            }
        }
    }
    
    protected function expectException($exceptionClass) {
        // This would need more complex implementation for proper exception testing
        // For now, just store the expectation
        $this->expectedExceptionClass = $exceptionClass;
    }
}