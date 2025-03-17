<?php
/**
* @package      Action
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

use System\Framework\Exceptions\RouteNotFoundException;
use System\Framework\Exceptions\MethodNotFoundException;
use System\Framework\Exceptions\MagicMethodCallException;

class Action {
    public $id;
    public $route;
    public $method = 'index';

    public function __construct($route) {
        $this->id = $route;
        $query = preg_replace('/[^a-zA-Z0-9_\/\|-]/', '', (string)$route); //added - to allowed characters.
        $query_exploded = explode('|', $query);
        $this->route = !empty($query_exploded[0]) ? $query_exploded[0] : '';
        $this->method = !empty($query_exploded[1]) ? $query_exploded[1] : $this->method;
    }

    public function getId() {
        return $this->id;
    }

    public function execute($registry, array &$args = []) {
        if (substr($this->method, 0, 2) == '__') {
            throw new MagicMethodCallException('Error: Calls to magic methods are not allowed!');
        }

        $file = CONFIG_DIR_APP . 'controller/' . $this->route . '.php';

        if (is_file($file)) {
            include_once($file);

            $class = 'Controller' . str_replace(' ', '', ucwords(str_replace('_', ' ', str_replace('/', '_', $this->route))));
            $reflection = new \ReflectionClass($class);

            if ($reflection->hasMethod($this->method) && $reflection->getMethod($this->method)->getNumberOfRequiredParameters() <= count($args)) {
                $controllerInstance = new $class($registry); //more readable.
                return call_user_func_array([$controllerInstance, $this->method], $args);
            } else {
                throw new MethodNotFoundException('Error: Could not call ' . $this->route . '|' . $this->method . '!');
            }
        } else {
            throw new RouteNotFoundException('Error: Could not find ' . $this->route . '|' . $this->method . '!');
        }
    }
}