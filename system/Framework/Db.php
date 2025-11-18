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
	private $connection = null;
	private $data = [];
	private $affected;
	private $config = [];
	private $isConnected = false;
	
	// Cache properties
	private $cacheEnabled = false;
	private $cacheTtl = 3600;
	private $cacheInstance = null;
	private $cachePrefix = 'db:';
	private $skipCache = false; // For manual cache control

    public function __construct($driver,$db_hostname,$db_database,$db_username,$db_password,$db_port,$encoding,$options) {
		$options = [];
		$encoding = (!empty($encoding) ? $encoding : 'utf8');

		if(empty($db_hostname) && empty($db_database) && empty($db_username) && empty($db_password) && empty($db_port)) {
			throw new \Exception('The database login data is not filled in or is filled in incorrectly. Please check the config.');
		}
		
		// Initialize cache settings from config
		$this->initializeCache();

		if(class_exists('PDO')) {
			try{
				
				if(empty($driver) || $driver === 'mysql') {
					if(empty($options)) {
						$options = [
							\PDO::MYSQL_ATTR_INIT_COMMAND        => "SET NAMES {$encoding}",
							\PDO::ATTR_PERSISTENT                => true, // Long connection
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
	
	/**
	 * Initialize cache settings from config
	 */
	private function initializeCache() {
		if (defined('CONFIG_CACHE_ENABLED')) {
			$this->cacheEnabled = (bool)CONFIG_CACHE_ENABLED;
		}
		if (defined('CONFIG_CACHE_TTL')) {
			$this->cacheTtl = (int)CONFIG_CACHE_TTL;
		}
	}
	
	/**
	 * Get cache instance (lazy loading)
	 */
	private function getCacheInstance() {
		if ($this->cacheInstance === null && class_exists('\System\Framework\Cache')) {
			$this->cacheInstance = new \System\Framework\Cache();
		}
		return $this->cacheInstance;
	}
	
	/**
	 * Generate cache key for a query
	 */
	private function generateCacheKey($sql, $params) {
		$key = $this->cachePrefix . md5($sql . serialize($params));
		return $key;
	}
	
	/**
	 * Manual cache control - skip cache for next query
	 */
	public function noCache() {
		$this->skipCache = true;
		return $this;
	}
	
	/**
	 * Clear cache by pattern
	 */
	public function clearCache($pattern = null) {
		$cache = $this->getCacheInstance();
		if ($cache) {
			if ($pattern) {
				$cache->clear($pattern);
			} else {
				$cache->clear($this->cachePrefix . '*');
			}
		}
		return $this;
	}

	public function query(string $sql, $params=[]) {
		
		// Check if this is a SELECT query and cache is enabled
		$isSelect = stripos(trim($sql), 'SELECT') === 0;
		$shouldCache = $this->cacheEnabled && $isSelect && !$this->skipCache;
		
		// Check if this is a write operation (INSERT, UPDATE, DELETE, TRUNCATE, etc.)
		$sqlUpper = strtoupper(trim($sql));
		$isWriteOperation = (
			stripos($sqlUpper, 'INSERT') === 0 ||
			stripos($sqlUpper, 'UPDATE') === 0 ||
			stripos($sqlUpper, 'DELETE') === 0 ||
			stripos($sqlUpper, 'TRUNCATE') === 0 ||
			stripos($sqlUpper, 'DROP') === 0 ||
			stripos($sqlUpper, 'ALTER') === 0 ||
			stripos($sqlUpper, 'CREATE') === 0
		);
		
		// Reset skipCache flag
		$skipCache = $this->skipCache;
		$this->skipCache = false;
		
		// Try to get from cache if enabled
		if ($shouldCache) {
			$cache = $this->getCacheInstance();
			if ($cache) {
				$cacheKey = $this->generateCacheKey($sql, $params);
				$cached = $cache->get($cacheKey);
				
				if ($cached !== null) {
					// Return cached result
					return $cached;
				}
			}
		}

		if(is_a($this->connection, 'PDO')) {
			$statement = $this->connection->prepare($sql);
			if(!empty($params)) {
				// Check if indexed array (0-based) or associative array
				if (array_keys($params) === range(0, count($params) - 1)) {
					// Indexed array - use 1-based binding for PDO
					foreach($params as $index => $value) {
						$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
						$statement -> bindValue($index + 1, $value, $varType);
					}
				} else {
					// Associative array - use named parameters
					foreach($params as $param => &$value) {
						$varType = ((is_null($value) ? \PDO::PARAM_NULL : is_bool($value)) ? \PDO::PARAM_BOOL : is_int($value)) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
						$statement -> bindParam($param, $value, $varType);
					}
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
						
						// Cache the result if caching is enabled
						if ($shouldCache && isset($cache) && isset($cacheKey)) {
							$cache->set($cacheKey, $result, $this->cacheTtl);
						}
	
						return $result;
					} else {
						$this->affected = $statement->rowCount();
						
						// Clear all cache on write operations (INSERT, UPDATE, DELETE, etc.)
						if ($isWriteOperation && $this->cacheEnabled) {
							$this->clearCache();
						}
	
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
		return $this->connection !== null;
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