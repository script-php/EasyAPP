<?php

/**
* @package      Load
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

namespace System\Framework;

class Load {

    public $registry;

    public function __construct($registry) {
		$this->registry = $registry;
	}


    function controller($route) {
        $util = new Util();
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        $controller = 'controller_' . str_replace('/', '_', $route);
		if (!$this->registry->has($controller)) {
            $file = DIR_CONTROLLER . '/' . $route . '.php';
            if (is_file($file)) {
                include_once($file);
                $class = $util->file2Class($controller);
                if (class_exists($class)) {
                    $load_controller = new $class($this->registry);
                    $this->registry->set($controller, $load_controller);
                }
            }
		}
    }


    function model($route) {
        $util = new Util();
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        $model = 'model_' . str_replace('/', '_', $route);
		if (!$this->registry->has($model)) {
            $file = DIR_MODEL . '/' . $route . '.php';
            if (is_file($file)) {
                include_once($file);
                $class = $util->file2Class($model);
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
        $route = DIR_VIEW . '/' . $route;
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