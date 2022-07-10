<?php

/**
* @package      DB
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class DB {

    private $connect;
	private $queries = 0;
	
    public function connect(string $host,string $name,string $user,string $pass,string $port,array $options=NULL,string $encoding='utf8') {
		$conn = '';
		if(class_exists('PDO')) {
			try{
				if($options == NULL) {
					$options = [
						\PDO::MYSQL_ATTR_INIT_COMMAND        => "SET NAMES {$encoding}",
						\PDO::ATTR_PERSISTENT                => true, // Long connection
						\PDO::ATTR_EMULATE_PREPARES          => false, // turn off emulation mode for "real" prepared statements
						\PDO::ATTR_DEFAULT_FETCH_MODE        => \PDO::FETCH_ASSOC, //make the default fetch be an associative array
						\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => true,
						\PDO::ATTR_ERRMODE                   => \PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
					];
				}
				else {
					$options = $options;
				}
				$conn = new \PDO("mysql:host={$host};dbname={$name}",$user,$pass,$options);
				$conn -> exec("SET character_set_client='{$encoding}',character_set_connection='{$encoding}',character_set_results='{$encoding}';");
				return $this->connect = $conn;
			}
			catch(PDOException $e) {
				exit($e->getMessage());
			}
		}
		else {
			exit('PDO is not installed on this server.');
		}
	}

    public function query($query, $params=NULL) {
		if(!isSet($query)){ $query=NULL; }
		if(!isSet($params)){ $params=NULL; }
		if($this->connect instanceof PDO) {
			if($query!=NULL) {
				$stmt = $this->connect -> prepare($query);
				if($params != NULL) {
					foreach($params as $param => &$value) {
						
						$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
						
						$stmt -> bindParam($param, $value, $varType);
					}
				}
				$stmt -> execute();
				$this->$queries++;
				return  $stmt; 
			}
		}
		else {
			exit('Is not initiated by PDO.');
		}
	}

	//TODO: do it to show the number of queries per server
	public function queries() {
		return $this->$queries;
	}

}