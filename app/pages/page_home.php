<?php

/**
* @package      Home page example
* @version      1.0.0
* @author       Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

class page_home {
	

	private $settings = [];

    
	// do something when the page is loaded 
	function __construct() {
		$settings = APP::Settings($this); // load settings
	}


	// show or do something something when index.php?route=home or index.php?route=home/index its accessed
	function index() {
		echo 'first page';
	}


	// show or do something something when index.php?route=home/section its accessed
	function section() {
		//write your page logical code here
		//example:

		// prevent csrf on get
		if(!APP::GET_CSRF()) {
			// posible not safe 
		}

		// prevent csrf on post
		if(APP::POST_CSRF()) {
			// posible not safe
		}

	}


	// show or do something something when index.php?route=home/db_usage its accessed
	function db_usage() {
		// How to use a database:
		$users = APP::QUERY("main", "SELECT * FROM users WHERE validated='1' AND active=:active", [
			':active'		=> '1',
			':something'	=> 'something'
		]);

		//How to use a different database:
		$files = APP::QUERY("db2", "SELECT * FROM files");
	}


	// show or do something something when index.php?route=home/classes its accessed
	function classes() {
		// all classes from classes folder are autoloaded, ready to be used
		$my_class = new MyOwnClass();
		$my_class->method();

		// OR

		MyOwnClass::method();
	}


	// show or do something something when index.php?route=home/functions its accessed
	function functions() {
		// if you have a function and you use it just in few pages but you dont want to write it everytime in these pages,
		// you can put it into sa file called "my_function_name.function.php" in functions folder
		// usage example: 
		$body = APP::FUNCTION("my_function_name")("String", 123, false);
	}


	// show or do something something when index.php?route=home/app_variable its accessed
	function app_variable() {
		// set a variable:
		$value = 'variable value';
		APP::VAR('variable', $value);

		// rewrite
		APP::VAR('variable', 'set other value');

		// use it
		$test = APP::VAR('variable');
		echo APP::VAR('variable');
	}


	// show or do something something when index.php?route=home/show_template its accessed
	function show_template() {
		// here you have two options:

		// first one is when you just want to populate the template with data, 
		// the template will be like this: <div>{CONTENT}</div>
		$data['content'] = 'Show you content!';
		echo APP::HTML('app/layout/test/test.html', $data);

		// OR
		// when you need to use variables in the template
		// the template will be like this:
		/* <div><?php foreach($posts as $post) { ?>
				<div><?php echo $post; ?>
			<?php } ?></div>
		*/
		$data['posts'] = array('post 1', 'post 2');
		echo APP::HTML('app/layout/test/test.html', $data, true);

	}


	// show or do something something when index.php?route=home/plugins its accessed
	function plugins() {
		$plugins = APP::GET_PLUGINS();

		foreach($plugins as $plugin) {
			$package = (isset($plugin['package']) ? $plugin['package'] : 'Unknown');
			$version = (isset($plugin['version']) ? $plugin['version'] : 'Unknown');
			$author = (isset($plugin['author']) ? $plugin['author'] : 'Unknown');
			$copyright = (isset($plugin['copyright']) ? $plugin['copyright'] : 'Unknown');
			$link = (isset($plugin['link']) ? $plugin['link'] : 'Unknown');

			echo 'Name: ' . $package.'<br>';
			echo 'Author: ' . $author.'<br>';
			echo 'Website: ' . $link.'<br>';
			echo 'Version: ' . $version.'<br>';
			echo 'Copyright: ' . $copyright.'<br>';
			echo '<a href="#">Install</a> | <a href="#">Uninstall</a>';
			echo '<hr>';
		}
	}


	// this method is used to register all custom hooks used in the page
	// all hooks will be visible in admin panel when the admin can atach action to each hook.
	function __hooks() {
		return [
			'MY_OWN_HOOK',
			'another_custom_hook'
		];
	}

}

?>