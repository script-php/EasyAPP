<?php

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
            $file = APP_CONTROLLER . '/' . $route . '.php';
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
            $file = APP_MODEL . '/' . $route . '.php';
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

}