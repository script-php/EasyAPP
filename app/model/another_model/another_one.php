<?php

/**
* @package      Another model example
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ModelAnotherModelAnotherOne extends Model {

    function test() {

        return $this->db->query("SELECT * FROM users");

    }

}