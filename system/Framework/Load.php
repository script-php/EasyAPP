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
        $registry = 'controller_' . str_replace('/', '_', $route);
		if (!$this->registry->has($registry)) {
            $exploded = explode('/', $route);
            $controller = 'controller_' . str_replace('/', '_', $exploded[count($exploded)-1]);
            $file = APP_CONTROLLER . '/' . $route . '.php';
            if (is_file($file)) {
                include_once($file);
                $class = $util->file2Class($controller);
                if (class_exists($class)) {
                    $load_controller = new $class($this->registry);
                    $this->registry->set($registry, $load_controller);
                }
            }
		}
    }

    function model($route) {
        $util = new Util();
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        $registry = 'model_' . str_replace('/', '_', $route);
		if (!$this->registry->has($registry)) {
            $exploded = explode('/', $route);
            $model = 'model_' . str_replace('/', '_', $exploded[count($exploded)-1]);
            $file = APP_MODEL . '/' . $route . '.php';
            if (is_file($file)) {
                include_once($file);
                $class = $util->file2Class($model);
                if (class_exists($class)) {
                    $load_model = new $class($this->registry);
                    $this->registry->set($registry, $load_model);
                }
            }
		}
    }

}