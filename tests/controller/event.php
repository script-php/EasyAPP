<?php

class ControllerEvent extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}

	public function beforeController(&$route, &$args) {

	}

	public function afterController(&$route, &$args, &$output) {
		$output = 'replace output';
	}


    public function beforeModel(&$route, &$args) {

	}
	public function afterModel(&$route, &$args, &$output) {
		$output = 'replace output';
	}


	public function beforeLanguage(&$route) {
		$language['aaaa'] = 'bbbbb';
	}
	public function afterLanguage(&$route, &$language) {
		$language['aaaa'] = 'bbbbb';
	}


	public function beforeView(&$route, &$data) {
		// return 'before';
	}

	public function afterView(&$route, &$data, &$output) {
		// $output = 'new view';
	}

}
