<?php

/**
* @package      Error page example
* @version      1.0.0
* @author       Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

class ControllerErrorFolderError extends Controller {

    private $settings = [];

    function __construct() {
        // do something when the class is initialized
    }

    function index() {
        echo 'Page not found! :(';
    }

}