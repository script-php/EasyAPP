<?php

class ControllerHome extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {

        $this->response->setOutput('home');
		
	}

}
