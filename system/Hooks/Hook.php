<?php

/**
* @package      Hook
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

namespace System\Hooks;

class Hook {

	private $hooks = []; // keep all the registered hooks
	private $actions = []; // keep all actions	

	/** 
	 * Register a new hook to be able to use it later.
	 */
	public function register($hook) {
		$hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook); // Sanitize it
		$hook = strtoupper($hook);
		$hash = md5($hook);
		if(empty($this->hooks[$hash])) {
			$this->hooks[$hash] = $hook;
		}
	}


	/**
	* Attach an action to a known hook name.
	*/
	public function set($hook, $action, $queue=0) {
		// TODO: queue
		$hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook); // Sanitize it
		$action = preg_replace('/[^a-zA-Z0-9\\\\_\/]/', '', $action); // Sanitize it
		$hook = strtoupper($hook);
		$hash = md5($hook);
		// Attach an action to the hook only if it is registered
		if(!empty($this->hooks[$hash]) && $this->hooks[$hash] == $hook) {
			if(empty($this->actions[$hook]) || !empty($this->actions[$hook]) && !in_array($action, $this->actions[$hook])) {
				$this->actions[$hook][] = $action;	
			}
		}
	}


	/**
	 * Run a hook / execute all actions attached to a hook.
	 */
	public function run($hook) {
		$hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook); // Sanitize it
		$hook = strtoupper($hook);
		$hash = md5($hook);
		// Run the hook only if it is registered
		if(!empty($this->hooks[$hash]) && $this->hooks[$hash] == $hook) {
			if(!empty($this->actions[$hook])) {
				foreach($this->actions[$hook] as $action) {
					$explode = explode('/',$action);
					$class_name = (!empty($explode[0])) ? $explode[0] : NULL;
					$class_hash = md5($class_name);
					if ($class_name != NULL && class_exists($class_name)) {
                        $class = new $class_name(); 
						$method = (!empty($explode[1])) ? $explode[1] : NULL;
						if ($method != NULL && is_callable([$class, $method])) {
							call_user_func_array([$class, $method], []); # args
						}
					}
				}
			}
		}
	}
	
}