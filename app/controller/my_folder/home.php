<?php

/**
* @package      Home page example
* @version      1.0.0
* @author       Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

class ControllerMyFolderHome extends Controller {

	private $settings = [];

	function __construct($registry) {
		$this->registry = $registry;
	}
	

	function index() {
		// show or do something something when index.php?route=home or index.php?route=home/index its accessed

		$data['title'] = 'Title page';
		$data['body'] = 'Content page';

		//echo $this->show->html('app/layout/base.html', $data); // valid, working
		echo $this->show->html('app/layout/base_v2.html', $data, true);
	}
	

	function utils() {
		// show or do something something when index.php?route=home/utils its accessed
		
		$text = "This is an example !@#$%^&*()_+:|<>,.?/{}][";

		// example #1
		$chars2html = $this->util->chars2Html($text); // text to html
		$html2chars = $this->util->html2Chars($chars2html); // html to text
		echo $html2chars;

		// example #2
		$check = $this->util->checkChars($text, "abcdefghijklmnopqrstuvwxyz");
		if(!$check) {
			pre('The text contains more characters than accepted');
		}

		// example #3
		$check = $this->util->contains($text, 'exampl');
		if($check) {
			pre('the string contains this piece of text');
		}

		// example #4
		pre($this->util->file2Class('my_new_home_page'));

		// example #5
		pre($this->util->class2File('MyNewHomePage'));

		// example #6
		$minlength = 5;
		$maxlength = 30;
		$uselower = true;
		$useupper = true;
		$usenumbers = true;
		$usespecial = false;
		$random = $this->util->random($minlength, $maxlength, $uselower, $useupper, $usenumbers, $usespecial);
		pre($random);

		// example #7
		$text = "This is a                     						text with 							            a 



		lot of           				whitespaces and new lines.";
		pre($this->util->textIntegrity($text));

	}


	function model_usage() {

		$this->load->model('another_model/another_one');

		$users = $this->model_another_model_another_one->test();

		foreach($users as $user) {
			echo $user['name'];
		}

	}

	function controller_usage() {

		$this->load->controller('error');

		echo $this->controller_error->index();

	}


	function db_usage() {
		// show or do something something when index.php?route=home/db_usage its accessed

		// How to use a database:
		$users = $this->db->query("SELECT * FROM users WHERE validated='1' AND active=:active", [
			':active'		=> '1',
			':something'	=> 'something'
		]);

		//How to use a different database:
		$files = $this->db->query("SELECT * FROM users");
	}


	// show or do something something when index.php?route=home/classes its accessed
	function classes() {
		// all classes from classes folder are autoloaded, ready to be used
		$my_class = new my_class();
		echo $my_class->test();

		// OR

		echo my_other_class::test();
	}


	// show or do something something when index.php?route=home/show_template its accessed
	function show_template() {
		// here you have two options:

		// first one is when you just want to populate the template with data, 
		// the template will be like this: <div>{CONTENT}</div>
		$data['body'] = 'Show you content!';
		echo $this->show->html('app/layout/base.html', $data);

		// OR
		// when you need to use variables in the template
		// the template will be like this:
		/* <div><?php foreach($posts as $post) { ?>
				<div><?php echo $post; ?>
			<?php } ?></div>
		*/
		$data['body'] = array('post 1', 'post 2');
		echo $this->show->html('app/layout/base_v2.html', $data, true);

	}


	// show or do something something when index.php?route=home/plugins its accessed
	function plugins() {
		$plugins = $this->plugin->list();

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


