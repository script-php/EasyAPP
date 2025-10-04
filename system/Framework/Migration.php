<?php

/**
 * @package      Framework - Migration System
 * @author       EasyAPP Framework
 * @copyright    Copyright (c) 2022, script-php.ro
 * @link         https://script-php.ro
 */

namespace System\Framework;

use System\Framework\Tables;
use System\Framework\Exceptions\DatabaseQuery as FrameworkException;

/**
 * Abstract Migration Base Class
 * 
 * Provides the foundation for database schema migrations with integrated Tables support.
 * Each migration must implement up() and down() methods for applying and rolling back changes.
 * 
 * Features:
 * - Seamless Tables class integration
 * - Automatic transaction management
 * - Error handling and logging
 * - Data transformation support
 * - Rollback capabilities
 */
abstract class Migration {
    
    /**
     * Tables instance for schema operations
     * @var Tables
     */
    protected $tables;
    
    /**
     * Database connection
     * @var object
     */
    protected $db;
    
    /**
     * Registry for framework dependencies
     * @var object
     */
    protected $registry;
    
    /**
     * Migration metadata
     * @var array
     */
    protected $metadata = [];
    
    /**
     * Debug mode flag
     * @var bool
     */
    protected $debug = false;
    
    /**
     * Initialize migration with framework dependencies
     * 
     * @param object $registry Framework registry
     */
    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->tables = new Tables($registry);
        
        // Set debug mode from config
        $this->debug = defined('CONFIG_DEBUG') && CONFIG_DEBUG;
        
