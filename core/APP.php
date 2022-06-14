<?php
/*
** Utils methods from APP:
** 
** APP::RENDER_PAGES();
** APP::HTML($path_file_html, [array]);
** APP::JSON([array],boolean); // will return json if 2nd param is false or will print it with text/json header by default
** APP::POST($name, [options]);
** APP::GET($name, [options]);
** APP::Chars2HTML($text);
** APP::HTML2Chars($text);
** APP::checkChars($text,"allowed characters");
** APP::TextIntegrity($text);
** APP::FINGERPRINT();
** APP::TEXT(string $text,array $params=NULL);
** APP::FILE($path);
** APP::REDIRECT($address);
** APP::IP();
** APP::RANDOM($minlength=5, $maxlength=5, $uselower=true, $useupper=true, $usenumbers=true, $usespecial=false);
** APP::MAIL($to, $subject, $message);
** APP::FUNCTION("function_name", $functions_args...);
** APP::PDO($servername,$host,$name,$user,$pass,$options,$encoding);
** APP::QUERY($servername, $query, $array_params);
** APP::VAR('the_name_of_internal_variable', 'value'); to set a value OR
** APP::VAR('the_name_of_internal_variable') to get the value
** APP::CONTAINS(string $haystack, string $needle) : boolean
** APP::POST_CSRF() : boolean
** APP::GET_CSRF() : boolean
*/

class APP {

	public static $variable = array();

	private static $conn = array();

	private static $queries = 0;

	public static $route = "route";

	public static $home_page = "PageHome";

	public static $error_page = "PageError";

	public static $folder_functions = "app/functions";

	public static $folder_classes = "app/classes"; # autoloader classes

	public static $folder_plugins = 'app/plugins'; # autoloader plugins

	public static $folder_pages = 'app/pages'; # autoloader pages

	public static $hooks = [];
	public static $actions = [];
	private static $registry = []; // register new hooks

	private static $characters = array('\'','-','_','~','`','@','$','^','*','(',')','=','[',']','{','}','"','“','”','\\','|','?','.','>','<',',',':','/','+');

	private static $html = array('&#39;','&#45;','&#95;','&#126;','&#96;','&#64;','&#36;','&#94;','&#42;','&#40;','&#41;','&#61;','&#91;','&#93;','&#123;','&#125;','&#34;','&#8220;','&#8221;','&#92;','&#124;','&#63;','&#46;','&#62;','&#60;','&#44;','&#58;','&#47;','&#43;');

	public static function PDO(string $servername,string $host,string $name,string $user,string $pass,array $options=NULL,string $encoding='utf8') {
		$conn = '';
		if(class_exists('PDO')) {
			try{
				if($options == NULL) {
					$options = [
						PDO::MYSQL_ATTR_INIT_COMMAND        => "SET NAMES {$encoding}",
						PDO::ATTR_PERSISTENT                => true, // Long connection
						PDO::ATTR_EMULATE_PREPARES          => false, // turn off emulation mode for "real" prepared statements
						PDO::ATTR_DEFAULT_FETCH_MODE        => PDO::FETCH_ASSOC, //make the default fetch be an associative array
						PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => true,
						PDO::ATTR_ERRMODE                   => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
					];
				}
				else {
					$options = $options;
				}
				$conn = new PDO("mysql:host={$host};dbname={$name}",$user,$pass,$options);
				$conn -> exec("SET character_set_client='{$encoding}',character_set_connection='{$encoding}',character_set_results='{$encoding}';");
				return self::$conn[$servername] = $conn;
			}
			catch(PDOException $e) {
				exit($e->getMessage());
			}
		}
		else {
			exit('PDO is not installed on this server.');
		}
	}

	public static function QUERY($servername, $query, $params=NULL) {
		if(!isSet($query)){ $query=NULL; }
		if(!isSet($params)){ $params=NULL; }
		if(self::$conn[$servername] instanceof PDO) {
			if($query!=NULL) {
				$stmt = self::$conn[$servername] -> prepare($query);
				if($params != NULL) {
					foreach($params as $param => &$value) {
						
						$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
						
						$stmt -> bindParam($param, $value, $varType);
					}
				}
				$stmt -> execute();
				self::$queries++;
				return  $stmt; 
			}
			else {
				exit("Ohh, come on! Really? What do you want to do with this function if you not make a query?");
			}
		}
		else {
			exit('Why you bully this class? That thing you set there is not initiated by the PDO, so I think it\'s not a database. Do something good for this project and put a database ... You Mother Fucker.');
		}
	}

