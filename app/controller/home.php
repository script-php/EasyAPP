<?php

/**
* @package      Home page
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ControllerHome extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {
		$data = [];
		$data['title'] = 'Welcome to EasyAPP Framework'; // Page title
		$data['subtitle'] = 'A Modern PHP Framework for Rapid Development'; // Page subtitle
		$data['content'] = $this->load->view('home/index.html'); // Load view

		$model = $this->load->model('common/home'); // Load model
		// Use model data
		$data['test'] = $model->test;
		
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
		$data = [];
		$data['title'] = 'View Method Test';
		$data['message'] = 'Testing improved view method with security enhancements';
		$data['timestamp'] = time();
		$data['items'] = ['Feature 1: Input validation', 'Feature 2: Security protection', 'Feature 3: Safe data extraction'];
		
		$content = $this->load->view('test/view_test.html', $data);
		$this->response->setOutput($content);
	}


	function testLibrary() {
		$data = [];
		$data['title'] = 'Library Test';
		
		// Load custom library
		// $appLibrary = $this->load->library('test');
		// $data['message'] = $appLibrary->getMessage();

		// pre($data['message']);

		$this->response->setOutput($this->load->view('test/view_test.html', $data));
	}

}
