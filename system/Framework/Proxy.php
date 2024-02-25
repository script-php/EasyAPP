<?php

namespace System\Framework;
class Proxy {

	public function __get($method) {
		return $this->{$method};
	}	

	public function __set($method, $value) {
		 $this->{$method} = $value;
	}
	
	public function __call($method, $args) {
		$arg_data = array();
		
		$args = func_get_args();
		
		foreach ($args as $arg) {
			if ($arg instanceof Ref) {
				$arg_data[] =& $arg->getRef();
			} else {
				$arg_data[] =& $arg;
			}
		}
		if (isset($this->{$method})) {		
			return call_user_func_array($this->{$method}, $arg_data);	
		} else {
			$trace = debug_backtrace();
			exit('<b>Notice</b>: Undefined method: ' . $method . ' in <b>' . $trace[0]['file'] . '</b> on line <b>' . $trace[0]['line'] . '</b>');
		}
	}
}