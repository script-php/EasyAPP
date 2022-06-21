<?php

/**
* @package      HOOK
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

class HOOK {

	private static $hooks = []; // keep all the registered hooks
	private static $actions = []; // keep all actions
	private static $objects = []; // keep the objects of classes
	

	/** 
	 * Register a new hook to be able to use it later.
	 */
	public static function REGISTER($hook) {
		$hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook); // Sanitize it
		$hook = strtoupper($hook);
		$hash = md5($hook);
		if(empty(self::$hooks[$hash])) {
			self::$hooks[$hash] = $hook;
		}
	}


	/**
	* Attach an action to a known hook name.
	*/
	public static function SET($hook, $action, $queue=0) {
		// TODO: queue
		$hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook); // Sanitize it
		$action = preg_replace('/[^a-zA-Z0-9_\/]/', '', $action); // Sanitize it
		$hook = strtoupper($hook);
		$hash = md5($hook);
		// Attach an action to the hook only if it is registered
		if(!empty(self::$hooks[$hash]) && self::$hooks[$hash] == $hook) {
			if(empty(self::$actions[$hook]) || !empty(self::$actions[$hook]) && !in_array($action, self::$actions[$hook])) {
				self::$actions[$hook][] = $action;	
			}
		}
	}


	/**
	 * Run a hook / execute all actions attached to a hook.
	 */
	static function RUN($hook) {
		$hook = preg_replace('/[^a-zA-Z0-9_]/', '', $hook); // Sanitize it
		$hook = strtoupper($hook);
		$hash = md5($hook);
		// Run the hook only if it is registered
		if(!empty(self::$hooks[$hash]) && self::$hooks[$hash] == $hook) {
			if(!empty(self::$actions[$hook])) {
				foreach(self::$actions[$hook] as $action) {
					$explode = explode('/',$action);
					$class_name = (!empty($explode[0])) ? $explode[0] : NULL;
					$class_hash = md5($class_name);
					if ($class_name != NULL && class_exists($class_name)) {
						// Prevent reinitialization of a class if we have to run more actions from it.
						// I hope it's a good idea :/
						if(empty(self::$objects[$class_hash])) {
							self::$objects[$class_hash] = new $class_name(); 
						}
						$method = (!empty($explode[1])) ? $explode[1] : NULL;
						if ($method != NULL && is_callable([self::$objects[$class_hash], $method])) {
							call_user_func_array([self::$objects[$class_hash], $method], []); # args
						}
					}
				}
			}
		}
	}
	

    // static function GET_PLUGINS() {
    static function GET_PLUGINS() {
		$get_plugins = null;
		$directory = new RecursiveDirectoryIterator('app/plugins', RecursiveDirectoryIterator::SKIP_DOTS);
		if (is_null($get_plugins)) {
			$get_plugins = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
		}
		$contains = '.php';
		$plugins = array();
		foreach ($get_plugins as $file_plugin) {
			if (APP::CONTAINS(strtolower($file_plugin), $contains)) {
				if ($file_plugin->isReadable()) {
					include_once $file_plugin->getPathname();
				}
				$plugin = APP::File2Class(str_replace($contains, '', $file_plugin->getFilename()), 'Plugin'); // get the name of the class
				$plugin_info = NULL;
				if (class_exists($plugin)) { // check if the class exists. Maybe the name of the class is different than the name of file.
					$plugin_class = new $plugin(); // execute the class
					//if (is_callable([$plugin_class])) { // check if the class have the method called "plugin" 
						//$register = call_user_func_array([$plugin_class, 'register'], []); // get the register actions and hooks
						$class = new ReflectionClass($plugin_class);
						$doc = $class->getDocComment();
						
						preg_match_all('/@([a-z]+?)\s+(.*?)\n/i', $doc, $info); // https://stackoverflow.com/questions/11461800/how-to-parse-doc-comments
						
						if(isset($info[1]) || count($info[1]) !== 0){
							$plugin_info = array_combine(array_map("trim",$info[1]), array_map("trim",$info[2]));
						}
					//}
				}
				if($plugin_info !== NULL) { // if everything is ok, show the details about the plugin
					$plugins[] = $plugin_info; // show the info about plugin
				}
				// break;
			}
		}
		return $plugins;
	}

}