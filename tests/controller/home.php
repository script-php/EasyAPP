<?php

/**
* @package      Home page
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ControllerHome extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {

		$this->response->setOutput('Homepage');

        $this->load->model('test');

        $this->model_test->teste();
		
	}

}
