<?php

class ControllerStartupEvents extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {

        $this->event->register('before:controller/common/home', new System\Framework\Action('event|beforeController'));
        $this->event->register('after:controller/common/home', new System\Framework\Action('event|afterController'));

		$this->event->register('before:model/aa/test|test', new System\Framework\Action('event|beforeModel'));
		$this->event->register('after:model/aa/test|test', new System\Framework\Action('event|afterModel'));

		$this->event->register('before:language/en', new System\Framework\Action('event|beforeLanguage'));
		$this->event->register('after:language/en', new System\Framework\Action('event|afterLanguage'));

		$this->event->register('before:view/test_view.html', new System\Framework\Action('event|beforeView'));
		$this->event->register('after:view/test_view.html', new System\Framework\Action('event|afterView'));
		
	}

}
