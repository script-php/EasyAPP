<?php

/**
* @package      Error page example
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ControllerMyErrorPageError extends Controller {

    private $settings = [];

    function __construct() {
        // do something when the class is initialized
    }

    function index() {
        echo 'Page not found! :(';
    }

    function test() {
        return 'test method';
    }

}