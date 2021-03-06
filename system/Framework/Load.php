<?php

/**
* @package      Load
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Load {

    public $config;
    public $util;
	public $registry;

    public function __construct($registry) {
		$this->registry = $registry;
		$this->config = $registry->get('config');
		$this->util = $registry->get('util');
	}


    function controller($route) {
        // $util = new Util();
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        $controller = 'controller_' . str_replace('/', '_', $route);
		if (!$this->registry->has($controller)) {
            $file = $this->config->dir_controller . $route . '.php';
			// pre($file);
            if (is_file($file)) {
                include_once($file);
                $class = $this->util->file2Class($controller);
                if (class_exists($class)) {
                    $load_controller = new $class($this->registry);
                    $this->registry->set($controller, $load_controller);
                }
            }
		}
    }


    function model($route) {
        // $util = new Util();
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        $model = 'model_' . str_replace('/', '_', $route);
		if (!$this->registry->has($model)) {
            $file = $this->config->dir_model . $route . '.php';
            if (is_file($file)) {
                include_once($file);
                $class = $this->util->file2Class($model);
                if (class_exists($class)) {
                    $load_model = new $class($this->registry);
                    $this->registry->set($model, $load_model);
                }
            }
		}
    }


    public function language($route) {
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route); // Sanitize the call
        $output = $this->registry->get('language')->load($route);	
		return $output;
	}


    public function view(string $route, array $data = [], bool $code = true) {
		$route = preg_replace('/[^a-zA-Z0-9_\/.]/', '', (string)$route); // Sanitize the call
        $route = $this->config->dir_view . '/' . $route;
        if(file_exists($route)) {
			if($code) {
				ob_start();
				extract($data);
				include $route;
				$output = ob_get_contents();
				ob_end_clean();
			}
			else {
				$output = file_get_contents($route);
				if($data != NULL) {
					foreach($data as $key => $value) {
						$output = str_replace('{'.strtoupper($key).'}', $value, $output); 
					}
				}
			}
			$output = str_replace("\t", "", $output);
			if(preg_match('/(\s){2,}/s', $output) === 1) {
				$output = preg_replace('/(\s){2,}/s', '', $output);
			}
			$output = preg_replace("/[\n\r]/","",$output);
			return $output;
		}
		else {
			exit('Error: Could not load template ' . $route . '!');
		}
	}


    public function json($Response, $header=TRUE) {
		$json = json_encode($Response);
		if($header) {
			header('Content-type: text/json;charset=UTF-8');
			echo $json;
		}
		else {
			return $json;
		}
	}


    public function text(string $text,array $params=NULL) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{'.strtoupper($param).'}',$value,$text);
			}
		}
		return $text;
	}

}