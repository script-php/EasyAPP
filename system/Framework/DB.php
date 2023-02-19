<?php

/**
* @package      DB - PDO Connection
* @version      v1.0.1
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class DB {

    private $connect;
	private $queries = 0;
	private $fetchAll;
	private $row;
	private $rows;
	private $count;
	private $time_query = 0;
	private $last_insert_id;
	
    public function __construct(string $host,string $name,string $user,string $pass,string $port,array $options=[],string $encoding='utf8') {
		$conn = '';
		if(class_exists('PDO')) {
			try{
				if(empty($options)) {
					$options = [
						\PDO::MYSQL_ATTR_INIT_COMMAND        => "SET NAMES {$encoding}",
						\PDO::ATTR_PERSISTENT                => true, // Long connection
						\PDO::ATTR_EMULATE_PREPARES          => false, // turn off emulation mode for "real" prepared statements
						\PDO::ATTR_DEFAULT_FETCH_MODE        => \PDO::FETCH_ASSOC, //make the default fetch be an associative array
						\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => true,
						\PDO::ATTR_ERRMODE                   => \PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
					];
				}
				$conn = new \PDO("mysql:host={$host};dbname={$name}",$user,$pass,$options);
				$conn -> exec("SET character_set_client='{$encoding}',character_set_connection='{$encoding}',character_set_results='{$encoding}';");
				$conn -> exec("SET time_zone='+03:00';");
				$this->connect = $conn;
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
		if(is_a($this->connect, 'PDO')) {
			$time_start = microtime(true);
			if($query!=NULL) {
				$stmt = $this->connect -> prepare($query);
				if($params != NULL) {
					foreach($params as $param => &$value) {
						$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
						$stmt -> bindParam($param, $value, $varType);
					}
				}
				$execute = $stmt->execute();
				$this->fetchAll = $stmt->fetchAll();
				$this->count = count($this->fetchAll) ? $stmt->rowCount() : 0;
				$this->queries++;
				$this->time_query = microtime(true) - $time_start;
				$this->last_insert_id = $this->connect->lastInsertId();
				return  $execute; 
			}
		}
		else {
			exit('Is not initiated by PDO.');
		}
	}

	public function row() {
		return !empty($this->fetchAll[0]) ? $this->fetchAll[0] : [];
	}

	public function rows() {
		return $this->fetchAll;
	}

	public function count() {
		return $this->count;
	}

	public function time() {
		return $this->time_query;
	}

	public function queries() {
		return $this->queries;
	}

	public function lastId() {
		return $this->connect->lastInsertId();
	}

}