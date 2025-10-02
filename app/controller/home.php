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
		$data['title'] = 'Welcome to EasyAPP Framework';
		$data['subtitle'] = 'A Modern PHP Framework for Rapid Development';
		$data['content'] = $this->load->view('home/index.html');
		
		$this->response->setOutput($this->load->view('base.html', $data));
	}

}
