<?php

/**
* @package      Registry
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

 class Registry {
	private $data = [];

	public function __get(string $key) {
		return $this->get($key);
	}

	public function __set(string $key, object $value) {
		$this->set($key, $value);
	}

	public function get($key) {
		$system_class = 'System\\Framework\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
		if(!$this->has($key) && class_exists($system_class)) {
			$this->set($key, new $system_class($this));
		}
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}
}