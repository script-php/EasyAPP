<?php 

/**
* @package      DB - Tables
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

use System\Framework\Exceptions\DatabaseQuery as FrameworkException;

/**
 * Enhanced MySQL Tables Schema Management
 * 
 * This class provides a fluent, optimized interface for managing database schemas
 * with proper error handling, validation, and performance optimizations.
 * 
 * =============================================================================
 * VALIDATION FEATURES
 * =============================================================================
 * 
 * • Table/Column Names: Validates against MySQL reserved keywords and enforces max 64 char limit
 * • Column Positioning: Validates that target columns exist before applying AFTER/FIRST clauses
 * • Foreign Keys: Checks type compatibility and validates constraint names
 * • Pre-flight Checks: All operations validated before SQL execution in transactions
 * 
 * =============================================================================
 * COMPLETE METHOD REFERENCE
 * =============================================================================
 * 
 * CORE METHODS:
 * -------------
 * __construct($registry)           - Initialize Tables manager with registry dependencies
 * create(array $tables = [])       - Create or update database tables with transaction support
 * 
 * FLUENT API - TABLE STRUCTURE:
 * -----------------------------
 * table($name)                     - Start defining a table schema
 * column($name, $change = null)    - Define a column (optionally rename existing)
 * type($str)                       - Set column data type (VARCHAR, INT, etc.)
 * after($str)                      - Position column after another column
 * default($str)                    - Set default value for column
 * primary($str)                    - Define primary key constraint
 * 
 * COLUMN CONSTRAINTS:
 * ------------------
 * autoIncrement($bool = true)      - Make column auto-incrementing (camelCase)
 * auto_increment($bool = true)     - Legacy alias for autoIncrement()
 * notNull($bool = true)            - Make column required/not nullable (camelCase)
 * not_null($bool = true)          - Legacy alias for notNull()
 * nullable($bool = true)           - Make column nullable (opposite of notNull)
 * unique($indexName = null)        - Add unique constraint to single column
 * comment($comment)                - Add descriptive comment to column
 * 
 * MODERN COLUMN TYPES:
 * -------------------
 * json()                           - JSON column type (MySQL 5.7+)
 * uuid()                           - UUID column (CHAR(36))
 * enum(array $values)              - ENUM with validation
 * decimal($precision = 10, $scale = 2) - DECIMAL with precision control
 * timestamp($currentTimestamp = false) - TIMESTAMP with optional auto-default
 * date()                           - DATE column type
 * datetime()                       - DATETIME column type
 * boolean()                        - BOOLEAN (stored as TINYINT(1))
 * geometry()                       - GEOMETRY spatial data type
 * point()                          - POINT spatial data type
 * polygon()                        - POLYGON spatial data type
 * text($size = '')                 - TEXT with size variants (tiny, medium, long)
 * unsigned()                       - Add UNSIGNED modifier to numeric types
 * onUpdate($value)                 - Set ON UPDATE clause (for timestamps)
 * 
 * INDEXES AND CONSTRAINTS:
 * -----------------------
 * index($name, array $key = [])    - Create regular index on columns
 * uniqueComposite(array $columns, $indexName = null) - Multi-column unique constraint
 * fulltext($columns = [])          - Create fulltext search index
 * spatial($column, $indexName = null) - Create spatial index for GIS data
 * foreign($constraintName = null, $key, $table, $column, $onDelete = false, $onUpdate = false) - Define foreign key with CASCADE options
 * 
 * TABLE PROPERTIES:
 * ----------------
 * engine($str)                     - Set storage engine (InnoDB, MyISAM, MEMORY)
 * charset($str)                    - Set character set (utf8mb4, latin1, etc.)
 * collate($str)                    - Set collation (utf8mb4_unicode_ci, etc.)
 * tableComment($str)               - Set table comment/description
 * 
 * COLUMN OPERATIONS:
 * -----------------
 * delete()                         - Mark column for deletion
 * first()                          - Position column as first in table
 * after($str)                      - Position column after another column (validates target exists)
 * 
 * SCHEMA INTROSPECTION:
 * --------------------
 * exists($tableName)               - Check if table exists in database
 * describe($tableName)             - Get detailed column information for table
 * getIndexes($tableName)           - Get all indexes for specified table
 * 
 * UTILITY AND DEBUG:
 * -----------------
 * debug($bool = true)              - Enable/disable debug logging
 * getExecutedQueries()             - Get array of all executed SQL queries
 * getErrors()                      - Get array of any errors that occurred
 * getTables()                      - Get current table configuration (for testing)
 * clearTables()                    - Clear all table definitions (for testing)
 * getCurrentTable()                - Get name of currently active table
 * getCurrentColumn()               - Get name of currently active column
 * 
 * USAGE EXAMPLES:
 * --------------
 * 
 * Basic Table Creation:
 * $tables->table('users')
 *     ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
 *     ->column('name')->type('VARCHAR(100)')->notNull(true)
 *     ->column('email')->type('VARCHAR(100)')->unique()
 *     ->create();
 * 
 * Modern Column Types:
 * $tables->table('products')
 *     ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
 *     ->column('data')->json()
 *     ->column('price')->decimal(10, 2)
 *     ->column('status')->enum(['active', 'inactive'])
 *     ->column('created_at')->timestamp(true)
 *     ->create();
 * 
 * Foreign Key Relations:
 * $tables->table('orders')
 *     ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
 *     ->column('user_id')->type('INT(11)')->notNull(true)
 *     ->foreign('fk_orders_users', 'user_id', 'users', 'id', true, true)  // CASCADE on delete and update
 *     ->create();
 * 
 * Complex Indexes:
 * $tables->table('posts')
 *     ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
 *     ->column('title')->type('VARCHAR(200)')->notNull(true)
 *     ->column('content')->text('long')
 *     ->index('idx_title', ['title'])
 *     ->fulltext(['title', 'content'])
 *     ->create();
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
    private $table_use = '';
    private $column_use = '';
    
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
                
                // Compute diff-based changes and execute only what's needed
                $this->executeDiffBasedChanges($allTables);
                
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


    private function executeTables(array $tables): void {

        try {

            foreach ($tables as $table) {
                $sql = "SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
                $foreign_query = $this->db->query($sql, [CONFIG_DB_DATABASE, CONFIG_DB_PREFIX . $table['name']]);
                foreach ($foreign_query->rows as $foreign) {
                    $dropSql = "ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` DROP FOREIGN KEY `" . $foreign['CONSTRAINT_NAME'] . "`";
                    $this->db->query($dropSql);
                    $this->logQuery($dropSql);
                }
            }

            foreach ($tables as $table) {
                $sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                $table_query = $this->db->query($sql, [CONFIG_DB_DATABASE, CONFIG_DB_PREFIX . $table['name']]);
                if (!$table_query->num_rows) {
                    $this->createTable($table);
                } else {
                    $this->alterTable($table);
                }
            }

            foreach ($tables as $table) {
                if (isset($table['foreign'])) {
                    foreach ($table['foreign'] as $foreign) {

                        $onDelete = ($foreign['onDelete'] ? " ON DELETE CASCADE" : "");
                        $onUpdate = ($foreign['onUpdate'] ? " ON UPDATE CASCADE" : "");
                        $constraintName = $foreign['name'] ?? 'fk_' . $table['name'] . '_' . $foreign['table'] . '_' . $foreign['key'];

                        $addForeignKeySql = "ALTER TABLE `" . CONFIG_DB_PREFIX . $table['name'] . "` ADD CONSTRAINT `" . $constraintName . "` FOREIGN KEY (`" . $foreign['key'] . "`) REFERENCES `" . CONFIG_DB_PREFIX . $foreign['table'] . "` (`" . $foreign['column'] . "`)" . $onDelete . $onUpdate;
                        $this->db->query($addForeignKeySql);
                        $this->logQuery($addForeignKeySql);
                    }
                }
            }

        } catch (\Exception $exception) {
            $this->logError('Failed to execute tables: ' . $exception->getMessage());
            throw new FrameworkException('Database operation failed: ' . $exception->getMessage());
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
        $hash = $this->util->hash(json_encode($tables, JSON_UNESCAPED_UNICODE));
        
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
        $hash = $this->util->hash(json_encode($tables, JSON_UNESCAPED_UNICODE));
        
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
        if (!isset($table['name']) || empty($table['name'])) {
            throw new FrameworkException('Table name is required');
        }
        
        $this->validateTableName($table['name']);
        
        // Validate columns
        if (!isset($table['column']) || !is_array($table['column'])) {
            throw new FrameworkException('Table must have columns: ' . $table['name']);
        }
        
        foreach ($table['column'] as $column) {
            if (!isset($column['name']) || !isset($column['type'])) {
                throw new FrameworkException('Column must have name and type in table: ' . $table['name']);
            }
            
            // Validate column name
            $this->validateColumnName($column['name']);
        }
    }
    
    /**
     * Validate foreign key relationships
     * 
     * @param array $table Table configuration
     * @param array $tablesByName All tables indexed by name
     * @throws FrameworkException On validation failure
     */
    private function validateForeignKeys(array $table, array $tablesByName): void {
        if (!isset($table['foreign']) || !is_array($table['foreign'])) {
            return;
        }
        
        foreach ($table['foreign'] as $foreign) {
            // Validate foreign key structure
            if (!isset($foreign['key']) || !isset($foreign['table']) || !isset($foreign['column'])) {
                throw new FrameworkException('Foreign key must have key, table, and column properties in table: ' . $table['name']);
            }
            
            // Check if referenced table exists in the schema
            if (!isset($tablesByName[$foreign['table']])) {
                throw new FrameworkException("Foreign key references non-existent table: {$foreign['table']} from table: {$table['name']}");
            }
            
            $referencedTable = $tablesByName[$foreign['table']];
            
            // Check if local column exists
            if (!isset($table['column'][$foreign['key']])) {
                throw new FrameworkException("Foreign key column does not exist: {$foreign['key']} in table {$table['name']}");
            }
            
            // Check if referenced column exists
            if (!isset($referencedTable['column'][$foreign['column']])) {
                throw new FrameworkException("Foreign key references non-existent column: {$foreign['column']} in table {$foreign['table']}");
            }
            
            // Validate constraint name if provided
            if (isset($foreign['name']) && !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $foreign['name'])) {
                throw new FrameworkException("Invalid foreign key constraint name: {$foreign['name']} in table {$table['name']}");
            }
            
            // Check type compatibility (basic check)
            $localType = $this->normalizeType($table['column'][$foreign['key']]['type']);
            $referencedType = $this->normalizeType($referencedTable['column'][$foreign['column']]['type']);
            
            if ($localType !== $referencedType) {
                throw new FrameworkException("Foreign key type mismatch: {$foreign['key']} ({$localType}) does not match {$foreign['table']}.{$foreign['column']} ({$referencedType})");
            }
        }
    }
    
    /**
     * Validate table name against MySQL restrictions and reserved keywords
     * 
     * @param string $tableName Table name to validate
     * @throws FrameworkException If validation fails
     */
    private function validateTableName(string $tableName): void {
        // Check length (max 64 characters in MySQL)
        if (strlen($tableName) > 64) {
            throw new FrameworkException("Table name exceeds maximum length of 64 characters: {$tableName}");
        }
        
        // Check for valid naming pattern
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new FrameworkException("Invalid table name format: {$tableName}. Must start with letter or underscore and contain only alphanumeric characters and underscores.");
        }
        
        // Common MySQL reserved keywords that should be avoided
        $reservedKeywords = [
            'ACCESSIBLE', 'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC', 'ASENSITIVE',
            'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE',
            'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION',
            'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME',
            'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE', 'DATABASES', 'DAY_HOUR',
            'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT',
            'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW',
            'DIV', 'DOUBLE', 'DROP', 'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED',
            'EXISTS', 'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR',
            'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GENERAL', 'GRANT', 'GROUP', 'HAVING',
            'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE',
            'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1',
            'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IO_AFTER_GTIDS',
            'IO_BEFORE_GTIDS', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LEADING', 'LEAVE',
            'LEFT', 'LIKE', 'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP',
            'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY', 'MASTER_BIND',
            'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH', 'MAXVALUE', 'MEDIUMBLOB', 'MEDIUMINT',
            'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD', 'MODIFIES',
            'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'ONE_SHOT', 'OR',
            'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'OPTION', 'OPTIMIZATION', 'OPTIMIZE', 'OPTIONALLY',
            'PARTITION', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'RANGE', 'READ', 'READS',
            'READ_WRITE', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REPLACE',
            'REQUIRE', 'RESIGNAL', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE', 'SCHEMA',
            'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 'SHOW',
            'SIGNAL', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING',
            'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING',
            'STRAIGHT_JOIN', 'TABLE', 'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT',
            'TO', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED',
            'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES',
            'VARBINARY', 'VARCHAR', 'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH',
            'WRITE', 'X509', 'XOR', 'YEAR_MONTH', 'ZEROFILL'
        ];
        
        if (in_array(strtoupper($tableName), $reservedKeywords, true)) {
            throw new FrameworkException("Table name '{$tableName}' is a MySQL reserved keyword. Please use a different name.");
        }
    }
    
    /**
     * Validate column name against MySQL restrictions and reserved keywords
     * 
     * @param string $columnName Column name to validate
     * @throws FrameworkException If validation fails
     */
    private function validateColumnName(string $columnName): void {
        // Check length (max 64 characters in MySQL)
        if (strlen($columnName) > 64) {
            throw new FrameworkException("Column name exceeds maximum length of 64 characters: {$columnName}");
        }
        
        // Check for valid naming pattern
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $columnName)) {
            throw new FrameworkException("Invalid column name format: {$columnName}. Must start with letter or underscore and contain only alphanumeric characters and underscores.");
        }
        
        // Most problematic reserved keywords that should be avoided in column names
        $restrictedKeywords = [
            'SELECT', 'FROM', 'WHERE', 'ORDER', 'GROUP', 'HAVING', 'LIMIT', 'OFFSET', 'JOIN',
            'LEFT', 'RIGHT', 'INNER', 'OUTER', 'ON', 'AND', 'OR', 'NOT', 'IN', 'EXISTS',
            'BETWEEN', 'LIKE', 'IS', 'NULL', 'TRUE', 'FALSE', 'DEFAULT', 'KEY', 'PRIMARY',
            'FOREIGN', 'CONSTRAINT', 'INDEX', 'UNIQUE', 'CHECK', 'ACTION', 'CASCADE',
            'RESTRICT', 'SET', 'TABLE', 'COLUMN', 'CREATE', 'ALTER', 'DROP', 'DELETE',
            'INSERT', 'UPDATE', 'VALUES', 'INTO', 'REFERENCES', 'ADD', 'MODIFY', 'CHANGE'
        ];
        
        if (in_array(strtoupper($columnName), $restrictedKeywords, true)) {
            throw new FrameworkException("Column name '{$columnName}' is a MySQL reserved keyword. Please use a different name.");
        }
    }
    
    /**
     * Normalize MySQL type for comparison
     * 
     * Handles various MySQL type representations and normalizes them for FK validation.
     * Examples:
     *   'INT(11)' -> 'INT'
     *   'VARCHAR(255)' -> 'VARCHAR'
     *   'BIGINT(20) UNSIGNED' -> 'BIGINT'
     *   'BOOLEAN' -> 'TINYINT'
     * 
     * @param string $type MySQL column type
     * @return string Normalized type
     */
    private function normalizeType(string $type): string {
        // Trim whitespace and convert to uppercase
        $type = strtoupper(trim($type));
        
        // Remove size specifications (e.g., INT(11), VARCHAR(255))
        $type = preg_replace('/\([^)]*\)/', '', $type);
        
        // Remove modifiers like UNSIGNED, ZEROFILL, etc.
        $type = preg_replace('/(UNSIGNED|ZEROFILL|SIGNED|BINARY)\s*/i', '', $type);
        
        // Clean up any remaining extra whitespace
        $type = trim(preg_replace('/\s+/', ' ', $type));
        
        // Comprehensive type mapping for synonyms and aliases
        $typeMap = [
            // Integer types
            'INTEGER' => 'INT',
            'TINYINT' => 'TINYINT',
            'SMALLINT' => 'SMALLINT',
            'MEDIUMINT' => 'MEDIUMINT',
            'INT' => 'INT',
            'BIGINT' => 'BIGINT',
            
            // Decimal types
            'NUMERIC' => 'DECIMAL',
            'FIXED' => 'DECIMAL',
            'DEC' => 'DECIMAL',
            'DECIMAL' => 'DECIMAL',
            
            // Floating point types
            'FLOAT' => 'FLOAT',
            'DOUBLE' => 'DOUBLE',
            'REAL' => 'DOUBLE',
            'DOUBLE PRECISION' => 'DOUBLE',
            
            // Boolean type
            'BOOLEAN' => 'TINYINT',
            'BOOL' => 'TINYINT',
            
            // String types
            'CHAR' => 'CHAR',
            'VARCHAR' => 'VARCHAR',
            'BINARY' => 'BINARY',
            'VARBINARY' => 'VARBINARY',
            
            // Text types
            'TINYTEXT' => 'TINYTEXT',
            'TEXT' => 'TEXT',
            'MEDIUMTEXT' => 'MEDIUMTEXT',
            'LONGTEXT' => 'LONGTEXT',
            
            // Blob types
            'TINYBLOB' => 'TINYBLOB',
            'BLOB' => 'BLOB',
            'MEDIUMBLOB' => 'MEDIUMBLOB',
            'LONGBLOB' => 'LONGBLOB',
            
            // Date/Time types
            'DATE' => 'DATE',
            'TIME' => 'TIME',
            'DATETIME' => 'DATETIME',
            'TIMESTAMP' => 'TIMESTAMP',
            'YEAR' => 'YEAR',
            
            // Spatial types
            'GEOMETRY' => 'GEOMETRY',
            'POINT' => 'POINT',
            'LINESTRING' => 'LINESTRING',
            'POLYGON' => 'POLYGON',
            'MULTIPOINT' => 'MULTIPOINT',
            'MULTILINESTRING' => 'MULTILINESTRING',
            'MULTIPOLYGON' => 'MULTIPOLYGON',
            'GEOMETRYCOLLECTION' => 'GEOMETRYCOLLECTION',
            
            // JSON type
            'JSON' => 'JSON',
            
            // Enum and Set
            'ENUM' => 'ENUM',
            'SET' => 'SET'
        ];
        
        return $typeMap[$type] ?? $type;
    }
    /**
     * Set table comment
     * 
     * @param string $comment Table comment/description
     * @return $this
     */
    public function tableComment(string $comment) {
        $this->tables[$this->table_use]['table_comment'] = $comment;
        return $this;
    }
    
    /**
     * Build column definition string for SQL
     * 
     * @param array $column Column configuration
     * @param bool $includePositioning Whether to include AFTER/FIRST clauses (only for ALTER, not CREATE)
     * @return string Column definition
     */
    private function buildColumnDefinition(array $column, bool $includePositioning = false): string {
        // Get column name (handle CHANGE COLUMN if specified)
        $name = isset($column['change']) && !empty($column['change']) ? $column['change'] : $column['name'];
        
        // Remove backticks if present (normalize)
        $name = trim($name, '`');
        
        $definition = "`{$name}` {$column['type']}";
        
        // Add NOT NULL constraint
        if (!empty($column['not_null'])) {
            $definition .= ' NOT NULL';
        }
        
        // Add DEFAULT value
        if (isset($column['default'])) {
            $defaultValue = $column['default'];
            
            // Handle boolean values
            if (is_bool($defaultValue)) {
                $definition .= ' DEFAULT ' . ($defaultValue ? '1' : '0');
            }
            // Don't quote SQL functions and NULL
            else if (in_array(strtoupper($defaultValue), ['CURRENT_TIMESTAMP', 'NOW()', 'NULL']) || 
                     preg_match('/^[0-9]+(\.[0-9]+)?$/', $defaultValue) || // Numbers
                     strtolower($defaultValue) === 'true' || 
                     strtolower($defaultValue) === 'false') {
                $definition .= ' DEFAULT ' . $defaultValue;
            } else {
                // Quote string values
                $definition .= " DEFAULT '" . addslashes($defaultValue) . "'";
            }
        }
        
        // Add AUTO_INCREMENT (only if NOT NULL)
        if (!empty($column['auto_increment'])) {
            $definition .= ' AUTO_INCREMENT';
        }
        
        // Add ON UPDATE clause
        if (!empty($column['on_update'])) {
            $definition .= ' ON UPDATE ' . $column['on_update'];
        }
        
        // Add column comment
        if (!empty($column['comment'])) {
            $comment = addslashes($column['comment']);
            $definition .= " COMMENT '{$comment}'";
        }
        
        // Add positioning ONLY if explicitly requested (for ALTER TABLE ADD COLUMN)
        if ($includePositioning) {
            if (isset($column['after'])) {
                $definition .= " AFTER `{$column['after']}`";
            } elseif (isset($column['first']) && $column['first']) {
                $definition .= " FIRST";
            }
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
    private function createTable(array $table): void {
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
                        $sql .= ",\n  FULLTEXT INDEX (`{$columns}`)";
                    } else {
                        $sql .= ",\n  FULLTEXT INDEX (`{$fulltext}`)";
                    }
                }
            }
            
            // Add regular indexes
            if (isset($table['index'])) {
                foreach ($table['index'] as $index) {
                    $columns = '`' . implode('`, `', $index['key']) . '`';
                    $sql .= ",\n  KEY `{$index['name']}` ({$columns})";
                }
            }
            
            // Add unique indexes
            if (isset($table['unique'])) {
                foreach ($table['unique'] as $unique) {
                    $columns = '`' . implode('`, `', $unique['columns']) . '`';
                    $sql .= ",\n  UNIQUE KEY `{$unique['name']}` ({$columns})";
                }
            }
            
            // Add spatial indexes
            if (isset($table['spatial'])) {
                foreach ($table['spatial'] as $spatial) {
                    $sql .= ",\n  SPATIAL INDEX `{$spatial['name']}` (`{$spatial['column']})";
                }
            }
            
            // Add table options
            $engine = $table['engine'] ?? self::DEFAULT_ENGINE;
            $charset = $table['charset'] ?? self::DEFAULT_CHARSET;
            $collate = $table['collate'] ?? self::DEFAULT_COLLATE;
            
            $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate}";
            
            // Add table comment if provided
            if (!empty($table['table_comment'])) {
                $comment = addslashes($table['table_comment']);
                $sql .= " COMMENT '{$comment}'";
            }
            
            $sql .= ";";
            
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
    private function alterTable(array $table): void {
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
        
        // Get existing columns from database for positioning validation
        $tableNameOnly = str_replace(CONFIG_DB_PREFIX, '', $tableName);
        $sql = "SELECT COLUMN_NAME as name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION";
        $result = $this->db->query($sql, [CONFIG_DB_DATABASE, $tableNameOnly]);
        $existingColumns = $result->rows ?? [];
        
        // Merge with new columns from configuration
        $allColumns = array_merge($existingColumns, array_map(function($col) {
            return ['name' => $col['name']];
        }, array_filter($table['column'], function($col) {
            return !isset($col['delete']) || !$col['delete'];
        })));
        
        foreach ($table['column'] as $column) {
            $this->alterSingleColumn($tableName, $column, $allColumns);
        }
    }
    
    /**
     * Alter a single column with proper SQL generation
     * 
     * @param string $tableName Full table name with prefix
     * @param array $column Column configuration
     * @param array $allColumns All columns in the table for validation
     */
    private function alterSingleColumn(string $tableName, array $column, array $allColumns = []): void {
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
        
        // Determine if positioning should be included (only for ADD and CHANGE operations)
        $usePositioning = !$columnExists || (isset($column['change']) && !empty($column['change']));
        
        // Build column definition with positioning only when valid
        $columnDef = $this->buildColumnDefinition($column, $usePositioning);
        
        // Determine operation type
        if (!$columnExists) {
            $sql = "ALTER TABLE `{$tableName}` ADD COLUMN {$columnDef}";
        } elseif (isset($column['change']) && !empty($column['change'])) {
            $sql = "ALTER TABLE `{$tableName}` CHANGE `{$column['name']}` `{$column['change']}` {$columnDef}";
        } else {
            $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN {$columnDef}";
        }
        
        // Validate positioning if it's being used
        if ((isset($column['after']) || (isset($column['first']) && $column['first'])) && $usePositioning) {
            if (isset($column['after'])) {
                $this->validateColumnPositioning($tableName, $column['after'], 'after', $allColumns);
            }
        }
        
        // Log BEFORE executing so we can see what failed
        $this->logQuery($sql);
        $this->db->query($sql);
    }
    
    /**
     * Validate column positioning (AFTER/FIRST clauses)
     * 
     * @param string $tableName Full table name with prefix
     * @param string $targetColumn The column to position after/before
     * @param string $type The positioning type ('after' or 'first')
     * @param array $allColumns All columns in table for validation
     * @throws FrameworkException If target column doesn't exist
     */
    private function validateColumnPositioning(string $tableName, string $targetColumn, string $type = 'after', array $allColumns = []): void {
        // If we have allColumns array, check against it
        if (!empty($allColumns)) {
            $columnNames = array_column($allColumns, 'name');
            if (!in_array($targetColumn, $columnNames)) {
                throw new FrameworkException("Column positioning failed: Target column '{$targetColumn}' does not exist in {$type} clause for table " . str_replace(CONFIG_DB_PREFIX, '', $tableName));
            }
            return;
        }
        
        // Otherwise, query the database to verify column exists
        if (!$this->columnExists($tableName, $targetColumn)) {
            throw new FrameworkException("Column positioning failed: Target column '{$targetColumn}' does not exist in {$type} clause for table " . str_replace(CONFIG_DB_PREFIX, '', $tableName));
        }
    }
    
    /**
     * Check if column exists in table
     * 
     * @param string $tableName Full table name with prefix
     * @param string $columnName Column name
     * @return bool Whether column exists
     */
    private function columnExists(string $tableName, string $columnName): bool {
        // Remove prefix for information_schema query
        $tableNameOnly = str_replace(CONFIG_DB_PREFIX, '', $tableName);
        
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        $query = $this->db->query($sql, [CONFIG_DB_DATABASE, $tableNameOnly, $columnName]);
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
            
            // Temporarily disable foreign key checks to allow primary key drop
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            
            $dropPrimarySql = "ALTER TABLE `{$tableName}` DROP PRIMARY KEY";
            $this->db->query($dropPrimarySql);
            $this->logQuery($dropPrimarySql);
            
            // Re-enable foreign key checks
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
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
     * Update table properties (engine, charset, collation, comment)
     * 
     * @param array $table Table configuration
     */
    private function updateTableProperties(array $table): void {
        $tableName = CONFIG_DB_PREFIX . $table['name'];
        
        // Collect all ALTER TABLE modifications
        $alterStatements = [];
        
        // Update engine
        if (isset($table['engine'])) {
            $alterStatements[] = "ENGINE = {$table['engine']}";
        }
        
        // Update charset and collation
        if (isset($table['charset'])) {
            $charset = $table['charset'];
            $collate = isset($table['collate']) ? " COLLATE {$table['collate']}" : '';
            $alterStatements[] = "CONVERT TO CHARACTER SET {$charset}{$collate}";
        }
        
        // Update table comment
        if (!empty($table['table_comment'])) {
            $comment = addslashes($table['table_comment']);
            $alterStatements[] = "COMMENT '{$comment}'";
        }
        
        // Execute all ALTER statements at once (more efficient)
        if (!empty($alterStatements)) {
            $sql = "ALTER TABLE `{$tableName}` " . implode(", ", $alterStatements);
            $this->db->query($sql);
            $this->logQuery($sql);
        }
    }

    public function table($table) {
		$this->table_use = $table;
		$this->tables[$this->table_use] = [
			'name' => $this->table_use,
			'column' => [],
			'engine' => 'InnoDB',
			'charset' => 'utf8mb4',
			'collate' => 'utf8mb4_unicode_ci',
		];
		return $this;
	}
	
	public function column($name, string $change = null) {
		$this->column_use = $name;
		$this->tables[$this->table_use]['column'][$this->column_use]['name'] = $name;
        if (!empty($change)) {
            $this->tables[$this->table_use]['column'][$this->column_use]['change'] = $change;
        }
		return $this;
	}
	
	public function type($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['type'] = $str;
		return $this;
	}

    public function after($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['after'] = $str;
		return $this;
	}
	
	public function autoIncrement($bool = true) {
		$this->tables[$this->table_use]['column'][$this->column_use]['auto_increment'] = $bool;
		return $this;
	}

    // Legacy method for backward compatibility
    public function auto_increment($bool = true) {
        return $this->autoIncrement($bool);
    }

    public function default($str) {
		$this->tables[$this->table_use]['column'][$this->column_use]['default'] = $str;
		return $this;
	}

    public function primary($str) {
        $this->tables[$this->table_use]['primary'] = $str;
		return $this;
	}

    public function fulltext($str = []) {
        $this->tables[$this->table_use]['fulltext'] = $str;
		return $this;
	}

    public function foreign(?string $constraintName, $key, $table, $column, bool $onDelete = false, bool $onUpdate = false) {
        // Auto-generate constraint name if not provided
        // Format: fk_<local_table>_<foreign_table>_<column>
        $name = $constraintName ?: 'fk_' . $this->table_use . '_' . $table . '_' . $key;
        
        $this->tables[$this->table_use]['foreign'][] = [
            'key' => $key,
            'table' => $table,
            'column' => $column,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate,
            'name' => $name
        ];
		return $this;
	}
	
	public function notNull($bool = true) {
		$this->tables[$this->table_use]['column'][$this->column_use]['not_null'] = $bool;
		return $this;
	}

    // Legacy method for backward compatibility
    public function not_null($bool = true) {
        return $this->notNull($bool);
    }
	
	public function engine($str) {
		$this->tables[$this->table_use]['engine'] = $str;
		return $this;
	}
	
	public function charset($str) {
		$this->tables[$this->table_use]['charset'] = $str;
		return $this;
	}
	
	public function collate($str) {
		$this->tables[$this->table_use]['collate'] = $str;
		return $this;
	}

    public function delete() {
        $this->tables[$this->table_use]['column'][$this->column_use]['delete'] = true;
        return $this;
    }

    public function index(string $name, array $key = []) {
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
     * Set column type to DATE
     * 
     * @return $this
     */
    public function date() {
        return $this->type('DATE');
    }
    
    /**
     * Set column type to DATETIME
     * 
     * @return $this
     */
    public function datetime() {
        return $this->type('DATETIME');
    }
    
    /**
     * Set column type to BOOLEAN (stored as TINYINT(1))
     * 
     * @return $this
     */
    public function boolean() {
        return $this->type('TINYINT(1)');
    }
    
    /**
     * Set column type to GEOMETRY
     * 
     * @return $this
     */
    public function geometry() {
        return $this->type('GEOMETRY');
    }
    
    /**
     * Set column type to POINT (spatial data)
     * 
     * @return $this
     */
    public function point() {
        return $this->type('POINT');
    }
    
    /**
     * Set column type to POLYGON (spatial data)
     * 
     * @return $this
     */
    public function polygon() {
        return $this->type('POLYGON');
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
     * Set ON UPDATE clause for timestamp columns
     * 
     * @param string $value ON UPDATE value (e.g., 'CURRENT_TIMESTAMP')
     * @return $this
     */
    public function onUpdate(string $value) {
        $this->tables[$this->table_use]['column'][$this->column_use]['on_update'] = $value;
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
    
    /**
     * Get current tables configuration (for testing)
     * 
     * @return array Tables configuration
     */
    public function getTables(): array {
        return array_values($this->tables);
    }
    
    /**
     * Clear all tables (for testing)
     * 
     * @return $this
     */
    public function clearTables() {
        $this->tables = [];
        $this->table_use = '';
        $this->column_use = '';
        return $this;
    }
    
    /**
     * Get current table being used
     * 
     * @return string Current table name
     */
    public function getCurrentTable(): string {
        return $this->table_use;
    }
    
    /**
     * Get current column being used
     * 
     * @return string Current column name
     */
    public function getCurrentColumn(): string {
        return $this->column_use;
    }
    
    /**
     * Drop table from database
     * 
     * @param string|null $tableName Table name to drop (uses current table if null)
     * @param bool $ifExists Add IF EXISTS clause to prevent errors
     * @return $this
     * @throws FrameworkException On drop failure
     */
    public function drop(?string $tableName = null, bool $ifExists = true): self {
        try {
            $table = $tableName ?: $this->table_use;
            
            if (empty($table)) {
                throw new FrameworkException('No table specified for drop operation');
            }
            
            $fullTableName = CONFIG_DB_PREFIX . $table;
            $ifExistsClause = $ifExists ? 'IF EXISTS ' : '';
            
            $sql = "DROP TABLE {$ifExistsClause}`{$fullTableName}`";
            
            $this->db->query($sql);
            $this->logQuery($sql);
            
            // Clear from cache if exists
            if (isset($this->tableCache[$table])) {
                unset($this->tableCache[$table]);
            }
            
            if ($this->debug) {
                $this->log("Dropped table: {$table}");
            }
            
            return $this;
            
        } catch (\Exception $e) {
            $error = 'Table drop failed for ' . ($tableName ?: $this->table_use) . ': ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Drop multiple tables in dependency order
     * 
     * @param array $tableNames Array of table names to drop
     * @param bool $ifExists Add IF EXISTS clause to prevent errors
     * @return $this
     * @throws FrameworkException On drop failure
     */
    public function dropTables(array $tableNames, bool $ifExists = true): self {
        try {
            // First, drop all foreign key constraints to avoid dependency issues
            foreach ($tableNames as $tableName) {
                $this->dropForeignKeys($tableName);
            }
            
            // Then drop all tables
            foreach ($tableNames as $tableName) {
                $this->drop($tableName, $ifExists);
            }
            
            return $this;
            
        } catch (\Exception $e) {
            $error = 'Bulk table drop failed: ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Drop all foreign keys from a table
     * 
     * @param string $tableName Table name (without prefix)
     * @return $this
     */
    public function dropForeignKeys(string $tableName): self {
        try {
            $fullTableName = CONFIG_DB_PREFIX . $tableName;
            
            // Get all foreign keys for this table
            $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                   WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
                   
            $result = $this->db->query($sql, [CONFIG_DB_DATABASE, $fullTableName]);
            
            // Drop each foreign key
            foreach ($result->rows as $row) {
                $dropSql = "ALTER TABLE `{$fullTableName}` DROP FOREIGN KEY `{$row['CONSTRAINT_NAME']}`";
                $this->db->query($dropSql);
                $this->logQuery($dropSql);
            }
            
            return $this;
            
        } catch (\Exception $e) {
            $this->logError('Failed to drop foreign keys for table ' . $tableName . ': ' . $e->getMessage());
            return $this;
        }
    }
    
    /**
     * Truncate table (remove all data but keep structure)
     * 
     * @param string|null $tableName Table name to truncate (uses current table if null)
     * @return $this
     * @throws FrameworkException On truncate failure
     */
    public function truncate(?string $tableName = null): self {
        try {
            $table = $tableName ?: $this->table_use;
            
            if (empty($table)) {
                throw new FrameworkException('No table specified for truncate operation');
            }
            
            $fullTableName = CONFIG_DB_PREFIX . $table;
            
            // Disable foreign key checks temporarily
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            
            $sql = "TRUNCATE TABLE `{$fullTableName}`";
            $this->db->query($sql);
            $this->logQuery($sql);
            
            // Re-enable foreign key checks
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            
            if ($this->debug) {
                $this->log("Truncated table: {$table}");
            }
            
            return $this;
            
        } catch (\Exception $e) {
            // Make sure to re-enable foreign key checks even on error
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
            
            $error = 'Table truncate failed for ' . ($tableName ?: $this->table_use) . ': ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Rename table
     * 
     * @param string $newName New table name
     * @param string|null $oldName Old table name (uses current table if null)
     * @return $this
     * @throws FrameworkException On rename failure
     */
    public function renameTable(string $newName, ?string $oldName = null): self {
        try {
            $oldTable = $oldName ?: $this->table_use;
            
            if (empty($oldTable)) {
                throw new FrameworkException('No table specified for rename operation');
            }
            
            $oldFullName = CONFIG_DB_PREFIX . $oldTable;
            $newFullName = CONFIG_DB_PREFIX . $newName;
            
            $sql = "RENAME TABLE `{$oldFullName}` TO `{$newFullName}`";
            $this->db->query($sql);
            $this->logQuery($sql);
            
            // Update cache
            if (isset($this->tableCache[$oldTable])) {
                $this->tableCache[$newName] = $this->tableCache[$oldTable];
                unset($this->tableCache[$oldTable]);
            }
            
            // Update current table reference if renamed current table
            if ($this->table_use === $oldTable) {
                $this->table_use = $newName;
            }
            
            if ($this->debug) {
                $this->log("Renamed table: {$oldTable} -> {$newName}");
            }
            
            return $this;
            
        } catch (\Exception $e) {
            $error = 'Table rename failed: ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Copy table structure (and optionally data)
     * 
     * @param string $newTableName New table name
     * @param string|null $sourceTableName Source table name (uses current table if null)
     * @param bool $copyData Whether to copy data as well
     * @return $this
     * @throws FrameworkException On copy failure
     */
    public function copyTable(string $newTableName, ?string $sourceTableName = null, bool $copyData = false): self {
        try {
            $sourceTable = $sourceTableName ?: $this->table_use;
            
            if (empty($sourceTable)) {
                throw new FrameworkException('No source table specified for copy operation');
            }
            
            $sourceFullName = CONFIG_DB_PREFIX . $sourceTable;
            $newFullName = CONFIG_DB_PREFIX . $newTableName;
            
            // Create table structure
            $sql = "CREATE TABLE `{$newFullName}` LIKE `{$sourceFullName}`";
            $this->db->query($sql);
            $this->logQuery($sql);
            
            // Copy data if requested
            if ($copyData) {
                $dataSql = "INSERT INTO `{$newFullName}` SELECT * FROM `{$sourceFullName}`";
                $this->db->query($dataSql);
                $this->logQuery($dataSql);
            }
            
            if ($this->debug) {
                $copyType = $copyData ? 'with data' : 'structure only';
                $this->log("Copied table {$copyType}: {$sourceTable} -> {$newTableName}");
            }
            
            return $this;
            
        } catch (\Exception $e) {
            $error = 'Table copy failed: ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }

    /**
     * Execute schema changes based on computed differences between desired and existing schemas
     * Only modifies tables/columns/indexes that have actual changes
     * 
     * @param array $tables Desired table definitions
     * @return void
     * @throws FrameworkException On execution failure
     */
    private function executeDiffBasedChanges(array $tables): void {
        try {
            // Clear table cache to get fresh state of what exists in DB
            $this->tableCache = [];
            
            $dbPrefix = CONFIG_DB_PREFIX;
            $dbName = CONFIG_DB_DATABASE;
            
            // Step 1: Identify and drop changed foreign keys
            $this->dropChangedForeignKeys($tables, $dbPrefix, $dbName);
            
            // Step 2: Process each table - create new or alter existing
            foreach ($tables as $table) {
                $tableName = $dbPrefix . $table['name'];
                
                // Check if table exists in database
                if ($this->tableExists($table['name'])) {
                    // Get existing schema
                    $existingSchema = $this->getTableSchema($table['name']);
                    
                    // Compute differences
                    $diff = $this->computeTableDiff($existingSchema, $table);
                    
                    if (!empty($diff['changes'])) {
                        if ($this->debug) {
                            $this->log("Table '{$table['name']}' has changes: " . json_encode($diff['changes']));
                        }
                        
                        // Apply only the changes detected
                        $this->applyTableChanges($table['name'], $diff);
                    } else {
                        if ($this->debug) {
                            $this->log("Table '{$table['name']}' has no changes, skipping");
                        }
                    }
                } else {
                    // Table doesn't exist - create it
                    if ($this->debug) {
                        $this->log("Creating new table '{$table['name']}'");
                    }
                    $this->createTable($table);
                }
            }
            
            // Step 3: Re-add foreign keys with proper CASCADE options
            $this->addForeignKeys($tables, $dbPrefix);
            
        } catch (\Exception $exception) {
            $this->logError('Failed to execute diff-based changes: ' . $exception->getMessage());
            throw new FrameworkException('Database operation failed: ' . $exception->getMessage());
        }
    }

    /**
     * Get complete schema information for existing table
     * 
     * @param string $tableName Table name without prefix
     * @return array Schema information with columns, indexes, constraints
     */
    private function getTableSchema(string $tableName): array {
        $dbPrefix = CONFIG_DB_PREFIX;
        $dbName = CONFIG_DB_DATABASE;
        $schema = [
            'name' => $tableName,
            'columns' => [],
            'indexes' => [],
            'foreignKeys' => [],
            'properties' => []
        ];
        
        // Get columns
        $sql = "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA, COLUMN_DEFAULT, COLUMN_COMMENT 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION";
        $result = $this->db->query($sql, [$dbName, $dbPrefix . $tableName]);
        
        foreach ($result->rows as $col) {
            $schema['columns'][$col['COLUMN_NAME']] = [
                'type' => $col['COLUMN_TYPE'],
                'nullable' => $col['IS_NULLABLE'] === 'YES',
                'primary' => $col['COLUMN_KEY'] === 'PRI',
                'unique' => $col['COLUMN_KEY'] === 'UNI',
                'extra' => $col['EXTRA'],
                'default' => $col['COLUMN_DEFAULT'],
                'comment' => $col['COLUMN_COMMENT']
            ];
        }
        
        // Get indexes
        $sql = "SELECT DISTINCT INDEX_NAME, INDEX_TYPE, COLUMN_NAME, SEQ_IN_INDEX
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME != 'PRIMARY'
                ORDER BY INDEX_NAME, SEQ_IN_INDEX";
        $result = $this->db->query($sql, [$dbName, $dbPrefix . $tableName]);
        
        foreach ($result->rows as $idx) {
            if (!isset($schema['indexes'][$idx['INDEX_NAME']])) {
                $schema['indexes'][$idx['INDEX_NAME']] = [
                    'type' => $idx['INDEX_TYPE'],
                    'columns' => []
                ];
            }
            $schema['indexes'][$idx['INDEX_NAME']]['columns'][] = $idx['COLUMN_NAME'];
        }
        
        // Get foreign keys
        $sql = "SELECT kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                       COALESCE(rc.DELETE_RULE, 'RESTRICT') as DELETE_RULE,
                       COALESCE(rc.UPDATE_RULE, 'RESTRICT') as UPDATE_RULE
                FROM information_schema.KEY_COLUMN_USAGE kcu
                LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc 
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                WHERE kcu.TABLE_SCHEMA = ? AND kcu.TABLE_NAME = ? AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";
        $result = $this->db->query($sql, [$dbName, $dbPrefix . $tableName]);
        
        foreach ($result->rows as $fk) {
            $schema['foreignKeys'][$fk['CONSTRAINT_NAME']] = [
                'column' => $fk['COLUMN_NAME'],
                'table' => $fk['REFERENCED_TABLE_NAME'],
                'refColumn' => $fk['REFERENCED_COLUMN_NAME'],
                'onDelete' => $fk['DELETE_RULE'] === 'CASCADE',
                'onUpdate' => $fk['UPDATE_RULE'] === 'CASCADE'
            ];
        }
        
        // Get table properties
        $sql = "SELECT ENGINE, TABLE_COLLATION, TABLE_COMMENT FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
        $result = $this->db->query($sql, [$dbName, $dbPrefix . $tableName]);
        
        if ($result->num_rows > 0) {
            $props = $result->rows[0];
            $schema['properties'] = [
                'engine' => $props['ENGINE'],
                'collation' => $props['TABLE_COLLATION'],
                'comment' => $props['TABLE_COMMENT']
            ];
        }
        
        return $schema;
    }

    /**
     * Compute differences between existing and desired table schemas
     * 
     * @param array $existing Existing table schema
     * @param array $desired Desired table definition
     * @return array Diff information with types of changes
     */
    private function computeTableDiff(array $existing, array $desired): array {
        $diff = [
            'changes' => [],
            'columnsToAdd' => [],
            'columnsToModify' => [],
            'columnsToDelete' => [],
            'indexesChanged' => false,
            'propertyChanges' => []
        ];
        
        // Compare columns
        $existingCols = array_keys($existing['columns']);
        $desiredCols = array_column($desired['column'] ?? [], 'name');
        
        // Columns to delete
        $toDelete = array_diff($existingCols, $desiredCols);
        if (!empty($toDelete)) {
            $diff['changes']['columnDeletions'] = count($toDelete);
            $diff['columnsToDelete'] = $toDelete;
        }
        
        // Columns to add or modify
        foreach ($desired['column'] ?? [] as $desiredCol) {
            $colName = $desiredCol['name'];
            
            if (!isset($existing['columns'][$colName])) {
                // New column
                $diff['changes']['columnAdditions'][] = $colName;
                $diff['columnsToAdd'][] = $desiredCol;
            } else {
                // Check if column needs modification
                if ($this->columnNeedsUpdate($existing['columns'][$colName], $desiredCol, $colName)) {
                    $diff['changes']['columnModifications'][] = $colName;
                    $diff['columnsToModify'][] = $desiredCol;
                }
            }
        }
        
        // Compare indexes
        if ($this->indexesChanged($existing['indexes'], $desired['index'] ?? [])) {
            $diff['changes']['indexChanges'] = true;
            $diff['indexesChanged'] = true;
        }
        
        // Compare table properties
        if (isset($desired['tableComment'])) {
            if (($existing['properties']['comment'] ?? '') !== $desired['tableComment']) {
                $diff['changes']['commentChange'] = $desired['tableComment'];
                $diff['propertyChanges']['comment'] = $desired['tableComment'];
            }
        }
        
        if (isset($desired['engine'])) {
            if (($existing['properties']['engine'] ?? '') !== $desired['engine']) {
                $diff['changes']['engineChange'] = $desired['engine'];
                $diff['propertyChanges']['engine'] = $desired['engine'];
            }
        }
        
        return $diff;
    }

    /**
     * Check if a column needs updating based on definition changes
     * Compares the SQL that would be generated for the column
     * 
     * @param array $existingCol Existing column definition (from database)
     * @param array $desiredCol Desired column definition (from migration)
     * @param string $colName Column name
     * @return bool True if column needs modification
     */
    private function columnNeedsUpdate(array $existingCol, array $desiredCol, string $colName = ''): bool {
        // Build what the SQL would look like for both
        // If they're identical, no update needed
        
        // For existing column, reconstruct what it would look like
        $existingSql = $this->buildColumnDefinitionFromDb($existingCol, $colName);
        
        // For desired column, build its definition
        $desiredSql = $this->buildColumnDefinition($desiredCol, false);
        
        // Compare the SQL definitions
        // Normalize whitespace for comparison
        $existingSql = preg_replace('/\s+/', ' ', trim($existingSql));
        $desiredSql = preg_replace('/\s+/', ' ', trim($desiredSql));
        
        return $existingSql !== $desiredSql;
    }
    
    /**
     * Build column definition from existing database column info
     * This reconstructs what the column definition SQL would be
     * 
     * @param array $col Database column information
     * @param string $colName Column name (since DB schema doesn't include it)
     * @return string Column definition SQL
     */
    private function buildColumnDefinitionFromDb(array $col, string $colName = ''): string {
        // The column name comes from the array key, not the value
        $definition = "`{$colName}` {$col['type']}";
        
        if (!$col['nullable']) {
            $definition .= ' NOT NULL';
        }
        
        if ($col['default'] !== null) {
            // Handle SQL functions
            $default = $col['default'];
            if (in_array(strtoupper($default), ['CURRENT_TIMESTAMP', 'NOW()', 'NULL']) || 
                preg_match('/^[0-9]+(\.[0-9]+)?$/', $default) ||
                strtolower($default) === 'true' || 
                strtolower($default) === 'false') {
                $definition .= ' DEFAULT ' . $default;
            } else {
                $definition .= " DEFAULT '" . addslashes($default) . "'";
            }
        }
        
        if (strpos($col['extra'], 'auto_increment') !== false) {
            $definition .= ' AUTO_INCREMENT';
        }
        
        if (!empty($col['comment'])) {
            $definition .= " COMMENT '" . addslashes($col['comment']) . "'";
        }
        
        return $definition;
    }

    /**
     * Check if indexes have changed between existing and desired state
     * 
     * @param array $existingIndexes Existing indexes
     * @param array $desiredIndexes Desired indexes
     * @return bool True if indexes differ
     */
    private function indexesChanged(array $existingIndexes, array $desiredIndexes): bool {
        // Build comparable format
        $existing = [];
        foreach ($existingIndexes as $name => $index) {
            $existing[$name] = [
                'type' => $index['type'],
                'columns' => json_encode($index['columns'])
            ];
        }
        
        $desired = [];
        foreach ($desiredIndexes as $index) {
            $indexName = $index['name'] ?? '';
            if ($indexName) {
                $desired[$indexName] = [
                    'type' => $index['type'] ?? 'BTREE',
                    'columns' => json_encode($index['column'] ?? [])
                ];
            }
        }
        
        // Compare structure
        return json_encode($existing) !== json_encode($desired);
    }

    /**
     * Apply detected changes to a table
     * 
     * @param string $tableName Table name without prefix
     * @param array $diff Differences from computeTableDiff
     * @return void
     */
    private function applyTableChanges(string $tableName, array $diff): void {
        $dbPrefix = CONFIG_DB_PREFIX;
        $fullTableName = $dbPrefix . $tableName;
        
        // Delete columns
        foreach ($diff['columnsToDelete'] as $colName) {
            $sql = "ALTER TABLE `{$fullTableName}` DROP COLUMN `{$colName}`";
            $this->db->query($sql);
            $this->logQuery($sql);
            if ($this->debug) {
                $this->log("Dropped column '{$colName}' from table '{$tableName}'");
            }
        }
        
        // Add new columns
        foreach ($diff['columnsToAdd'] as $column) {
            $colDef = $this->buildColumnDefinition($column, true);
            $sql = "ALTER TABLE `{$fullTableName}` ADD COLUMN " . $colDef;
            $this->db->query($sql);
            $this->logQuery($sql);
            if ($this->debug) {
                $this->log("Added column '{$column['name']}' to table '{$tableName}'");
            }
        }
        
        // Modify existing columns
        foreach ($diff['columnsToModify'] as $column) {
            $colDef = $this->buildColumnDefinition($column, false);
            $sql = "ALTER TABLE `{$fullTableName}` MODIFY COLUMN " . $colDef;
            $this->db->query($sql);
            $this->logQuery($sql);
            if ($this->debug) {
                $this->log("Modified column '{$column['name']}' in table '{$tableName}'");
            }
        }
        
        // Handle index changes (drop and recreate all indexes)
        if ($diff['indexesChanged']) {
            // Re-create indexes will be handled at table level
            if ($this->debug) {
                $this->log("Table '{$tableName}' has index changes");
            }
        }
        
        // Update table properties
        if (isset($diff['propertyChanges']['comment'])) {
            $comment = addslashes($diff['propertyChanges']['comment']);
            $sql = "ALTER TABLE `{$fullTableName}` COMMENT = '{$comment}'";
            $this->db->query($sql);
            $this->logQuery($sql);
        }
    }

    /**
     * Drop foreign keys that are about to change
     * 
     * @param array $tables Tables with desired FK definitions
     * @param string $dbPrefix Database prefix
     * @param string $dbName Database name
     * @return void
     */
    private function dropChangedForeignKeys(array $tables, string $dbPrefix, string $dbName): void {
        foreach ($tables as $table) {
            $tableName = $dbPrefix . $table['name'];
            
            // Get existing foreign keys
            $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                    WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
            $result = $this->db->query($sql, [$dbName, $tableName]);
            
            // Build list of desired FK names
            $desiredFKs = [];
            if (isset($table['foreign'])) {
                foreach ($table['foreign'] as $fk) {
                    $fkName = $fk['name'] ?? 'fk_' . $table['name'] . '_' . $fk['table'] . '_' . $fk['key'];
                    $desiredFKs[$fkName] = true;
                }
            }
            
            // Drop FKs that don't exist in desired definition or have changed
            foreach ($result->rows as $row) {
                $fkName = $row['CONSTRAINT_NAME'];
                
                if (!isset($desiredFKs[$fkName])) {
                    // FK to be removed - drop it
                    $dropSql = "ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$fkName}`";
                    $this->db->query($dropSql);
                    $this->logQuery($dropSql);
                    if ($this->debug) {
                        $this->log("Dropped foreign key '{$fkName}' from table '{$table['name']}'");
                    }
                } else {
                    // FK exists in desired state - keep as is
                    unset($desiredFKs[$fkName]);
                }
            }
        }
    }

    /**
     * Add all foreign keys defined in table schemas
     * 
     * @param array $tables Tables with foreign key definitions
     * @param string $dbPrefix Database prefix
     * @return void
     */
    private function addForeignKeys(array $tables, string $dbPrefix): void {
        $dbName = CONFIG_DB_DATABASE;
        
        foreach ($tables as $table) {
            if (isset($table['foreign'])) {
                foreach ($table['foreign'] as $foreign) {
                    $tableName = $dbPrefix . $table['name'];
                    $constraintName = $foreign['name'] ?? 'fk_' . $table['name'] . '_' . $foreign['table'] . '_' . $foreign['key'];
                    
                    // Check if the constraint already exists
                    $checkSql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                                WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
                    $result = $this->db->query($checkSql, [$dbName, $tableName, $constraintName]);
                    
                    if ($result->num_rows > 0) {
                        // FK already exists, skip
                        if ($this->debug) {
                            $this->log("Foreign key '{$constraintName}' already exists on table '{$table['name']}'");
                        }
                        continue;
                    }
                    
                    $onDelete = ($foreign['onDelete'] ? " ON DELETE CASCADE" : "");
                    $onUpdate = ($foreign['onUpdate'] ? " ON UPDATE CASCADE" : "");
                    
                    $addForeignKeySql = "ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$foreign['key']}`) REFERENCES `{$dbPrefix}{$foreign['table']}` (`{$foreign['column']}`)" . $onDelete . $onUpdate;
                    $this->db->query($addForeignKeySql);
                    $this->logQuery($addForeignKeySql);
                    
                    if ($this->debug) {
                        $this->log("Added foreign key '{$constraintName}' to table '{$table['name']}'");
                    }
                }
            }
        }
    }

}