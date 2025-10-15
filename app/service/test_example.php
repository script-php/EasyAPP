<?php

/**
* Test Service for validating improved service method
* @package TestService
*/

class ServiceTestExample extends Service {

    public function __construct($registry) {
        parent::__construct($registry);
    }

    /**
     * Default index method
     * @return string
     */
    public function index() {
        echo '[TestExample Service] ';
        return "Test service index method executed successfully!";

    }

    /**
     * Method with parameters
     * @param string $message
     * @param int $count
     * @return string
     */
    public function greet($message, $count = 1) {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = "Greeting: " . $message;
        }
        return implode(", ", $result);
    }

    /**
     * Method without parameters
     * @return array
     */
    public function getInfo() {
        return [
            'service' => 'TestExample',
            'status' => 'active',
            'timestamp' => time()
        ];
    }
}