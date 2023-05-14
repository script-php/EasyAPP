<?php

/**
* @package      Registry
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

 class Registry {
	private $data = array();

	public function __get(string $key) {
		return $this->get($key);
	}

	public function __set(string $key, object $value) {
		$this->set($key, $value);
	}

	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}
}