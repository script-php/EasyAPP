<?php

/**
* @package      Error page example
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ControllerErrorNotFound extends Controller {

    function index() {
        echo 'Page not found! :(';
    }

}