<?php

/**
* @package      Proxy
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Proxy {
    private $registry = null;

    public function __construct($registry) {
        $this->registry = $registry;
    }
    
    public function setRegistry($registry) {
        $this->registry = $registry;
    }
    
    public function getRegistry() {
        return $this->registry;
    }
	
    public function createControllerProxy($controller) {
        $proxy = new ObjectProxy($controller, $this->registry);
        
        $proxy->beforeMethodCall(function($method, &$args, $registry) use ($controller) {
            $className = get_class($controller);
            error_log("Controller: Calling method {$className}::{$method}");
            
            $eventData = [
                'class' => $className,
                'method' => $method,
                'args' => &$args,  // Pass by reference
                'registry' => $registry
            ];
            
            $registry->get('events')->trigger("controller.method.call", $eventData);
            
        });
		
		$proxy->beforePropertySet(function($property, $value, $registry) use ($controller) {
            $className = get_class($controller);
            error_log("Controller: Setting property {$className}->{$property}");
            
            $eventData = [
                'class' => $className,
                'property' => $property,
                'value' => $value,
                'registry' => $registry
            ];

            $registry->get('events')->trigger("controller.property.set", $eventData);
            
        });
        
        return $proxy;
    }
	
	
	
    
    public function createModelProxy($model) {
        $proxy = new ObjectProxy($model, $this->registry);

        $proxy->beforeMethodCall(function($method, &$args, $registry) use ($model) {
            $className = get_class($model);
            error_log("Model: Calling method {$className}::{$method}");
            
            $eventData = [
                'class' => $className,
                'method' => $method,
                'args' => &$args,
                'registry' => $registry
            ];

            $registry->get('events')->trigger("model.method.call", $eventData);

        });
		
        
        $proxy->beforePropertySet(function($property, $value, $registry) use ($model) {
            $className = get_class($model);
            error_log("Model: Setting property {$className}::{$property}");
            
            $eventData = [
                'class' => $className,
                'property' => $property,
                'value' => $value,
                'registry' => $registry
            ];

            $registry->get('events')->trigger("model.property.set", $eventData);
            
        });
        
        return $proxy;
    }
}

// class Proxy extends \stdClass {

//     private $model;
// 	private $callback;

//     public function __construct($model, $callback) {
//         $this->model = $model;
// 		$this->callback = $callback;
//     }

// 	public function &__get(string $key) {
// 		if (method_exists($this->model, $key)) {
// 			return $this->model->{$key};
// 		} 
// 		else {
// 			throw new \Exception('Error: Could not call proxy key ' . $key . '!');
// 		}
// 	}

// 	public function __set(string $key, $value): void {
// 		$this->model->{$key} = $value;
// 	}

//     public function exists(string $method) {
//         return method_exists($this->model, $method);
//     }

//     public function __call($name, $args) {
// 		return call_user_func_array($this->callback, [$this->model, $name, $args]);
//     }
// }
