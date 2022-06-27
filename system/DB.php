<?php

/**
* @package      DB
* @version      1.0.0
* @author       YoYoDeveloper
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

// namespace System\Database;

class DB {

    private static $connect = array();

	private static $queries = 0;

    public static function CONNECT(string $servername,string $host,string $name,string $user,string $pass,array $options=NULL,string $encoding='utf8') {
        $servername = strtolower($servername);
        $hash = md5($servername);
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
				return self::$connect[$hash] = $conn;
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

}