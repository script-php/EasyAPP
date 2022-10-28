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
	private $request;
	public $data = array();
	
	public function __construct($registry) {
		$this->registry = $registry;
		$this->config = $this->registry->get('config');
		$this->request = $this->registry->get('request');

		$this->default = !empty($config->default_language) ? $config->default_language : $this->default;
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