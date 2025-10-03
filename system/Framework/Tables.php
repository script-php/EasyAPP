<?php 

/**
* @package      DB - Tables
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

use System\Framework\Exceptions\FrameworkException;

/**
 * Enhanced MySQL Tables Schema Management
 * 
 * This class provides a fluent, optimized interface for managing database schemas
 * with proper error handling, validation, and performance optimizations.
 */
class Tables {
    
    // Operation types for better tracking
    const OPERATION_CREATE = 'create';
    const OPERATION_ALTER = 'alter';
    const OPERATION_DROP = 'drop';
    
    // MySQL engines
    const ENGINE_INNODB = 'InnoDB';
    const ENGINE_MYISAM = 'MyISAM';
    const ENGINE_MEMORY = 'MEMORY';

    const DEFAULT_ENGINE = 'InnoDB';
    const DEFAULT_CHARSET = 'utf8mb4';
    const DEFAULT_COLLATE = 'utf8mb4_unicode_ci';

    // Table definitions
    public $tables = [];
    private $tableCache = [];
    private $shouldExecute = true;
    
    // Database and registry
    private $db;
    private $util;
    protected $registry;
    
    // Current context tracking
    private $currentTable = '';
    private $currentColumn = '';
    
    // Execution tracking
    private $executedQueries = [];
    private $errors = [];
    private $debug = false;
    
    // Performance optimization
    private $batchQueries = [];
    private $useBatch = false;

	/**
	 * Initialize Tables manager with optimized dependencies
	 */
	public function __construct($registry) {
		$this->registry = $registry;
		
		// Initialize dependencies with error checking
		try {
			$this->db = $this->registry->get('db');
			$this->util = $this->registry->get('util');
		} catch (\Exception $e) {
			throw new FrameworkException('Failed to initialize Tables: ' . $e->getMessage());
		}
		
		// Set debug mode from config
		$this->debug = defined('CONFIG_DEBUG') && CONFIG_DEBUG;
	}

