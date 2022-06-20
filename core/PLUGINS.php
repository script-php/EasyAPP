<?php

class PLUGINS {

    public static $hooks = [];
	private static $actions = [];
	private static $registry = []; // register new hooks

    static function HOOK_TEST($hook) {
		$json = file_get_contents('storage/hooks/'.$hook.'.json');
		$array = json_decode($json);
		foreach($array as $action) {
			list($plugin_name, $plugin_method) = explode('/',$action);
			if (class_exists($plugin_name)) {
				$plugin = new $plugin_name();
				if (is_callable([$plugin, $plugin_method])) {
					call_user_func_array([$plugin, $plugin_method], []); # args
				}
			}
		} 
	}

    public static function Settings($plugin) {
		$module_id = md5(get_class($plugin)); // maybe add the version too: md5(get_class($plugin).$plugin->version)
		return ['status' => true]; // example
    }

    static function HOOK($name) {
		$name = strtoupper($name);
		// TODO: move everything into an predefined action in START
		#####################
		if($name == 'START') {
            PAGE::LOAD(); // move it into an action from base or from a plugin
		}
		#####################
		// load and execute plugins for this hook from cache or db
	}


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

	public static function REGISTRY(array $actions = []) {
		if(is_array($actions) && count($actions) > 0) {
			foreach($actions as $action) {
				self::$registry[] = $action;
			}
		}
	}

	public static function ACTION($hook, $action, $queue=0) {
		// TODO: queue
		if(empty(self::$actions[$hook]) || !empty(self::$actions[$hook]) && !in_array($action, self::$actions[$hook])) {
			self::$actions[$hook][] = $action;	
		}
	}

}