	//TODO: do it to show the number of queries per server
	public static function QUERIES() {
		return self::$queries;
	}

	public static function RENDER_PAGES() {
		$route = APP::GET(APP::$route);
		if($route == NULL) {
			$route = self::$home_page;
		}

		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route);

		$route_exploded = explode('/', $route);

		$class = 'page_'.$route_exploded[0];
		$method = (!empty($route_exploded[1])) ? $route_exploded[1] : 'index';

		if (substr($method, 0, 2) == '__') {
			$method = str_replace('__', '', $route);
		}

		if (class_exists($class)) {
			$page_class = new $class();
			if (is_callable([$page_class, $method])) {
				call_user_func_array([$page_class, $method], []); # args
			}
		}

	}

	public static function HTML(string $filename, array $data = [], bool $code = false) {
		if(file_exists($filename)) {
			if($code) {
				ob_start();
				extract($data);
				include $filename;
				$content = ob_get_contents();
				ob_end_clean();
			}
			else {
				$content = file_get_contents($filename);
				if($data != NULL) {
					foreach($data as $key => $value) {
						$content = str_replace('{'.strtoupper($key).'}', $value, $content); 
					}
				}
			}
			$content = str_replace("\t", "", $content);
			if(preg_match('/(\s){2,}/s', $content) === 1) {
				$content = preg_replace('/(\s){2,}/s', '', $content);
			}
			$content = preg_replace("/[\n\r]/","",$content);
			return $content;
		}
		else {
			exit('File "'.$filename.'" does not exist.');
		}
	}

	public static function JSON($Response, $header=TRUE) {
		$json = json_encode($Response);
		if($header) {
			header('Content-type: text/json;charset=UTF-8');
			echo $json;
		}
		else {
			return $json;
		}
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

	public static function FUNCTION($function) {
		$included = false;
		if(!function_exists($function)) {
			if(!file_exists(self::$folder_functions.'/'.$function.'.function.php')) { exit("The \"{$function}\" function file does not exist."); }
			else {
				$included = true;
				include self::$folder_functions.'/'.$function.'.function.php';
			}
			if(!function_exists($function) && $included) {
				exit("The \"{$function}\" function file was loaded but probably the function have a different name.");
			}
		}
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

	public static function TEXT(string $text,array $params=NULL) {
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
		if(filter_var($to, FILTER_VALIDATE_EMAIL)) {
			$headers = "From: " . APP::VAR('config')['email'] . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
			mail($to, $subject, $message, $headers);
			return TRUE;
		}
		else{
			return FALSE;
		}
	}

	public static function VAR($var, $value = NULL) {
		if($value === NULL) {
			return !empty(self::$variable[$var]) ? self::$variable[$var] : NULL;
		}
		else {
			self::$variable[$var] = $value;
		}
	}

	public static function CONTAINS(string $haystack = NULL, string $needle = NULL) {
		if($haystack == NULL || $needle == NULL) {
			return false;
		}
		return function_exists('str_contains') ? (str_contains($haystack, $needle)?true:false) : (strpos($haystack, $needle) ? true : false);
	}

    public static function POST_CSRF() {
		if ($_SERVER['REQUEST_METHOD']==='POST') {
			$ORIGIN = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : NULL;
			$HOSTNAME = !is_null($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
			if($ORIGIN != NULL && APP::CONTAINS($ORIGIN,$HOSTNAME)) {
				return true;
			}
		}
		return false;
	}

    public static function GET_CSRF() {
		if ($_SERVER['REQUEST_METHOD']==='GET') {
			$REFERER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;
			$HOSTNAME = !is_null($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
			if($REFERER != NULL && APP::CONTAINS($REFERER,$HOSTNAME)) {
				return true;
			}
		}
		return false;
	}

	public static function File2Class($file, $add='') {
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
	
	public static function Class2File($class, $delete='') {
		$class = preg_replace('/[^a-zA-Z0-9]/', '', $class); // Sanitize it
		$class = preg_replace("/^{$delete}/","",$class);
		$class = preg_replace("/([A-Z])/","_$1",$class);
		$class = preg_replace("/^_/","",$class);
		$class = strtolower($class);
		return $class;
	}

	####################
	# PLUGIN SYSTEM

	// get a list of plugins
	// used just for admin.
	static function GET_PLUGINS() {
		$get_plugins = null;
		$directory = new RecursiveDirectoryIterator('app/plugins', RecursiveDirectoryIterator::SKIP_DOTS);
		if (is_null($get_plugins)) {
			$get_plugins = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
		}
		$contains = '.php';
		$plugins = array();
		foreach ($get_plugins as $file_plugin) {
			if (APP::CONTAINS(strtolower($file_plugin), $contains)) {
				if ($file_plugin->isReadable()) {
					include_once $file_plugin->getPathname();
				}
				$plugin = APP::File2Class(str_replace($contains, '', $file_plugin->getFilename()), 'Plugin'); // get the name of the class
				$plugin_info = NULL;
				if (class_exists($plugin)) { // check if the class exists. Maybe the name of the class is different than the name of file.
					$plugin_class = new $plugin(); // execute the class
					//if (is_callable([$plugin_class])) { // check if the class have the method called "plugin" 
						//$register = call_user_func_array([$plugin_class, 'register'], []); // get the register actions and hooks
						$class = new ReflectionClass($plugin_class);
						$doc = $class->getDocComment();
						
						preg_match_all('/@([a-z]+?)\s+(.*?)\n/i', $doc, $info); // https://stackoverflow.com/questions/11461800/how-to-parse-doc-comments
						
						if(isset($info[1]) || count($info[1]) !== 0){
							$plugin_info = array_combine(array_map("trim",$info[1]), array_map("trim",$info[2]));
						}
					//}
				}
				if($plugin_info !== NULL) { // if everything is ok, show the details about the plugin
					$plugins[] = $plugin_info; // show the info about plugin
				}
				// break;
			}
		}
		return $plugins;
	}

	public static function REGISTRY(array $actions = []) {
		if(is_array($actions) && count($actions) > 0) {
			foreach($actions as $action) {
				self::$registry[] = $action;
			}
		}
	}

	static function HOOK($name) {
		$name = strtoupper($name);
		// TODO: move everything into an predefined action in START
		#####################
		if($name == 'START') {
			// if its start, load the pages
			$route = APP::GET(APP::$route);

			$class = self::$home_page;
			if($route != NULL) {
				$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', $route); // clean the route
				$route_exploded = explode('/', $route); // explode it
				$class = APP::File2Class($route_exploded[0], 'Page'); // get the class
			}
			$method = (!empty($route_exploded[1])) ? $route_exploded[1] : 'index'; // get the method

			if (substr($method, 0, 2) == '__') { $method = str_replace('__', '', $method); } // don't let magic methods to be called from url

			$not_found = true;
			if (class_exists($class)) {
				$page_class = new $class();
				if (is_callable([$page_class, $method])) {
					$not_found = false;
					call_user_func_array([$page_class, $method], []); # args
				}
			}
			if($not_found) {
				if (class_exists(self::$error_page)) {
					$page_class = new self::$error_page();
					if (is_callable([$page_class, 'index'])) {
						call_user_func_array([$page_class, 'index'], []); # args
					}
				}
				else {
					exit('Page not found!');
				}
			}
		}
		#####################
		// load and execute plugins for this hook from cache or db
	}

	static function HOOK_TEST($hook) {
		$json = file_get_contents('storage/hooks/'.$hook.'.json');
		$array = json_decode($json);
		foreach($array as $action) {
			list($plugin_name, $plugin_method) = explode('/',$action);
			if (class_exists($plugin_name)) {
				$plugin = new $plugin_name();
				if (is_callable([$plugin, $plugin_method])) {
					call_user_func_array([$plugin, $plugin_method], []); # args
				}
			}
		} 
	}

	static function ADD_ACTION(array $arr) {

	}

    public static function Settings($plugin) {
		$module_id = md5(get_class($plugin)); // maybe add the version too: md5(get_class($plugin).$plugin->version)
		return ['status' => true]; // example
    }
	
	# PLUGIN SYSTEM
	####################

}