<?php

/**
* @package      Request
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;
class Request {
	public $get = array();
	public $post = array();
	public $session = array();
	public $cookie = array();
	public $files = array();
	public $server = array();
	public $ip;
    public $registry;

	public function __construct($registry) {
        $this->registry = $registry;
		$this->get = $this->clean($_GET);
		$this->post = $this->clean($_POST);
		$this->session = $this->clean($_SESSION);
		$this->request = $this->clean($_REQUEST);
		$this->cookie = $this->clean($_COOKIE);
		$this->files = $this->clean($_FILES);
		$this->server = $this->clean($_SERVER);
        $this->ip = $this->ip();
	}
	
	public function clean($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				unset($data[$key]);
				$data[$this->clean($key)] = $this->clean($value);
			}
		} else {
			$data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
		}
		return $data;
	}

    public function ip() {
		return $this->server['HTTP_CLIENT_IP'] ?? $this->server["HTTP_CF_CONNECTING_IP"] ?? $this->server['HTTP_X_FORWARDED'] ?? $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['HTTP_FORWARDED'] ?? $this->server['HTTP_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	public static function fingerprint(int $x = NULL) {
		$string = $_SERVER['HTTP_USER_AGENT'];
		$bracket_place = 0;
		$bracket_start = NULL;
		$return = array();
		$split = str_split( $string );
		for($i=0;$i<count($split);$i++) {
			# Set +1 everytime I find an opening bracket
			if($split[$i] == "(") { $bracket_place++; }
			# Save the position of the first opening bracket
			if($split[$i] === "(" && $bracket_place === 1) { $bracket_start = $i; }
			# When I find the last closing bracket I store in array the positions of the opening and closing brackets
			if($split[$i] === ")" && $bracket_place === 1) {
				$return[] = substr($string, ($bracket_start+1), (($i-$bracket_start)-1));
				$bracket_start = NULL;
			}	
			# Set -1 everytime I find an closing bracket
			if($split[$i] == ")") { $bracket_place--; }
		}
		if(count($return) === 0 || $x < 0 || $x > count($return)-1) {
			return NULL;
		}
		if($x === NULL) {
			return implode(' ~ ', $return);
		}
		return $return[$x];
	}

    public function csrf($method='get') {
        $util = $this->registry->get('util');
        $request_method = strtolower($this->server['REQUEST_METHOD']);
        $method = strtolower($method);
		if ($request_method===$method) {
            if($method == 'get') {
                $origin = !empty($this->server['HTTP_REFERER']) ? $this->server['HTTP_REFERER'] : NULL;
            }
            else if($method == 'post') {
                $origin = !empty($this->server['HTTP_ORIGIN']) ? $this->server['HTTP_ORIGIN'] : NULL;
            }
			$hostname = !is_null($this->server['HTTP_HOST']) ? $this->server['HTTP_HOST'] : NULL;
			if($origin != NULL && $util->contains($origin,$hostname)) {
				return true;
			}
		}
		return false;
	}

	public function end_session() {
		$request = $this->registry->get('request');
		foreach($request->session as $key => $value) {
			$_SESSION[$key] = $value;
		}
	}
	
	public function redirect($url) {
		header('Location: ' . $url);
		exit();
	}

	public function setcookie($name, $value, $time, $path, $domain) {
		setcookie($name, $value, $time, $path, $domain);
		$this->cookie[$name] = $value;
	}

}