    /**
     * Create or update database tables with proper validation and transaction management
     * 
     * @param array $tables Optional tables array to merge with current tables
     * @return bool Success status
     * @throws FrameworkException On validation or execution failure
     */
    public function create(array $tables = []): bool {
        try {
            // Merge provided tables with existing ones
            $allTables = array_merge($this->tables, $tables);
            
            if (empty($allTables)) {
                if ($this->debug) {
                    $this->log('No tables to create or update');
                }
                return true;
            }
            
            // Check if schema changes are needed
            if (!$this->shouldExecuteChanges($allTables)) {
                if ($this->debug) {
                    $this->log('No schema changes detected, skipping execution');
                }
                return true;
            }
            
            // Execute within transaction
            return $this->executeWithTransaction(function() use ($allTables) {
                // Validate all tables before execution
                $this->validateTables($allTables);
                
                // Execute schema changes
                $this->executeTables($allTables);
                
                // Update change tracking
                $this->updateChangeHash($allTables);
                
                return true;
            });
            
        } catch (\Exception $e) {
            $error = 'Schema creation failed: ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }


    function actions($tables) {

        try {

            foreach ($tables as $table) {
                $foreign_query = $this->db->query("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
                foreach ($foreign_query->rows as $foreign) {
                    $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP FOREIGN KEY `" . $foreign['CONSTRAINT_NAME'] . "`");
                    pre("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP FOREIGN KEY `" . $foreign['CONSTRAINT_NAME'] . "`");
                }
            }

            foreach ($tables as $table) {
                $table_query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . CONFIG_DB_DATABASE . "' AND TABLE_NAME = '" . CONFIG_DB_PREFIX . $table['name'] . "'");
                if (!$table_query->num_rows) {
                    $this->createTable($table);
                } else {
                    $this->alterTable($table);
                }
            }

            foreach ($tables as $table) {
                if (isset($table['foreign'])) {
                    foreach ($table['foreign'] as $foreign) {

                        $cascade = ($foreign['cascade'] ? " ON DELETE CASCADE " : "");

                        $this->db->query("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FOREIGN KEY (`" . $foreign['key'] . "`) REFERENCES `" . CONFIG_DB_PREFIX . $foreign['table'] . "` (`" . $foreign['column'] . "`)" . $cascade);

                        pre("ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD FOREIGN KEY (`" . $foreign['key'] . "`) REFERENCES `" . CONFIG_DB_PREFIX . $foreign['table'] . "` (`" . $foreign['column'] . "`)" . $cascade);
                    }
                }
            }

        } catch (\ErrorException $exception) {
            exit($exception);
        }
    }



    /**
     * Execute operation within database transaction
     * 
     * @param callable $operation Operation to execute
     * @return mixed Operation result
     * @throws FrameworkException On transaction failure
     */
    private function executeWithTransaction(callable $operation) {
        try {
            $this->db->query('START TRANSACTION');
            $result = $operation();
            $this->db->query('COMMIT');
            return $result;
        } catch (\Exception $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }
    }
    
    /**
     * Check if schema changes should be executed based on diff
     * 
     * @param array $tables Tables to check
     * @return bool Whether changes should be executed
     */
    private function shouldExecuteChanges(array $tables): bool {
        $debug_backtrace = debug_backtrace();
        $file = $this->util->hash($debug_backtrace[1]['file'] ?? 'unknown');
        $hash = $this->util->hash(json_encode($tables, JSON_SORT_KEYS));
        
        $hashFile = CONFIG_DIR_STORAGE . 'logs/tables-' . $file;
        
        if (is_file($hashFile)) {
            $storedHash = file_get_contents($hashFile);
            return $hash !== $storedHash;
        }
        
        return true;
    }
    
    /**
     * Update the change tracking hash
     * 
     * @param array $tables Tables that were processed
     */
    private function updateChangeHash(array $tables): void {
        $debug_backtrace = debug_backtrace();
        $file = $this->util->hash($debug_backtrace[1]['file'] ?? 'unknown');
        $hash = $this->util->hash(json_encode($tables, JSON_SORT_KEYS));
        
        $hashFile = CONFIG_DIR_STORAGE . 'logs/tables-' . $file;
        file_put_contents($hashFile, $hash);
    }
    
    /**
     * Check if table exists in database
     * 
     * @param string $tableName Table name (without prefix)
     * @return bool Whether table exists
     */
    private function tableExists(string $tableName): bool {
        if (isset($this->tableCache[$tableName])) {
            return $this->tableCache[$tableName];
        }
        
        $sql = "SELECT 1 FROM information_schema.tables 
               WHERE table_schema = ? AND table_name = ? LIMIT 1";
               
        $query = $this->db->query($sql, [CONFIG_DB_DATABASE, CONFIG_DB_PREFIX . $tableName]);
        $exists = $query->num_rows > 0;
        
        $this->tableCache[$tableName] = $exists;
        return $exists;
    }
    
    /**
     * Log SQL query for debugging
     * 
     * @param string $sql SQL query
     */
    private function logQuery(string $sql): void {
        if ($this->debug) {
            $this->executedQueries[] = $sql;
            error_log('[Tables] SQL: ' . $sql);
        }
    }
    
    /**
     * Log informational message
     * 
     * @param string $message Message to log
     */
    private function log(string $message): void {
        if ($this->debug) {
            error_log('[Tables] ' . $message);
        }
    }
    
    /**
     * Log error message
     * 
     * @param string $error Error message
     */
    private function logError(string $error): void {
        error_log('[Tables ERROR] ' . $error);
        $this->errors[] = $error;
    }
    
    /**
     * Comprehensive validation of all tables before execution
     * 
     * @param array $tables Tables to validate
     * @throws FrameworkException On validation failure
     */
    private function validateTables(array $tables): void {
        $tablesByName = [];
        
        // Index tables by name for efficient lookups
        foreach ($tables as $table) {
            if (!isset($table['name']) || empty($table['name'])) {
                throw new FrameworkException('Table name is required');
            }
            $tablesByName[$table['name']] = $table;
        }
        
        // Validate each table structure
        foreach ($tables as $table) {
            $this->validateTableStructure($table);
            $this->validateForeignKeys($table, $tablesByName);
        }
    }
    
    /**
     * Validate individual table structure
     * 
     * @param array $table Table configuration
     * @throws FrameworkException On validation failure
     */
    private function validateTableStructure(array $table): void {
        // Validate table name
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $table['name'])) {
            throw new FrameworkException('Invalid table name: ' . $table['name']);
        }
        
        // Validate columns
        if (!isset($table['column']) || !is_array($table['column'])) {
            throw new FrameworkException('Table must have columns: ' . $table['name']);
        }
        
        foreach ($table['column'] as $column) {
            if (!isset($column['name']) || !isset($column['type'])) {
                throw new FrameworkException('Column must have name and type in table: ' . $table['name']);
            }
            
            // Validate column name
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $column['name'])) {
                throw new FrameworkException('Invalid column name: ' . $column['name']);
            }
        }
    }
    /**
     * Build column definition string for SQL
     * 
     * @param array $column Column configuration
     * @return string Column definition
     */
    private function buildColumnDefinition(array $column): string {
        $name = isset($column['change']) && !empty($column['change']) ? $column['change'] : $column['name'];
        
        $definition = "`{$name}` {$column['type']}";
        
        // Add NOT NULL constraint
        if (!empty($column['not_null'])) {
            $definition .= ' NOT NULL';
        }
        
        // Add DEFAULT value
        if (isset($column['default'])) {
            $definition .= ' DEFAULT ' . $column['default'];
        }
        
        // Add AUTO_INCREMENT (only if NOT NULL)
        if (!empty($column['auto_increment'])) {
            $definition .= ' AUTO_INCREMENT';
        }
        
        return $definition;
    }
    
    # CREATE TABLE
    /**
     * Create new table with optimized SQL generation
     * 
     * @param array $table Table configuration
     * @throws FrameworkException On creation failure
     */
    function createTable($table) {
        try {
            $tableName = CONFIG_DB_PREFIX . $table['name'];
            $columns = [];
            
            // Build column definitions
            foreach ($table['column'] as $column) {
                if (!isset($column['delete']) || !$column['delete']) {
                    $columns[] = '  ' . $this->buildColumnDefinition($column);
                }
            }
            
            $sql = "CREATE TABLE `{$tableName}` (\n" . implode(",\n", $columns);
            
            // Add primary key
            if (isset($table['primary'])) {
                $sql .= ",\n  PRIMARY KEY ({$table['primary']})";
            }
            
            // Add fulltext indexes
            if (isset($table['fulltext'])) {
                foreach ($table['fulltext'] as $fulltext) {
                    if (is_array($fulltext)) {
                        $columns = implode('`, `', $fulltext);
                        $sql .= ",\n  FULLTEXT INDEX (`{$columns})";
                    } else {
                        $sql .= ",\n  FULLTEXT INDEX (`{$fulltext})";
                    }
                }
            }
            
            // Add regular indexes
            if (isset($table['index'])) {
                foreach ($table['index'] as $index) {
                    $columns = implode('`, `', $index['key']);
                    $sql .= ",\n  KEY `{$index['name']}` (`{$columns})";
                }
            }
            
            // Add table options
            $engine = $table['engine'] ?? self::DEFAULT_ENGINE;
            $charset = $table['charset'] ?? self::DEFAULT_CHARSET;
            $collate = $table['collate'] ?? self::DEFAULT_COLLATE;
            
            $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};";
            
            $this->db->query($sql);
            $this->logQuery($sql);
            
        } catch (\Exception $e) {
            throw new FrameworkException('Table creation failed for ' . $table['name'] . ': ' . $e->getMessage());
        }
    }


    # ALTER TABLE
    /**
     * Alter existing table structure with optimized approach
     * 
     * @param array $table Table configuration
     * @throws FrameworkException On alteration failure
     */
    function alterTable($table) {
        try {
            $tableName = CONFIG_DB_PREFIX . $table['name'];
            
            // Step 1: Alter columns
            $this->alterTableColumns($table);
            
            // Step 2: Drop and recreate indexes (more efficient than individual changes)
            $this->recreateTableIndexes($table);
            
            // Step 3: Update table properties (engine, charset)
            $this->updateTableProperties($table);
            
        } catch (\Exception $e) {
            throw new FrameworkException('Table alteration failed for ' . $table['name'] . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Alter table columns with proper validation
     * 
     * @param array $table Table configuration
     */
    private function alterTableColumns(array $table): void {
        $tableName = CONFIG_DB_PREFIX . $table['name'];
        
        foreach ($table['column'] as $column) {
            $this->alterSingleColumn($tableName, $column);
        }
    }
    
    /**
     * Alter a single column with proper SQL generation
     * 
     * @param string $tableName Full table name with prefix
     * @param array $column Column configuration
     */
    private function alterSingleColumn(string $tableName, array $column): void {
        // Check if column exists
        $columnExists = $this->columnExists($tableName, $column['name']);
        
        if (isset($column['delete']) && $column['delete']) {
            if ($columnExists) {
                $sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$column['name']}`";
                $this->db->query($sql);
                $this->logQuery($sql);
            }
            return;
        }
        
        // Build column definition
        $columnDef = $this->buildColumnDefinition($column);
        
        // Determine operation type
        if (!$columnExists) {
            $sql = "ALTER TABLE `{$tableName}` ADD COLUMN {$columnDef}";
        } elseif (isset($column['change']) && !empty($column['change'])) {
            $sql = "ALTER TABLE `{$tableName}` CHANGE `{$column['name']}` `{$column['change']}` {$columnDef}";
        } else {
            $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN {$columnDef}";
        }
        
        // Add positioning
        if (isset($column['after'])) {
            $sql .= " AFTER `{$column['after']}`";
        } elseif (isset($column['first']) && $column['first']) {
            $sql .= " FIRST";
        }
        
        $this->db->query($sql);
        $this->logQuery($sql);
    }
    
    /**
     * Check if column exists in table
     * 
     * @param string $tableName Full table name
     * @param string $columnName Column name
     * @return bool Whether column exists
     */
    private function columnExists(string $tableName, string $columnName): bool {
        $sql = "SELECT 1 FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
               
        $query = $this->db->query($sql, [CONFIG_DB_DATABASE, $tableName, $columnName]);
        return $query->num_rows > 0;
    }
    
    /**
     * Recreate table indexes efficiently
     * 
     * @param array $table Table configuration
     */
    private function recreateTableIndexes(array $table): void {
        $tableName = CONFIG_DB_PREFIX . $table['name'];
        
        // Drop all existing indexes first (more efficient)
        $this->dropAllTableIndexes($tableName);
        
        // Add new indexes
        $this->addTableIndexes($table);
    }
    
    /**
     * Drop all indexes from table
     * 
     * @param string $tableName Full table name
     */
    private function dropAllTableIndexes(string $tableName): void {
        $sql = "SELECT DISTINCT INDEX_NAME, INDEX_TYPE FROM information_schema.STATISTICS 
               WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME != 'PRIMARY'";
               
        $query = $this->db->query($sql, [CONFIG_DB_DATABASE, $tableName]);
        
        foreach ($query->rows as $index) {
            if ($index['INDEX_TYPE'] === 'FULLTEXT') {
                $dropSql = "ALTER TABLE `{$tableName}` DROP INDEX `{$index['INDEX_NAME']}`";
            } else {
                $dropSql = "ALTER TABLE `{$tableName}` DROP INDEX `{$index['INDEX_NAME']}`";
            }
            $this->db->query($dropSql);
            $this->logQuery($dropSql);
        }
        
        // Drop primary key if it needs to be recreated
        $primarySql = "SELECT 1 FROM information_schema.STATISTICS 
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY' LIMIT 1";
                     
        $primaryQuery = $this->db->query($primarySql, [CONFIG_DB_DATABASE, $tableName]);
        
        if ($primaryQuery->num_rows > 0) {
            // Remove AUTO_INCREMENT first if exists
            $this->removeAutoIncrementFromPrimaryKey($tableName);
            
            $dropPrimarySql = "ALTER TABLE `{$tableName}` DROP PRIMARY KEY";
            $this->db->query($dropPrimarySql);
            $this->logQuery($dropPrimarySql);
        }
    }
    
    /**
     * Remove AUTO_INCREMENT from primary key columns
     * 
     * @param string $tableName Full table name
     */
    private function removeAutoIncrementFromPrimaryKey(string $tableName): void {
        $sql = "SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS 
               WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = 'PRI' AND EXTRA LIKE '%auto_increment%'";
               
        $query = $this->db->query($sql, [CONFIG_DB_DATABASE, $tableName]);
        
        foreach ($query->rows as $column) {
            $modifySql = "ALTER TABLE `{$tableName}` MODIFY `{$column['COLUMN_NAME']}` {$column['COLUMN_TYPE']} NOT NULL";
            $this->db->query($modifySql);
            $this->logQuery($modifySql);
        }
    }
    
    /**
     * Add indexes to table
     * 
     * @param array $table Table configuration
     */
    private function addTableIndexes(array $table): void {
        $tableName = CONFIG_DB_PREFIX . $table['name'];
        
        // Add primary key
        if (isset($table['primary'])) {
            $sql = "ALTER TABLE `{$tableName}` ADD PRIMARY KEY ({$table['primary']})";
            $this->db->query($sql);
            $this->logQuery($sql);
        }
        
        // Add fulltext indexes
        if (isset($table['fulltext'])) {
            foreach ($table['fulltext'] as $fulltext) {
                if (is_array($fulltext)) {
                    $columns = implode('`, `', $fulltext);
                    $sql = "ALTER TABLE `{$tableName}` ADD FULLTEXT INDEX (`{$columns}`)";
                } else {
                    $sql = "ALTER TABLE `{$tableName}` ADD FULLTEXT INDEX (`{$fulltext}`)";
                }
                $this->db->query($sql);
                $this->logQuery($sql);
            }
        }
        
        // Add regular indexes
        if (isset($table['index'])) {
            foreach ($table['index'] as $index) {
                $columns = implode('`, `', $index['key']);
                $sql = "ALTER TABLE `{$tableName}` ADD INDEX `{$index['name']}` (`{$columns}`)";
                $this->db->query($sql);
                $this->logQuery($sql);
            }
        }
        
        // Re-add AUTO_INCREMENT to columns that need it
        $this->addAutoIncrementToColumns($table);
    }
    
    /**
     * Add AUTO_INCREMENT to columns that need it
     * 
     * @param array $table Table configuration
     */
    private function addAutoIncrementToColumns(array $table): void {
        $tableName = CONFIG_DB_PREFIX . $table['name'];
        
        foreach ($table['column'] as $column) {
            if (isset($column['auto_increment']) && $column['auto_increment']) {
                $sql = "ALTER TABLE `{$tableName}` MODIFY `{$column['name']}` {$column['type']} AUTO_INCREMENT";
                $this->db->query($sql);
                $this->logQuery($sql);
            }
        }
    }
    
    /**
     * Update table properties (engine, charset, collation)
     * 
     * @param array $table Table configuration
     */
    private function updateTableProperties(array $table): void {
        $tableName = CONFIG_DB_PREFIX . $table['name'];
        
        // Update engine
        if (isset($table['engine'])) {
            $sql = "ALTER TABLE `{$tableName}` ENGINE = {$table['engine']}";
            $this->db->query($sql);
            $this->logQuery($sql);
        }
        
        // Update charset and collation
        if (isset($table['charset'])) {
            $sql = "ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET {$table['charset']}";
            
            if (isset($table['collate'])) {
                $sql .= " COLLATE {$table['collate']}";
            }
            
            $this->db->query($sql);
            $this->logQuery($sql);
        }
    }

    function table($table) {
		$this->table_use = $table;
		$this->tables[$this->table_use] = [
			'name' => $this->table_use,
			'column' => [],
			'engine' => 'default InnoDB',
			'charset' => 'default utf8mb4',
			'collate' => 'default utf8mb4_unicode_ci',
		];
		return $this;
	}
	
	function column($name, string $change = NULL) {
		$this->column_use = $name;
		$this->tables[$this->table_use]['column'][$this->column_use]['name'] = $name;
        if(!empty($change)) {
            $this->tables[$this->table_use]['column'][$this->column_use]['change'] = $change;
        }
		return $this;
	}
	
	function type($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['type'] = $str;
		return $this;
	}

    function after($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['after'] = $str;
		return $this;
	}
	
	function auto_increment($bool) {
		$this->tables[$this->table_use]['column'][$this->column_use]['auto_increment'] = $bool;
		return $this;
	}

    function default($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['default'] = $str;
		return $this;
	}

    function primary($str) {
        // $this->tables[$this->table_use]['primary'] = [$str];
        $this->tables[$this->table_use]['primary'] = $str;
		return $this;
	}

    function fulltext($str = []) {
        // $this->tables[$this->table_use]['primary'] = [$str];
        $this->tables[$this->table_use]['fulltext'] = $str;
		return $this;
	}

    function foreign($key, $table, $column, bool $cascade = false) {
        $this->tables[$this->table_use]['foreign'][] = [
            'key' => $key,
            'table' => $table,
            'column' => $column,
            'cascade' => $cascade
        ];
		return $this;
	}
	
	function not_null($bool) {
		$this->tables[$this->table_use]['column'][$this->column_use]['not_null'] = $bool;
		return $this;
	}
	
	function engine($str) {
		$this->tables[$this->table_use]['engine'] = $str;
		return $this;
	}
	
	function charset($str) {
		$this->tables[$this->table_use]['charset'] = $str;
		return $this;
	}
	
	function collate($str) {
		$this->tables[$this->table_use]['collate'] = $str;
		return $this;
	}

    function delete() {
        $this->tables[$this->table_use]['column'][$this->column_use]['delete'] = true;
        return $this;
    }

    function index(string $name, array $key = []) {
        $this->tables[$this->table_use]['index'][] = [
            'name' => $name,
            'key' => $key,
        ];
		return $this;
    }

    // Modern column types and enhanced API methods
    
    /**
     * Set column type to JSON (MySQL 5.7+)
     * 
     * @return $this
     */
    public function json() {
        return $this->type('JSON');
    }
    
    /**
     * Set column type to UUID (stored as CHAR(36))
     * 
     * @return $this
     */
    public function uuid() {
        return $this->type('CHAR(36)');
    }
    
    /**
     * Set column type to ENUM with validation
     * 
     * @param array $values Allowed enum values
     * @return $this
     * @throws FrameworkException If no values provided
     */
    public function enum(array $values) {
        if (empty($values)) {
            throw new FrameworkException('ENUM column must have at least one value');
        }
        
        $enumValues = implode("','", array_map('addslashes', $values));
        return $this->type("ENUM('{$enumValues}')");
    }
    
    /**
     * Set column type to DECIMAL with precision
     * 
     * @param int $precision Total number of digits
     * @param int $scale Number of digits after decimal point
     * @return $this
     */
    public function decimal(int $precision = 10, int $scale = 2) {
        return $this->type("DECIMAL({$precision},{$scale})");
    }
    
    /**
     * Set column type to TIMESTAMP with proper default handling
     * 
     * @param bool $currentTimestamp Whether to default to CURRENT_TIMESTAMP
     * @return $this
     */
    public function timestamp(bool $currentTimestamp = false) {
        $this->type('TIMESTAMP');
        
        if ($currentTimestamp) {
            $this->default('CURRENT_TIMESTAMP');
        }
        
        return $this;
    }
    
    /**
     * Set column type to TEXT with size variant
     * 
     * @param string $size Size variant: 'tiny', 'medium', 'long', or empty for regular TEXT
     * @return $this
     */
    public function text(string $size = '') {
        $sizeMap = [
            'tiny' => 'TINYTEXT',
            'medium' => 'MEDIUMTEXT', 
            'long' => 'LONGTEXT'
        ];
        
        $type = $sizeMap[strtolower($size)] ?? 'TEXT';
        
        return $this->type($type);
    }
    
    /**
     * Set column to UNSIGNED (for numeric types)
     * 
     * @return $this
     */
    public function unsigned() {
        $currentType = $this->tables[$this->table_use]['column'][$this->column_use]['type'] ?? '';
        if (!empty($currentType)) {
            $this->tables[$this->table_use]['column'][$this->column_use]['type'] = $currentType . ' UNSIGNED';
        }
        return $this;
    }
    
    /**
     * Add column comment
     * 
     * @param string $comment Column comment
     * @return $this
     */
    public function comment(string $comment) {
        $this->tables[$this->table_use]['column'][$this->column_use]['comment'] = $comment;
        return $this;
    }
    
    /**
     * Set column as nullable (alias for not_null(false))
     * 
     * @param bool $nullable Whether column should be nullable
     * @return $this
     */
    public function nullable(bool $nullable = true) {
        return $this->not_null(!$nullable);
    }
    
    /**
     * Set column to auto increment with default parameters
     * 
     * @param bool $autoIncrement Whether to auto increment
     * @return $this
     */
    public function autoIncrement(bool $autoIncrement = true) {
        return $this->auto_increment($autoIncrement);
    }
    
    /**
     * Set column as first in table
     * 
     * @return $this
     */
    public function first() {
        $this->tables[$this->table_use]['column'][$this->column_use]['first'] = true;
        return $this;
    }
    
    /**
     * Add UNIQUE constraint to single column
     * 
     * @param string|null $indexName Optional custom index name
     * @return $this
     */
    public function unique(?string $indexName = null) {
        $indexName = $indexName ?: 'unique_' . $this->column_use;
        
        $this->tables[$this->table_use]['unique'][] = [
            'name' => $indexName,
            'columns' => [$this->column_use]
        ];
        
        return $this;
    }
    
    /**
     * Add composite UNIQUE constraint
     * 
     * @param array $columns Columns for unique constraint
     * @param string|null $indexName Optional custom index name
     * @return $this
     */
    public function uniqueComposite(array $columns, ?string $indexName = null) {
        $indexName = $indexName ?: 'unique_' . implode('_', $columns);
        
        $this->tables[$this->table_use]['unique'][] = [
            'name' => $indexName,
            'columns' => $columns
        ];
        
        return $this;
    }
    
    /**
     * Create spatial index (for GIS data)
     * 
     * @param string $column Column name
     * @param string|null $indexName Optional custom index name
     * @return $this
     */
    public function spatial(string $column, ?string $indexName = null) {
        $indexName = $indexName ?: 'spatial_' . $column;
        
        $this->tables[$this->table_use]['spatial'][] = [
            'name' => $indexName,
            'column' => $column
        ];
        
        return $this;
    }
    
    // Schema introspection methods
    
    /**
     * Check if table exists
     * 
     * @param string $tableName Table name (without prefix)
     * @return bool Whether table exists
     */
    public function exists(string $tableName): bool {
        return $this->tableExists($tableName);
    }
    
    /**
     * Get table column information
     * 
     * @param string $tableName Table name (without prefix)
     * @return array Column information
     */
    public function describe(string $tableName): array {
        $sql = "DESCRIBE `" . CONFIG_DB_PREFIX . $tableName . "`";
        $query = $this->db->query($sql);
        return $query->rows ?? [];
    }
    
    /**
     * Get table indexes
     * 
     * @param string $tableName Table name (without prefix)
     * @return array Index information
     */
    public function getIndexes(string $tableName): array {
        $sql = "SHOW INDEXES FROM `" . CONFIG_DB_PREFIX . $tableName . "`";
        $query = $this->db->query($sql);
        return $query->rows ?? [];
    }
    
    /**
     * Get executed queries for debugging
     * 
     * @return array Executed SQL queries
     */
    public function getExecutedQueries(): array {
        return $this->executedQueries;
    }
    
    /**
     * Get any errors that occurred
     * 
     * @return array Error messages
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Enable/disable debug mode
     * 
     * @param bool $debug Debug mode
     * @return $this
     */
    public function debug(bool $debug = true) {
        $this->debug = $debug;
        return $this;
    }

}