        // Initialize metadata
        $this->metadata = [
            'class_name' => get_class($this),
            'created_at' => date('Y-m-d H:i:s'),
            'applied_at' => null,
            'rolled_back_at' => null
        ];
    }
    
    /**
     * Apply the migration (create/modify schema)
     * 
     * This method must be implemented by each migration to define
     * the forward changes to be applied to the database.
     * 
     * @return void
     * @throws FrameworkException On migration failure
     */
    abstract public function up(): void;
    
    /**
     * Rollback the migration (undo changes)
     * 
     * This method must be implemented by each migration to define
     * how to undo the changes made in the up() method.
     * 
     * @return void
     * @throws FrameworkException On rollback failure
     */
    abstract public function down(): void;
    
    /**
     * Get migration description/summary
     * 
     * Override this method to provide a human-readable description
     * of what this migration does.
     * 
     * @return string Migration description
     */
    public function getDescription(): string {
        return 'Database migration: ' . $this->getClassName();
    }
    
    /**
     * Get migration version from class name
     * 
     * Extracts version number from migration class name.
     * Expected format: Migration_001_DescriptiveName
     * 
     * @return string Migration version
     */
    public function getVersion(): string {
        $className = $this->getClassName();
        if (preg_match('/Migration_(\d+)_/', $className, $matches)) {
            return $matches[1];
        }
        return '000';
    }
    
    /**
     * Get short class name without namespace
     * 
     * @return string Class name
     */
    public function getClassName(): string {
        return (new \ReflectionClass($this))->getShortName();
    }
    
    /**
     * Execute migration with transaction and error handling
     * 
     * @param string $direction 'up' or 'down'
     * @return bool Success status
     * @throws FrameworkException On execution failure
     */
    public function execute(string $direction = 'up'): bool {
        $startTime = microtime(true);
        
        try {
            // Validate direction
            if (!in_array($direction, ['up', 'down'])) {
                throw new FrameworkException("Invalid migration direction: {$direction}");
            }
            
            if ($this->debug) {
                $this->log("Starting migration {$direction}: " . $this->getClassName());
            }
            
            // Execute within transaction
            $this->db->query('START TRANSACTION');
            
            // Call the appropriate method
            if ($direction === 'up') {
                $this->up();
                $this->metadata['applied_at'] = date('Y-m-d H:i:s');
            } else {
                $this->down();
                $this->metadata['rolled_back_at'] = date('Y-m-d H:i:s');
            }
            
            $this->db->query('COMMIT');
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($this->debug) {
                $this->log("Migration {$direction} completed successfully in {$executionTime}ms");
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->db->query('ROLLBACK');
            
            $error = "Migration {$direction} failed for " . $this->getClassName() . ": " . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Check if migration has required dependencies
     * 
     * Override this method to check for required tables, columns, 
     * or other migrations that must exist before this migration runs.
     * 
     * @param string $direction 'up' or 'down'
     * @return bool Whether dependencies are satisfied
     */
    public function checkDependencies(string $direction = 'up'): bool {
        return true; // Override in subclasses if needed
    }
    
    /**
     * Execute raw SQL query with parameter binding
     * 
     * Helper method for complex SQL operations that can't be handled
     * by the Tables class fluent API.
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return object Query result
     */
    protected function query(string $sql, array $params = []) {
        if ($this->debug) {
            $this->log("Executing SQL: " . $sql);
        }
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Check if table exists in database
     * 
     * @param string $tableName Table name (without prefix)
     * @return bool Whether table exists
     */
    protected function tableExists(string $tableName): bool {
        return $this->tables->exists($tableName);
    }
    
    /**
     * Check if column exists in table
     * 
     * @param string $tableName Table name (without prefix)
     * @param string $columnName Column name
     * @return bool Whether column exists
     */
    protected function columnExists(string $tableName, string $columnName): bool {
        $columns = $this->tables->describe($tableName);
        foreach ($columns as $column) {
            if ($column['Field'] === $columnName) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if index exists on table
     * 
     * @param string $tableName Table name (without prefix)
     * @param string $indexName Index name
     * @return bool Whether index exists
     */
    protected function indexExists(string $tableName, string $indexName): bool {
        $indexes = $this->tables->getIndexes($tableName);
        foreach ($indexes as $index) {
            if ($index['Key_name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Transform data during migration
     * 
     * Helper method for safely transforming data when schema changes.
     * Processes data in batches to handle large datasets efficiently.
     * 
     * @param string $table Table name (without prefix)
     * @param callable $transformer Function to transform each row
     * @param int $batchSize Number of rows to process at once
     * @return int Number of rows processed
     */
    protected function transformData(string $table, callable $transformer, int $batchSize = 1000): int {
        $fullTableName = CONFIG_DB_PREFIX . $table;
        $totalProcessed = 0;
        $offset = 0;
        
        if ($this->debug) {
            $this->log("Starting data transformation for table: {$table}");
        }
        
        do {
            $sql = "SELECT * FROM `{$fullTableName}` LIMIT {$batchSize} OFFSET {$offset}";
            $result = $this->db->query($sql);
            
            if ($result->num_rows === 0) {
                break;
            }
            
            foreach ($result->rows as $row) {
                $transformedRow = $transformer($row);
                
                if ($transformedRow !== null) {
                    // Update the row with transformed data
                    $this->updateRow($fullTableName, $row['id'], $transformedRow);
                }
            }
            
            $totalProcessed += $result->num_rows;
            $offset += $batchSize;
            
            if ($this->debug && $totalProcessed % ($batchSize * 10) === 0) {
                $this->log("Processed {$totalProcessed} rows so far...");
            }
            
        } while ($result->num_rows === $batchSize);
        
        if ($this->debug) {
            $this->log("Data transformation completed. Total rows processed: {$totalProcessed}");
        }
        
        return $totalProcessed;
    }
    
    /**
     * Update a single row with transformed data
     * 
     * @param string $table Full table name with prefix
     * @param int $id Row ID
     * @param array $data Updated data
     */
    private function updateRow(string $table, int $id, array $data): void {
        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "`{$column}` = ?";
            $params[] = $value;
        }
        
        $params[] = $id; // For WHERE clause
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE id = ?";
        $this->db->query($sql, $params);
    }
    
    /**
     * Log informational message
     * 
     * @param string $message Message to log
     */
    protected function log(string $message): void {
        if ($this->debug) {
            error_log('[Migration] ' . $message);
        }
    }
    
    /**
     * Log error message
     * 
     * @param string $error Error message
     */
    protected function logError(string $error): void {
        error_log('[Migration ERROR] ' . $error);
    }
    
    /**
     * Get migration metadata
     * 
     * @return array Migration metadata
     */
    public function getMetadata(): array {
        return $this->metadata;
    }
}