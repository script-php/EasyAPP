<?php

/**
* @package      Proxy
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Proxy extends \stdClass {

    private $model;
	private $callback;

    public function __construct($model, $callback) {
        $this->model = $model;
		$this->callback = $callback;
    }

	public function &__get(string $key) {
		if (method_exists($this->model, $key)) {
			return $this->model->{$key};
		} 
		else {
			throw new \Exception('Error: Could not call proxy key ' . $key . '!');
		}
	}

	public function __set(string $key, $value): void {
		$this->model->{$key} = $value;
	}

    public function exists(string $method) {
        return method_exists($this->model, $method);
    }

    public function __call($name, $args) {
		return call_user_func_array($this->callback, [$this->model, $name, $args]);
    }
}
