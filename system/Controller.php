<?php

/**
* @package      Controller
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

abstract class Controller {

	protected $registry;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function __get($key) {
		return $this->registry->get($key);
	}

	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}

	function __callMethod($method, array $args = []) {
		$result = call_user_func_array([$this, $method], $args);
		return $result;
	}
	
}