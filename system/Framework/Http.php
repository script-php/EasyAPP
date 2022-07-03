<?php

/**
* @package      Http
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

namespace System\Framework;

class Http {

    public function post(string $name, array $options = NULL) {
		$util = new Util();
		$return = NULL;
		if(isset($_POST[$name]) && $_POST[$name] != '') {
			$return = $util->filter($_POST[$name], $options);
		}
		return $return;
	}

	public function get(string $name, array $options = NULL) {
		$util = new Util();
		$return = NULL;
		if(isset($_GET[$name]) && $_GET[$name] != '') {
			$return = $util->filter($_GET[$name], $options);
		}
		return $return;
	}

    public function postCsrf() {
		$util = new Util();
		if ($_SERVER['REQUEST_METHOD']==='POST') {
			$ORIGIN = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : NULL;
			$HOSTNAME = !is_null($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
			if($ORIGIN != NULL && $util->contains($ORIGIN,$HOSTNAME)) {
				return true;
			}
		}
		return false;
	}

    public function getCsrf() {
		$util = new Util();
		if ($_SERVER['REQUEST_METHOD']==='GET') {
			$REFERER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
			$HOSTNAME = !is_null($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
			if($REFERER != NULL && $util->contains($REFERER,$HOSTNAME)) {
				return true;
			}
		}
		return false;
	}

	public function redirect($address) {
		header('Location: '.$address);
		exit;
	}

    

	public function ip() {
		return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER['HTTP_X_FORWARDED'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	public function fingerprint(int $x = NULL) {
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

}