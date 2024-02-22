<?php

/**
* @package      Util
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Util {

    private $characters = array('\'','-','_','~','`','@','$','^','*','(',')','=','[',']','{','}','"','“','”','\\','|','?','.','>','<',',',':','/','+');

	private $html = array('&#39;','&#45;','&#95;','&#126;','&#96;','&#64;','&#36;','&#94;','&#42;','&#40;','&#41;','&#61;','&#91;','&#93;','&#123;','&#125;','&#34;','&#8220;','&#8221;','&#92;','&#124;','&#63;','&#46;','&#62;','&#60;','&#44;','&#58;','&#47;','&#43;');

    public function chars2Html($text) {
		return str_replace($this->characters, $this->html, $text);
	}
    
    public function html2Chars($text) {
		return str_replace($this->html, $this->characters, $text);
	}

	public function checkChars($text, $allowed_characters) {
		for($nr=0; $nr<strlen($text); $nr++) {
			$str = substr($text,$nr,1);
			$cate = substr_count($allowed_characters,$str);
			if($cate==0) {
				return FALSE;
			}
		}
		return TRUE;
	}

    public function contains(string $haystack = NULL, string $needle = NULL) {
		if($haystack == NULL || $needle == NULL) {
			return false;
		}
		return function_exists('str_contains') ? (str_contains($haystack, $needle)?true:false) : (strpos($haystack, $needle) ? true : false);
	}

	public function file2Class(string $file, string $add='') {
		$file = preg_replace('/[^a-zA-Z0-9_]/', '', $file); // Sanitize it
		$explode = explode('_', $file);
		$parts = $file;
		if(count($explode) > 0) {
			$parts = '';
			foreach($explode as $part) {
				$parts .= ucfirst($part);
			}
		}
		return ucfirst($add).$parts;
	}
	
	public function class2File(string $class, string $delete='') {
		$class = preg_replace('/[^a-zA-Z0-9]/', '', $class); // Sanitize it
		$class = preg_replace("/^{$delete}/","",$class);
		$class = preg_replace("/([A-Z])/","_$1",$class);
		$class = preg_replace("/^_/","",$class);
		$class = strtolower($class);
		return $class;
	}

    public function random($minlength=5, $maxlength=5, $uselower=true, $useupper=true, $usenumbers=true, $usespecial=false) {
		$charset = '';
		$key = '';
		if($uselower) { $charset .= "abcdefghijklmnopqrstuvwxyz"; }
		if($useupper) { $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; }
		if($usenumbers) { $charset .= "123456789"; }
		if($usespecial) { $charset .= "~@#$%^*()_+-={}|]["; }
		if($minlength > $maxlength) { $length = mt_rand($maxlength, $minlength); }
		else { $length = mt_rand($minlength, $maxlength); }
		for ($i = 0; $i < $length; $i++) { $key .= $charset[(mt_rand(0, strlen($charset) - 1))]; }
		return $key;
	}

    public function textIntegrity(string $text) {
		$text = preg_replace('/[\r\n]{3,}/', "\n\n", $text);
		$text = preg_replace('/[ \t]{3,}/', '  ', $text);
		return $text;
	}

	public function filter(string $value, array $options = NULL) {


		if($options != NULL) {
			$filter = array_key_exists('filter', $options) ? $options['filter'] : NULL;
			$type = array_key_exists('type', $options) ? $options['type'] : NULL;
			$HTML = array_key_exists('html', $options) ? $options['html'] : NULL;
				
			if($HTML) {
				$value = $this->chars2Html($value);
			}
			if($filter != NULL) {

				if(!$this->checkChars($value, $filter)) {
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

	public static function textTemplate(string $text,array $params=NULL) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{{'.strtoupper($param).'}}',$value,$text);
			}
		}
		return $text;
	}

	function hash($data) {
		return hash('sha256', $data);
	}

	function seo($text) {
		$text = strtolower($text);
		$text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);
		$text = preg_replace("/[\s\W]+/", " ", $text);
		$text = preg_replace("/\s[\s]+/", " ", $text);
		$text = str_replace(" ", "-", $text);
		$text = ltrim($text,'-');
		$text = rtrim($text,'-');
		return $text;
	}

	function simple_format_number($number) {
		if($number >= 1000) {
		   return round($number/1000,1) . "k";   // NB: you will want to round this
		}
		else {
			return $number;
		}
	}

	function format_number($n, $precision = 1) {
		if ($n < 900) {
			$n_format = number_format($n); // Default
		} 
		else if ($n < 900000) {
			$n_format = number_format($n / 1000, $precision). 'K'; // Thousand
		} 
		else if ($n < 900000000) {
			$n_format = number_format($n / 1000000, $precision). 'M'; // Million
		} 
		else if ($n < 900000000000) {
			$n_format = number_format($n / 1000000000, $precision). 'B'; // Billion
		} 
		else {
			$n_format = number_format($n / 1000000000000, $precision). 'T'; // Trillion
		}
		return $n_format;
	}

	function formatText($text) {
		
		$text = preg_replace('/__(.*?)__/', '<em>$1</em>', $text); // Italic
		$text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text); // Bold
		$text = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text); // Strikethrough
		$text = preg_replace('/```(.*?)```/', '<code>$1</code>', $text); // Monospace
		
		return $text;
	}

}