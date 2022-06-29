<?php
namespace System\Request;

class Http {

    public static function post(string $name, array $options = NULL) {
		$return = NULL;
		if(isset($_POST[$name]) && $_POST[$name] != '') {
			$return = self::options($_POST[$name], $options);
		}
		return $return;
	}

	public static function get(string $name, array $options = NULL) {
		$return = NULL;
		if(isset($_GET[$name]) && $_GET[$name] != '') {
			$return = self::options($_GET[$name], $options);
		}
		return $return;
	}

    public static function post_csrf() {
		if ($_SERVER['REQUEST_METHOD']==='POST') {
			$ORIGIN = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : NULL;
			$HOSTNAME = !is_null($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
			if($ORIGIN != NULL && APP::CONTAINS($ORIGIN,$HOSTNAME)) {
				return true;
			}
		}
		return false;
	}

    public static function get_csrf() {
		if ($_SERVER['REQUEST_METHOD']==='GET') {
			$REFERER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
			$HOSTNAME = !is_null($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
			if($REFERER != NULL && APP::CONTAINS($REFERER,$HOSTNAME)) {
				return true;
			}
		}
		return false;
	}

	public static function redirect($address) {
		header('Location: '.$address);
		exit;
	}

    private static function options(string $value, array $options = NULL) {
		if($options != NULL) {
			$filter = array_key_exists('filter', $options) ? $options['filter'] : NULL;
			$type = array_key_exists('type', $options) ? $options['type'] : NULL;
			$HTML = array_key_exists('html', $options) ? $options['html'] : NULL;
				
			if($HTML) {
				$value = APP::Chars2HTML($value);
			}
			if($filter != NULL) {
				if(!APP::checkChars($value, $filter)) {
					$value = NULL;
				}
			}
			if($type != NULL) {
				if (preg_match('/[a-zA-Z]/', $value) && preg_match('/[0-9]/', $value)) {
					$valueType = "alphanumeric";
					if(preg_match('/[^a-zA-Z0-9]/', $value)) {
						$valueType = "alphanumeric+";
					}
				}
				else if (preg_match('/[a-zA-Z]/', $value) && preg_match('/[^0-9]/', $value)) {
					$valueType = "alphabetic";
					if(preg_match('/[^a-zA-Z]/', $value)) {
						$valueType = "alphabetic+";
					}
				}
				else if (preg_match('/[0-9]/', $value) && preg_match('/[^a-zA-Z]/', $value)) {
					$valueType = "numeric";
					if(preg_match('/[^0-9]/', $value)) {
						$valueType = "numeric+";
					}
				}
				if($valueType != strtolower($type)) {
					$value = NULL;
				}
			}
		}
		return $value;
	}

}