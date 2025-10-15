<?php

/**
* @package      Registry
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Registry {
    private static $instance = null;
    private $items = [];

    function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __get(string $key) {
		return $this->get($key);
	}

	public function __set(string $key, object $value) {
		$this->set($key, $value);
	}
    
    public function set($key, $value) {
        $this->items[$key] = $value;
    }
    
    public function get($key, $default = null) {
        $system_class = 'System\\Framework\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
		if(!$this->has($key) && class_exists($system_class)) {
			$this->set($key, new $system_class($this));
		}
        return isset($this->items[$key]) ? $this->items[$key] : $default;
    }
    
    public function has($key) {
        return isset($this->items[$key]);
    }
    
    public function remove($key) {
        unset($this->items[$key]);
    }
    
    public function all() {
        return $this->items;
    }

    public function __call($key, $args) {
        // pre('calll');
    }
}