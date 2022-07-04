<?php

class ModelAnotherModelAnotherOne extends Model {

    function test() {

        return $this->db->query("SELECT * FROM users");

    }

}