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

    public function __construct($driver,$db_hostname,$db_database,$db_username,$db_password,$db_port,$encoding,$options) {
		$options = [];
		$encoding = (!empty($encoding) ? $encoding : 'utf8');

		if(empty($db_hostname) && empty($db_database) && empty($db_username) && empty($db_password) && empty($db_port)) {
			throw new \Exception('The database login data is not filled in or is filled in incorrectly. Please check the config.');
		}

		if(class_exists('PDO')) {
			try{
				
				if(empty($driver) || $driver === 'mysql') {
					if(empty($options)) {
						$options = [
							\PDO::MYSQL_ATTR_INIT_COMMAND        => "SET NAMES {$encoding}",
							\PDO::ATTR_PERSISTENT                => false, // Long connection
							\PDO::ATTR_EMULATE_PREPARES          => false, // turn off emulation mode for "real" prepared statements
							\PDO::ATTR_DEFAULT_FETCH_MODE        => \PDO::FETCH_ASSOC, //make the default fetch be an associative array
							\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => false,
							\PDO::ATTR_ERRMODE                   => \PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
						];
					}
					$conn = new \PDO("mysql:host={$db_hostname};port={$db_port};dbname={$db_database}",$db_username,$db_password,$options);
					$conn -> exec("SET character_set_client='{$encoding}',character_set_connection='{$encoding}',character_set_results='{$encoding}';");
					$conn -> exec("SET time_zone='+03:00';");
				}
				else if($driver === 'sqlsrv') {
					if(empty($options)) {
						$options = [
							\PDO::ATTR_EMULATE_PREPARES          => false, // turn off emulation mode for "real" prepared statements
							\PDO::ATTR_DEFAULT_FETCH_MODE        => \PDO::FETCH_ASSOC, //make the default fetch be an associative array
							\PDO::ATTR_ERRMODE                   => \PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
						];
					}
					$conn = new \PDO("sqlsrv:Server={$db_hostname},{$db_port};TrustServerCertificate=true;Database={$db_database}", $db_username, $db_password,$options);
				}
				$this->connection = $conn;
			}
			catch(PDOException $e) {
				throw new \Exception($e->getMessage());
			}
		}
		else {
			throw new \Exception('PDO is not installed on this server.');
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
			throw new \Exception('Is not initiated by PDO.');
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

	public function beginTransaction() {
		return $this->connection->beginTransaction();
	}

	public function commit() {
		return $this->connection->commit();
	}

	public function rollBack() {
		return $this->connection->rollBack();
	}

	public function queries() {
		return $this->queries;
	}

	public function __destruct() {
		unset($this->connection);
	}

}