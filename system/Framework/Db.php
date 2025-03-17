<?php

/**
* @package      DB - PDO Connection
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

use System\Framework\Exceptions\PDOExtensionNotFoundException;
use System\Framework\Exceptions\DatabaseConfigurationException;
use System\Framework\Exceptions\DatabaseConnectionException;
use System\Framework\Exceptions\DatabaseQueryException;

class Db {

	private $queries = 0;
	private $connection;
	private $data = [];
	private $affected;
	
    public function __construct() {

		if (!class_exists('PDO')) {
			throw new PDOExtensionNotFoundException('PDO is not installed on this server.');
		}

		$encoding = (!empty(CONFIG_DB_ENCODING) ? CONFIG_DB_ENCODING : 'utf8');

		$defaultOptions = [
			\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$encoding}; SET time_zone='+03:00';",
			\PDO::ATTR_PERSISTENT => false,
			\PDO::ATTR_EMULATE_PREPARES => false,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // or false if you have a specific need.
		];

		$options = CONFIG_DB_OPTIONS ?? $defaultOptions;

		if(empty(CONFIG_DB_HOSTNAME) && empty(CONFIG_DB_DATABASE) && empty(CONFIG_DB_USERNAME) && empty(CONFIG_DB_PASSWORD) && empty(CONFIG_DB_PORT)) {
			throw new DatabaseConfigurationException('The database login data is not filled in or is filled in incorrectly. Please check the config.');
		}

		try {
			$this->connection = new \PDO("mysql:host=" . CONFIG_DB_HOSTNAME . ";port=" . CONFIG_DB_PORT . ";dbname=" . CONFIG_DB_DATABASE, CONFIG_DB_USERNAME, CONFIG_DB_PASSWORD, $options);
		} catch (\PDOException $e) {
			throw new DatabaseConnectionException($e->getMessage());
		}
	}

	public function query(string $sql, $params = []) {
		if (!is_a($this->connection, 'PDO')) {
			throw new DatabaseConnectionException('Is not initiated by PDO.');
		}
	
		$statement = $this->connection->prepare($sql);
	
		if (!empty($params)) {
			foreach ($params as $param => &$value) {
				$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
				$statement->bindParam($param, $value, $varType);
			}
		}
	
		try {
			if ($statement && $statement->execute()) {
				$this->queries++;
				$result = new \stdClass();
	
				if ($statement->columnCount()) {
					$data = $statement->fetchAll();
					$result->row = isset($data[0]) ? $data[0] : [];
					$result->rows = $data;
					$result->num_rows = count($data);
					$this->affected = 0; // or remove this line.
				} else {
					$this->affected = $statement->rowCount();
					$result->affected_rows = $this->affected;
				}
	
				$statement->closeCursor();
				return $result;
			} else {
				return new \stdClass(); // Return an empty result object on failure.
			}
		} catch (\PDOException $e) {
			throw new DatabaseQueryException('Error: ' . $e->getMessage() . ' <br/>Error Code : ' . $e->getCode() . ' <br/>' . $sql);
		}
	
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