<?php

class ControllerStartupEvents extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {

        // $this->event->register('after:controller/template', new System\Framework\Action('errors/error|test'));
        // $this->event->register('after:controller/radio/home', new System\Framework\Action('errors/error|test'));
		
	}

}
