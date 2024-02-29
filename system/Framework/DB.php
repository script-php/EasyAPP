<?php

/**
* @package      DB - PDO Connection
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Db {

	private $queries = 0;
	private $connection;
	private $data = [];
	private $affected;
	
    public function __construct() {
		$port = CONFIG_DB_PORT;
		$options = CONFIG_DB_OPTIONS;
		$encoding = (!empty(CONFIG_DB_ENCODING) ? CONFIG_DB_ENCODING : 'utf8');

		if(empty(CONFIG_DB_HOSTNAME) && empty(CONFIG_DB_DATABASE) && empty(CONFIG_DB_USERNAME) && empty(CONFIG_DB_PASSWORD) && empty(CONFIG_DB_PORT)) {
			exit('The database login data is not filled in or is filled in incorrectly. Please check the config.');
		}

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
				$conn = new \PDO("mysql:host=".CONFIG_DB_HOSTNAME.";port=".CONFIG_DB_PORT.";dbname=".CONFIG_DB_DATABASE."",CONFIG_DB_USERNAME,CONFIG_DB_PASSWORD,$options);
				$conn -> exec("SET character_set_client='{$encoding}',character_set_connection='{$encoding}',character_set_results='{$encoding}';");
				$conn -> exec("SET time_zone='+03:00';");
				$this->connection = $conn;
			}
			catch(PDOException $e) {
				exit($e->getMessage());
			}
		}
		else {
			exit('PDO is not installed on this server.');
		}
	}

	public function query(string $sql, $params=[]) {

		if(is_a($this->connection, 'PDO')) {
			$statement = $this->connection->prepare($sql);
			if(!empty($params)) {
				foreach($params as $param => &$value) {
					$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
					$statement -> bindParam($param, $value, $varType);
				}
			}

			try {
				if ($statement && $statement->execute()) {
					$this->queries++;
					if ($statement->columnCount()) {

						$data = $statement->fetchAll();

						$result = new \stdClass();
						$result->row = isset($data[0]) ? $data[0] : [];
						$result->rows = $data;
						$result->num_rows = count($data);
						$this->affected = 0;
	
						return $result;
					} else {
						$this->affected = $statement->rowCount();
	
						return true;
					}
	
					$statement->closeCursor();
				} else {
					return true;
				}
			} catch (\PDOException $e) {
				throw new \Exception('Error: ' . $e->getMessage() . ' <br/>Error Code : ' . $e->getCode() . ' <br/>' . $sql);
			}

		}
		else {
			exit('Is not initiated by PDO.');
		}

		return FALSE;
	}

	public function countAffected(): int {
		return $this->affected;
	}

	public function getLastId(): int {
		return $this->connection->lastInsertId();
	}

	public function isConnected(): bool {
		if ($this->connection) {
			return true;
		} else {
			return false;
		}
	}

	public function queries() {
		return $this->queries;
	}

	public function __destruct() {
		unset($this->connection);
	}

}