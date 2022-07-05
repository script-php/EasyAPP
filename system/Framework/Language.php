<?php

namespace System\Framework;

class Language {
	private $default = 'en-gb';
	private $directory;
	public $data = array();
	
	public function __construct($directory) {
		$this->directory = $directory;
	}
	
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : $key);
	}
	
	public function all() {
		return $this->data;
	}

    public function directory($dir) {

    }
	
	public function load($filename) {

        $_ = array();

        $file = DIR_LANGUAGE . '/' . $this->default . '/' . $filename . '.php';

        if (is_file($file)) {
            require($file);
        }

        $file = DIR_LANGUAGE . '/' . $this->directory . '/' . $filename . '.php';
        
        if (is_file($file)) {
        	require($file);
        } 

        $this->data = array_merge($this->data, $_);
		
		return $this->data;
	}
}