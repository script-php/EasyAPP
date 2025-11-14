<?php

/**
* @package      Home page
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

use App\Model\User;

class ControllerHome extends Controller {

	/**
	 * Constructor
	 * @param Registry $registry
	 */
	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {

		/**
		 * Home Page Example
		 * This is the default home page controller method.
		 */

		$data = [];
		$data['title'] = 'Welcome to EasyAPP Framework'; // Page title
		$data['subtitle'] = 'A Modern PHP Framework for Rapid Development'; // Page subtitle
		$data['content'] = $this->load->view('home/index.html'); // Load view

		$this->load->model('home'); // Load model
		
		$method = $this->model_home->method(); // Use model data
		
		// When using direct echo, don't use setOutput to avoid conflicts
		$this->response->setOutput($this->load->view('base.html', $data)); // Render view
	}

	/**
	 * Static page example
	 */
	function page() {
		$data = [];
		$data['title'] = 'Static Page Example';
		$data['subtitle'] = 'This is a static page method';
		$data['content'] = '<p>This is a static page served by the <strong>page</strong> method in the <strong>ControllerHome</strong> class.</p>';		
		$this->response->setOutput($data['content']); // Render view
	}

	/**
	 * Test improved view method
	 */
	function testview() {

		/**
		 * Improved View Method Example
		 * This example demonstrates the improved view method with enhanced security features.
		 */
		$data = [];
		$data['title'] = 'View Method Test';
		$data['message'] = 'Testing improved view method with security enhancements';
		$data['timestamp'] = time();
		$data['items'] = ['Feature 1: Input validation', 'Feature 2: Security protection', 'Feature 3: Safe data extraction'];
		
		$content = $this->load->view('test/view_test.html', $data);
		$this->response->setOutput($content);
	}




	function testLibrary() {
		/**
		 * Library Loading Example
		 * This example demonstrates how to load and use a custom library
		 * using the EasyAPP Framework's loader.
		 */
		$data = [];
		$data['title'] = 'Library Test';
		$this->load->library('testlibrary'); // Load custom library
		$testLib = $this->library_testlibrary; // Access the loaded library

		$this->response->setOutput($this->load->view('test/view_test.html', $data));
	}





	function testLanguage() {

		/**
		 * Language Support Example
		 * This example demonstrates how to load language files and retrieve translations
		 * using the EasyAPP Framework's language library.
		 */
		$data = [];
		$data['title'] = 'Language Test';
		
		// Load language file
		$this->load->language('home');
		$data['text_title'] = $this->language->get('title');
		$data['text_text'] = $this->language->get('text');

		pre($data['text_title']);
		pre($data['text_text']);
	}






	function ormTest() {

		/**
		 * ORM Example
		 * This example demonstrates basic usage of the EasyAPP Framework's ORM capabilities.
		 */
		$data = [];
		$data['title'] = 'ORM Test';

		$users = User::query()
			->where('status', '=', 1)
			->orderBy('name', 'ASC')
			->get();

		foreach ($users as $user) {
			echo $user->name . "\n";
		}


		$user = User::find(1);
		if ($user) {
			$user->changeStatus(0); // Change status to 0
			echo "User status changed successfully.";
		} else {
			echo "User not found.";
		}


		$posts = App\Model\Post::query()->whereDate('created_at', '2025-11-11')->get();
		foreach ($posts as $post) {
			echo $post->title . "\n";
		}

	}



	function csrfExample() {

		/**
		 * CSRF Protection Example
		 * This example demonstrates how to generate and validate CSRF tokens
		 * using the EasyAPP Framework's CSRF library.
		 */

		if($this->request->server('REQUEST_METHOD') == 'POST') {
			$tokenValid = $this->csrf->validateRequest('my_form');

			$errors = [];

			if(!$tokenValid) {
				$errors[] = "Invalid CSRF token.";
			}

			if (!empty($errors)) {
				foreach ($errors as $error) {
					echo '<p style="color:red;">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
				}
			}

			if(empty($errors)) {
				echo '<p style="color:green;">CSRF token validated successfully.</p>';
				$name = $this->request->post('name');
				echo "Form submitted. Name: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
			}
			
		}

		$token = $this->csrf->generateToken('my_form');

		echo '<form method="POST" action="">
		<input type="hidden" name="csrf_token" value="' . $token . '">
		<input type="text" name="name">
		<button type="submit">Save</button>
		</form>';

	}


}
