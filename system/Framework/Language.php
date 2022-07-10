<?php

/**
* @package      Language
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Language {
	private $default = 'en-gb';
	private $directory;
	public $registry;
	private $config;
	public $data = array();
	
	public function __construct($registry) {
		$this->registry = $registry;
		$this->config = $registry->get('config');
	}
	
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : $key);
	}
	
	public function all() {
		return $this->data;
	}

    public function directory($directory) {
		$this->directory = $directory;
    }
	
	public function load($filename) {

        $_ = array();

        $file = $this->config->dir_language . $this->default . '/' . $filename . '.php';

        if (is_file($file)) {
            require($file);
        }

        $file = $this->config->dir_language . $this->directory . '/' . $filename . '.php';
        
        if (is_file($file)) {
        	require($file);
        } 

        $this->data = array_merge($this->data, $_);
		
		return $this->data;
	}
}