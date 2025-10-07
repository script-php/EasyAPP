<?php

/**
* @package      Example Test
* @author       EasyAPP Framework
*/

require_once 'system/TestCase.php';

class ExampleTest extends TestCase {
    
    public function testBasicAssertions() {
        $this->assertTrue(true);
        $this->assertFalse(false);
        $this->assertEquals('hello', 'hello');
        $this->assertNotEquals('hello', 'world');
    }
    
    public function testArrayOperations() {
        $array = [1, 2, 3, 4, 5];
        
        $this->assertCount(5, $array);
        $this->assertContains(3, $array);
        $this->assertTrue(in_array(4, $array));
    }
    
    public function testStringOperations() {
        $string = 'Hello EasyAPP Framework';
        
        $this->assertContains('EasyAPP', $string);
        $this->assertTrue(strlen($string) > 0);
        $this->assertEquals('Hello EasyAPP Framework', $string);
    }
    
    public function testNullValues() {
        $value = null;
        $notNull = 'something';
        
        $this->assertNull($value);
        $this->assertNotNull($notNull);
    }
}