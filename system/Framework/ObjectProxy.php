<?php

namespace System\Framework;

class ObjectProxy {
    protected $subject;
    protected $registry;
    protected $beforeCallbacks = [];
    protected $afterCallbacks = [];
    protected $beforeGetCallbacks = [];
    protected $afterGetCallbacks = [];
    protected $beforeSetCallbacks = [];
    protected $afterSetCallbacks = [];

    public function __construct($subject, $registry = null) {
        $this->subject = $subject;
        $this->registry = $registry;
        
        $this->beforeMethodCall(function($method, &$args, $registry) use ($subject) {
            $className = get_class($subject);
            $eventName = "before:{$className}|{$method}";
            
            $eventData = [
                'object' => $subject,
                'method' => $method,
                'args' => &$args,  // Pass by reference
                'registry' => $registry
            ];
            
            $registry->get('events')->trigger($eventName, $eventData);
            
            $args = $eventData['args'];
        });
        
        $this->afterMethodCall(function($method, $args, &$result, $registry) use ($subject) {
            $className = get_class($subject);
            $eventName = "after:{$className}|{$method}";
            
            $eventData = [
                'object' => $subject,
                'method' => $method,
                'args' => $args,
                'result' => &$result,  // Pass by reference
                'registry' => $registry
            ];
            
            $registry->get('events')->trigger($eventName, $eventData);
            
            $result = $eventData['result'];
        });
    }

    public function setRegistry($registry) {
        $this->registry = $registry;
        return $this;
    }
    
    public function getRegistry() {
        return $this->registry;
    }

    public function beforeMethodCall(callable $callback) {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }
    
    public function afterMethodCall(callable $callback) {
        $this->afterCallbacks[] = $callback;
        return $this;
    }
    
    public function beforePropertyGet(callable $callback) {
        $this->beforeGetCallbacks[] = $callback;
        return $this;
    }
    
    public function afterPropertyGet(callable $callback) {
        $this->afterGetCallbacks[] = $callback;
        return $this;
    }
    
    public function beforePropertySet(callable $callback) {
        $this->beforeSetCallbacks[] = $callback;
        return $this;
    }

    public function afterPropertySet(callable $callback) {
        $this->afterSetCallbacks[] = $callback;
        return $this;
    }
    
    public function __get($name) {
        foreach ($this->beforeGetCallbacks as $callback) {
            $callback($name, $this->registry);
        }
        
        $value = $this->subject->$name;
        
        foreach ($this->afterGetCallbacks as $callback) {
            $value = $callback($name, $value, $this->registry) ?: $value;
        }
        
        return $value;
    }
    
    public function __set($name, $value) {
        foreach ($this->beforeSetCallbacks as $callback) {
            $value = $callback($name, $value, $this->registry) ?: $value;
        }
        
        $this->subject->$name = $value;
        
        foreach ($this->afterSetCallbacks as $callback) {
            $callback($name, $value, $this->registry);
        }
    }
    
    public function __call($name, $arguments) {

        foreach ($this->beforeCallbacks as $callback) {
            $callback($name, $arguments, $this->registry);
        }
        
        $result = call_user_func_array([$this->subject, $name], $arguments);
        
        foreach ($this->afterCallbacks as $callback) {
            $callback($name, $arguments, $result, $this->registry);
        }
        
        return $result;
    }
    
    public function getSubject() {
        return $this->subject;
    }
}