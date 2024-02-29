<?php

/**
* @package      Autoloader
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System;

class Autoloader {

    public $path = '';
    public $namespace = '';
    public $directory = '';
    public $extension = '.php';
    public $recursive = false;

    public function load(array $extra = []) {
        if(!empty($extra['directory'])) {
            spl_autoload_register(function($class) use ($extra) {
                $namespace = !empty($extra['namespace']) ? $extra['namespace'] : $this->namespace; 
                $directory = !empty($extra['directory']) ? $extra['directory'] : $this->directory; 
                $extension = !empty($extra['extension']) ? $extra['extension'] : $this->extension;
                $recursive = !empty($extra['recursive']) ? $extra['recursive'] : $this->recursive;
                $path = '';
                $parts = explode('\\', $class);
                foreach ($parts as $part) {
                    $path .= (!$path) ? $part : '\\' . $part;
                }
                if(!empty($namespace)) {
                    if(substr($class, 0, strlen($namespace)) == $namespace) {
                        $class = substr($class, strlen($namespace));
                    }
                }
                // $file = $this->path . $directory . trim(strtolower(preg_replace('~([a-z])([A-Z]|[0-9])~', '\\1_\\2', $class)), '/') . $extension;
                $file = $directory . trim($class, '/') . $extension;
                $file = str_replace('\\', '/', $file);
                if (isset($file) && is_file($file)) {
                    include_once($file);
                } else {
                    if($recursive) {
                        $dir = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
                        $iterator = NULL;
                        if (is_null($iterator)) {
                            $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::LEAVES_ONLY);
                        }
                        foreach ($iterator as $file) {
                            if (strtolower($file->getFilename()) === strtolower($class . $extension)) {
                                if ($file->isReadable()) {
                                    $file = str_replace('\\', '/', $file->getPathname());
                                    include_once $file;
                                    break;
                                }
                            }
                        }
                    }
                }
            });
        }
	}

}