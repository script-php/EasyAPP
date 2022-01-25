<?php
/*
** @File: APP.php
** @Author: Glitch3R(YoYo)
** @Created: 23.01.2022
**
** APP::RENDER_PAGES();
** APP::RENDER_TEMPLATE($path_file_html, [array]);
** APP::RENDER_JSON([array]);
** APP::POST($name, [options]);
** APP::GET($name, [options]);
** APP::Chars2HTML($text);
** APP::HTML2Chars($text);
** APP::checkChars($text,"allowed characters");
** APP::TextIntegrity($text);
** APP::FINGERPRINT();
** APP::TextTemplate(string $text,array $params=NULL);
** APP::FILE($path);
** APP::REDIRECT($address);
** APP::IP();
** APP::RANDOM($minlength=5, $maxlength=5, $uselower=true, $useupper=true, $usenumbers=true, $usespecial=false);
** APP::MAIL($to, $subject, $message);
** APP::CALL("function_name")(arguments);
*/


/*
** TODO: make a function to load (include) and use a class witout to include all classes from a folder...
*/


class APP {
	
	public static $get_page = "page";
	
	public static $index_page = "home";
	
	public static $error_page = "404";
	
	public static $folder_page = "app/pages";
	
	public static $folder_functions = "app/functions";
	
	public static $folder_classes = "app/classes";
	
	private static $characters = array('\'','-','_','~','`','@','$','^','*','(',')','=','[',']','{','}','"','“','”','\\','|','?','.','>','<',',',':','/','+');
	
	private static $html = array('&#39;','&#45;','&#95;','&#126;','&#96;','&#64;','&#36;','&#94;','&#42;','&#40;','&#41;','&#61;','&#91;','&#93;','&#123;','&#125;','&#34;','&#8220;','&#8221;','&#92;','&#124;','&#63;','&#46;','&#62;','&#60;','&#44;','&#58;','&#47;','&#43;');
	
	
	public static function RENDER_PAGES() {
		
		$page = self::GET(self::$get_page, ['type'=>'alphabetic']);
		
		if(self::checkChars($page, "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ.-_")) {

			if($page == NULL) {
				$page = self::$index_page;
			}

			if(file_exists(self::$folder_page.'/'.$page.'.page.php')) {
				include self::$folder_page.'/'.$page.'.page.php';
			}
			else {
				include self::$folder_page.'/'.self::$error_page.'.page.php';
			}
			
		}
		else {
			exit();
		}
		
	}
	
	
	public static function RENDER_TEMPLATE($path,$array=NULL) {
		if(file_exists($path)) {
			ob_start();
			include $path;
			$path = ob_get_contents();
			ob_end_clean();
		}
		else { exit('File "'.$path.'" does not exist.'); }
		if($array == NULL) { $path = $path; }
		else {
			foreach($array as $key => $value) { $path = str_replace('{'.strtoupper($key).'}',$value,$path); }
			$path = $path;
		}
		$path = str_replace("\t", "", $path);
		if(preg_match('/(\s){2,}/s', $path) === 1) { $path = preg_replace('/(\s){2,}/s', '', $path); }
		$path = preg_replace("/[\n\r]/","",$path);
		return $path;
	}
	
	
	public static function RENDER_JSON($Response) {
		header('Content-type: text/json;charset=UTF-8');
		echo json_encode($Response);
	}
	
	
	private static function OPTIONS(string $value, array $options = NULL) {
		if($options != NULL) {
			$filter = array_key_exists('filter', $options) ? $options['filter'] : NULL;
			$type = array_key_exists('type', $options) ? $options['type'] : NULL;
			$HTML = array_key_exists('html', $options) ? $options['html'] : NULL;
				
			if($HTML) {
				$value = self::Chars2HTML($value);
			}
			if($filter != NULL) {
				if(!self::checkChars($value, $filter)) {
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
	
	public static function POST(string $name, array $options = NULL) {
		$return = NULL;
		if(isset($_POST[$name]) && $_POST[$name] != '') {
			$return = self::OPTIONS($_POST[$name], $options);
		}
		return $return;
	}
	
	public static function GET(string $name, array $options = NULL) {
		$return = NULL;
		if(isset($_GET[$name]) && $_GET[$name] != '') {
			$return = self::OPTIONS($_GET[$name], $options);
		}
		return $return;
	}
	
	public static function Chars2HTML($text) {
		return str_replace(self::$characters, self::$html, $text);
	}
	
	public static function HTML2Chars($text) {
		return str_replace(self::$html, self::$characters, $text);
	}
	
	public static function checkChars($text, $allowed_characters) {
	
		for($nr=0; $nr<strlen($text); $nr++) {
			$str = substr($text,$nr,1);
			$cate = substr_count($allowed_characters,$str);
			if($cate==0) {
				return FALSE;
			}
		}
		return TRUE;

	}
	
	
	//UTIL::CALL("oFunctie")("param 1", "param 2");
	public static function CALL($function) {
		include self::$folder_functions.'/'.$function.'.function.php';
		return $function;
	}
	
	public static function TextIntegrity(string $text) {
	
		$text = preg_replace("/^[\t|\s|\r|\n]+/", "", $text);
		$text = preg_replace("/[\t|\s|\r|\n]+$/", "", $text);
		
		return $text;
		
	}
	
	public static function FINGERPRINT(int $x = NULL) {
	
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
	
	public static function TextTemplate(string $text,array $params=NULL) {
		if($params != NULL) {
			foreach($params as $param => $value) {
				$text = str_replace('{'.strtoupper($param).'}',$value,$text);
			}
		}
		return $text;
	}
	
	public static function FILE($path) {
		$patch = NULL;
		if(file_exists($path)) {
			ob_start();
			include $path;
			$path = ob_get_contents();
			ob_end_clean();
			return $path;
		}
		else { exit('File "'.$path.'" does not exist.'); }
	}
	
	public static function REDIRECT($address) {
		header('Location: '.$address);
		exit;
	}
	
	public static function IP() {
		return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER['HTTP_X_FORWARDED'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
	
	public static function RANDOM($minlength=5, $maxlength=5, $uselower=true, $useupper=true, $usenumbers=true, $usespecial=false) {
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
	
	public static function MAIL($to, $subject, $message) {
		GLOBAL $config;
	
		if(filter_var($to, FILTER_VALIDATE_EMAIL)) {
			$headers = "From: " . $config['email'] . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
			mail($to, $subject, $message, $headers);
			return TRUE;
		}
		else{
			return FALSE;
		}

	}
	
}