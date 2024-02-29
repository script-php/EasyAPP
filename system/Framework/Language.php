<?php

/**
* @package      Language
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Language {
	private $default = 'en-gb';
	private $directory;
	public $registry;
	private $request;
	public $data = array();
	
	public function __construct($registry) {
		$this->registry = $registry;
		$this->request = $this->registry->get('request');
		$this->default = !empty(CONFIG_DEFAULT_LANGUAGE) ? CONFIG_DEFAULT_LANGUAGE : $this->default;
		$language = !empty($this->request->cookie['language']) ? $this->request->cookie['language'] : $this->default;
		$this->directory($language);
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

        $file = CONFIG_DIR_LANGUAGE . $this->default . '/' . $filename . '.php';

        if (is_file($file)) {
            require($file);
        }

        $file = CONFIG_DIR_LANGUAGE . $this->directory . '/' . $filename . '.php';
        
        if (is_file($file)) {
        	require($file);
        } 

        $this->data = array_merge($this->data, $_);
		
		return $this->data;
	}
}