<?php

/**
* @package      Home model example
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ModelHome extends Model {

    public $test = 'This is a test from ModelHome'; // Example property

    /**
     * Example method returning an array
     */
    function method() {
        return [1,2,3,4,5]; // Example method returning an array
    }

}