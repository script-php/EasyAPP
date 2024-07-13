<?php

namespace System\Framework;

class Proxy {

	protected array $data = [];

	public function &__get(string $key) {
		if (isset($this->data[$key])) {
			return $this->data[$key];
		} 
		else {
			throw new \Exception('Error: Could not call proxy key ' . $key . '!');
		}
	}

	public function __set(string $key, object $value): void {
		$this->data[$key] = $value;
	}

	public function __isset(string $key): bool {
		return isset($this->data[$key]);
	}

	public function __unset(string $key): void {
		unset($this->data[$key]);
	}

	public function data() {
		return $this->data;
	}

	public function exists(string $method) {
		if (isset($this->data[$method])) {
			return true;
		}
		return false;
	}

	public function __call(string $method, array $args) {
		foreach ($args as $key => &$value);

		if (isset($this->data[$method])) {
			return ($this->data[$method])(...$args);
		} else {
			$trace = debug_backtrace();
			exit('<b>Notice</b>:  Undefined property: Proxy::<b>' . $method . '</b> in <b>' . $trace[0]['file'] . '</b> on line <b>' . $trace[0]['line'] . '</b>');
		}
	}
}