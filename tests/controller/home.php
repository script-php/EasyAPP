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

        // $this->load->language('en');
        // pre($this->language->get('heading_title'));

        // $this->load->model('home/home');
        $this->response->setOutput($this->load->view('test_view.html'));
		
	}

}
