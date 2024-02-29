<?php
/**
* @package      Config
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class Config {
	private $data = array();
    
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}

	public function has($key) {
		return isset($this->data[$key]);
	}
	
	public function load($filename) {
		$file = DIR_CONFIG . $filename . '.php';

		if (file_exists($file)) {
			$config = array();

			require($file);

			$this->data = array_merge($this->data, $config);
		} else {
			trigger_error('Error: Could not load config ' . $filename . '!');
			exit();
		}
	}
}
