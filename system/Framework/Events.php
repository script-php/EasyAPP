<?php
namespace System\Framework;

class Events {
    protected $listeners = [];

    public function on($event, callable $callback, $priority = 10) {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        usort($this->listeners[$event], function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return true;
    }
    
    public function off($event, callable $callback) {
        if (!isset($this->listeners[$event])) {
            return false;
        }
        
        foreach ($this->listeners[$event] as $key => $listener) {
            if ($listener['callback'] === $callback) {
                unset($this->listeners[$event][$key]);
                return true;
            }
        }
        
        return false;
    }
    
    public function trigger($event, &$data = []) {
        $results = [];
        
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $results[] = call_user_func_array($listener['callback'], [&$data]);
            }
        }
        
        return $results;
    }
    
    public function hasListeners($event) {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }
    
    public function clear($event = null) {
        if ($event === null) {
            $this->listeners = [];
        } else if (isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
    }
    
}