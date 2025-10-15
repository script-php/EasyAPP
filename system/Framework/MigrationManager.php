<?php

/**
 * @package      Framework - Migration Manager
 * @author       EasyAPP Framework  
 * @copyright    Copyright (c) 2022, script-php.ro
 * @link         https://script-php.ro
 */

namespace System\Framework;

use System\Framework\Migration;
use System\Framework\Tables;
use System\Framework\Exceptions\DatabaseQuery as FrameworkException;

/**
 * Migration Manager
 * 
 * Handles the execution, tracking, and management of database migrations.
 * Provides a complete migration system with version control and rollback support.
 * 
 * Features:
 * - Automatic migration discovery and loading
 * - Version tracking and dependency management
 * - Rollback and batch processing support
 * - Progress reporting and error handling
 * - Integration with existing Tables system
 */
class MigrationManager {
    
    /**
     * Framework registry
     * @var object
     */
    private $registry;
    
    /**
     * Database connection
     * @var object
     */
    private $db;
    
    /**
     * Tables instance
     * @var Tables
     */
    private $tables;
    
    /**
     * Migration directory path
     * @var string
     */
    private $migrationPath;
    
    /**
     * Loaded migration instances
     * @var array
     */
    private $migrations = [];
    
    /**
     * Applied migrations cache
     * @var array
     */
    private $appliedMigrations = [];
    
    /**
     * Debug mode flag
     * @var bool
     */
    private $debug = false;
    
    /**
     * Migration tracker table name
     */
    const MIGRATION_TABLE = 'framework_migrations';
    
    /**
     * Initialize Migration Manager
     * 
     * @param object $registry Framework registry
     * @param string|null $migrationPath Custom migration directory path
     */
    public function __construct($registry, ?string $migrationPath = null) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->tables = new Tables($registry);
        
        // Set migration directory path
        $this->migrationPath = $migrationPath ?: PATH . 'migrations' . DIRECTORY_SEPARATOR;
        
        // Set debug mode from config
        $this->debug = defined('CONFIG_DEBUG') && CONFIG_DEBUG;
        
        // Ensure migration tracking table exists
        $this->ensureMigrationTable();
        
        // Load applied migrations cache
        $this->loadAppliedMigrations();
    }
    
    /**
     * Run all pending migrations
     * 
     * @param int|null $targetVersion Specific version to migrate to (null = latest)
     * @param bool $dryRun Show what would be executed without actually running
     * @return array Migration results
     * @throws FrameworkException On migration failure
     */
    public function migrate(?int $targetVersion = null, bool $dryRun = false): array {
        $results = [
            'executed' => [],
            'skipped' => [],
            'errors' => [],
            'total_time' => 0
        ];
        
        $startTime = microtime(true);
        
        try {
            // Discover and load migrations
            $this->discoverMigrations();
            
            // Get pending migrations
            $pendingMigrations = $this->getPendingMigrations($targetVersion);
            
            if (empty($pendingMigrations)) {
                $this->log('No pending migrations found');
                return $results;
            }
            
            $this->log(sprintf('Found %d pending migration(s)', count($pendingMigrations)));
            
            if ($dryRun) {
                $this->log('DRY RUN - No changes will be applied');
            }
            
            // Execute migrations in order
            foreach ($pendingMigrations as $migration) {
                $migrationResult = $this->executeMigration($migration, 'up', $dryRun);
                
                if ($migrationResult['success']) {
                    $results['executed'][] = $migrationResult;
                } else {
                    $results['errors'][] = $migrationResult;
                    
                    // Stop on first error unless in dry run mode
                    if (!$dryRun) {
                        break;
                    }
                }
            }
            
            $results['total_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->log(sprintf(
                'Migration completed: %d executed, %d errors in %dms',
                count($results['executed']),
                count($results['errors']),
                $results['total_time']
            ));
            
            return $results;
            
        } catch (\Exception $e) {
            $error = 'Migration process failed: ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Rollback migrations to a specific version
     * 
     * @param int $targetVersion Version to rollback to
     * @param bool $dryRun Show what would be executed without actually running
     * @return array Rollback results
     * @throws FrameworkException On rollback failure
     */
    public function rollback(int $targetVersion, bool $dryRun = false): array {
        $results = [
            'rolled_back' => [],
            'skipped' => [],
            'errors' => [],
            'total_time' => 0
        ];
        
        $startTime = microtime(true);
        
        try {
            // Discover and load migrations
            $this->discoverMigrations();
            
            // Get migrations to rollback (in reverse order)
            $migrationsToRollback = $this->getMigrationsToRollback($targetVersion);
            
            if (empty($migrationsToRollback)) {
                $this->log('No migrations to rollback');
                return $results;
            }
            
            $this->log(sprintf('Rolling back %d migration(s)', count($migrationsToRollback)));
            
            if ($dryRun) {
                $this->log('DRY RUN - No changes will be applied');
            }
            
            // Execute rollbacks in reverse order
            foreach ($migrationsToRollback as $migration) {
                $migrationResult = $this->executeMigration($migration, 'down', $dryRun);
                
                if ($migrationResult['success']) {
                    $results['rolled_back'][] = $migrationResult;
                } else {
                    $results['errors'][] = $migrationResult;
                    
                    // Stop on first error unless in dry run mode
                    if (!$dryRun) {
                        break;
                    }
                }
            }
            
            $results['total_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->log(sprintf(
                'Rollback completed: %d rolled back, %d errors in %dms',
                count($results['rolled_back']),
                count($results['errors']),
                $results['total_time']
            ));
            
            return $results;
            
        } catch (\Exception $e) {
            $error = 'Rollback process failed: ' . $e->getMessage();
            $this->logError($error);
            throw new FrameworkException($error);
        }
    }
    
    /**
     * Get migration status information
     * 
     * @return array Status information
     */
    public function getStatus(): array {
        $this->discoverMigrations();
        $this->loadAppliedMigrations();
        
        $status = [
            'total_migrations' => count($this->migrations),
            'applied_migrations' => count($this->appliedMigrations),
            'pending_migrations' => 0,
            'current_version' => $this->getCurrentVersion(),
            'latest_version' => $this->getLatestVersion(),
            'migrations' => []
        ];
        
        foreach ($this->migrations as $version => $migration) {
            $isApplied = isset($this->appliedMigrations[$version]);
            $appliedInfo = $isApplied ? $this->appliedMigrations[$version] : null;
            
            $status['migrations'][$version] = [
                'version' => $version,
                'class_name' => $migration->getClassName(),
                'description' => $migration->getDescription(),
                'applied' => $isApplied,
                'applied_at' => $appliedInfo['applied_at'] ?? null,
                'execution_time' => $appliedInfo['execution_time'] ?? null
            ];
            
            if (!$isApplied) {
                $status['pending_migrations']++;
            }
        }
        
        return $status;
    }
    
    /**
     * Create a new migration file
     * 
     * @param string $name Migration name (e.g., "CreateUsersTable")
     * @return string Path to created migration file
     * @throws FrameworkException On file creation failure
     */
    public function createMigration(string $name): string {
        // Ensure migration directory exists
        if (!is_dir($this->migrationPath)) {
            if (!mkdir($this->migrationPath, 0755, true)) {
                throw new FrameworkException('Failed to create migration directory: ' . $this->migrationPath);
            }
        }
        
        // Generate version number
        $version = $this->getNextVersion();
        
        // Generate class name
        $className = 'Migration_' . str_pad($version, 3, '0', STR_PAD_LEFT) . '_' . $this->toCamelCase($name);
        
        // Generate filename
        $filename = str_pad($version, 3, '0', STR_PAD_LEFT) . '_' . $this->toSnakeCase($name) . '.php';
        $filepath = $this->migrationPath . $filename;
        
        // Generate migration content
        $content = $this->generateMigrationTemplate($className, $name);
        
        // Write file
        if (file_put_contents($filepath, $content) === false) {
            throw new FrameworkException('Failed to create migration file: ' . $filepath);
        }
        
        $this->log("Created migration: {$filepath}");
        
        return $filepath;
    }
    
    /**
     * Discover and load migration files from directory
     */
    private function discoverMigrations(): void {
        if (!is_dir($this->migrationPath)) {
            $this->log('Migration directory not found: ' . $this->migrationPath);
            return;
        }
        
        $files = glob($this->migrationPath . '*.php');
        sort($files);
        
        foreach ($files as $file) {
            $this->loadMigrationFile($file);
        }
        
        // Sort migrations by version
        ksort($this->migrations);
    }
    
    /**
     * Load a migration file and instantiate the migration class
     * 
     * @param string $file Migration file path
     */
    private function loadMigrationFile(string $file): void {
        $filename = basename($file, '.php');
        
        // Extract version from filename
        if (!preg_match('/^(\d+)_/', $filename, $matches)) {
            $this->log("Skipping invalid migration file: {$filename}");
            return;
        }
        
        $version = (int)$matches[1];
        
        // Include the file
        require_once $file;
        
        // Find the migration class
        $className = $this->findMigrationClass($file);
        
        if (!$className) {
            $this->log("No migration class found in file: {$filename}");
            return;
        }
        
        // Instantiate migration
        try {
            $migration = new $className($this->registry);
            
            if (!($migration instanceof Migration)) {
                $this->log("Class {$className} does not extend Migration base class");
                return;
            }
            
            $this->migrations[$version] = $migration;
            
        } catch (\Exception $e) {
            $this->logError("Failed to instantiate migration {$className}: " . $e->getMessage());
        }
    }
    
    /**
     * Find migration class in loaded file
     * 
     * @param string $file File path
     * @return string|null Migration class name
     */
    private function findMigrationClass(string $file): ?string {
        $content = file_get_contents($file);
        
        // Look for class that extends Migration
        if (preg_match('/class\s+([A-Za-z_][A-Za-z0-9_]*)\s+extends\s+Migration/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get pending migrations up to target version
     * 
     * @param int|null $targetVersion Target version (null = all pending)
     * @return array Pending migration instances
     */
    private function getPendingMigrations(?int $targetVersion = null): array {
        $pending = [];
        
        foreach ($this->migrations as $version => $migration) {
            // Skip if already applied
            if (isset($this->appliedMigrations[$version])) {
                continue;
            }
            
            // Skip if beyond target version
            if ($targetVersion !== null && $version > $targetVersion) {
                continue;
            }
            
            $pending[$version] = $migration;
        }
        
        return $pending;
    }
    
    /**
     * Get migrations to rollback (applied migrations above target version)
     * 
     * @param int $targetVersion Target version to rollback to
     * @return array Migrations to rollback in reverse order
     */
    private function getMigrationsToRollback(int $targetVersion): array {
        $toRollback = [];
        
        foreach ($this->migrations as $version => $migration) {
            // Only rollback migrations that are applied and above target version
            if (isset($this->appliedMigrations[$version]) && $version > $targetVersion) {
                $toRollback[$version] = $migration;
            }
        }
        
        // Sort in reverse order (newest first)
        krsort($toRollback);
        
        return $toRollback;
    }
    
    /**
     * Execute a single migration
     * 
     * @param Migration $migration Migration instance
     * @param string $direction 'up' or 'down'
     * @param bool $dryRun Whether this is a dry run
     * @return array Execution result
     */
    private function executeMigration(Migration $migration, string $direction, bool $dryRun = false): array {
        $startTime = microtime(true);
        $version = $migration->getVersion();
        
        $result = [
            'version' => $version,
            'class_name' => $migration->getClassName(),
            'description' => $migration->getDescription(),
            'direction' => $direction,
            'success' => false,
            'execution_time' => 0,
            'error' => null,
            'dry_run' => $dryRun
        ];
        
        try {
            $this->log("Executing migration {$direction}: {$migration->getClassName()}");
            
            if ($dryRun) {
                $this->log("DRY RUN: Would execute {$direction} for {$migration->getClassName()}");
                $result['success'] = true;
            } else {
                // Check dependencies
                if (!$migration->checkDependencies($direction)) {
                    throw new FrameworkException('Migration dependencies not satisfied');
                }
                
                // Execute migration
                $migration->execute($direction);
                
                // Update tracking
                if ($direction === 'up') {
                    $this->recordMigrationApplied($version, $migration);
                } else {
                    $this->recordMigrationRolledBack($version);
                }
                
                $result['success'] = true;
            }
            
            $result['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->log("Migration {$direction} completed: {$migration->getClassName()} ({$result['execution_time']}ms)");
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['execution_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logError("Migration {$direction} failed: {$migration->getClassName()} - " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Ensure migration tracking table exists
     */
    private function ensureMigrationTable(): void {
        if (!$this->tables->exists(self::MIGRATION_TABLE)) {
            $this->tables->table(self::MIGRATION_TABLE)
                ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
                ->column('version')->type('VARCHAR(10)')->notNull(true)->unique()
                ->column('class_name')->type('VARCHAR(255)')->notNull(true)
                ->column('description')->type('TEXT')
                ->column('applied_at')->type('TIMESTAMP')->default('CURRENT_TIMESTAMP')
                ->column('execution_time')->type('INT(11)')->default(0)
                ->index('idx_version', ['version'])
                ->create();
                
            $this->log('Created migration tracking table: ' . self::MIGRATION_TABLE);
        }
    }
    
    /**
     * Load applied migrations from database
     */
    private function loadAppliedMigrations(): void {
        $sql = "SELECT * FROM `" . CONFIG_DB_PREFIX . self::MIGRATION_TABLE . "` ORDER BY version";
        $result = $this->db->query($sql);
        
        $this->appliedMigrations = [];
        foreach ($result->rows as $row) {
            $this->appliedMigrations[(int)$row['version']] = $row;
        }
    }
    
    /**
     * Record migration as applied
     * 
     * @param int $version Migration version
     * @param Migration $migration Migration instance
     */
    private function recordMigrationApplied(int $version, Migration $migration): void {
        $sql = "INSERT INTO `" . CONFIG_DB_PREFIX . self::MIGRATION_TABLE . "` 
                (version, class_name, description, execution_time) VALUES (?, ?, ?, ?)";
                
        $executionTime = 0; // Will be updated by caller if needed
        
        $this->db->query($sql, [
            $version,
            $migration->getClassName(),
            $migration->getDescription(),
            $executionTime
        ]);
        
        // Update cache
        $this->appliedMigrations[$version] = [
            'version' => $version,
            'class_name' => $migration->getClassName(),
            'description' => $migration->getDescription(),
            'applied_at' => date('Y-m-d H:i:s'),
            'execution_time' => $executionTime
        ];
    }
    
    /**
     * Record migration as rolled back (remove from applied)
     * 
     * @param int $version Migration version
     */
    private function recordMigrationRolledBack(int $version): void {
        $sql = "DELETE FROM `" . CONFIG_DB_PREFIX . self::MIGRATION_TABLE . "` WHERE version = ?";
        $this->db->query($sql, [$version]);
        
        // Update cache
        unset($this->appliedMigrations[$version]);
    }
    
    /**
     * Get current database version
     * 
     * @return int Current version
     */
    public function getCurrentVersion(): int {
        if (empty($this->appliedMigrations)) {
            return 0;
        }
        
        return max(array_keys($this->appliedMigrations));
    }
    
    /**
     * Get latest available version
     * 
     * @return int Latest version
     */
    public function getLatestVersion(): int {
        $this->discoverMigrations();
        
        if (empty($this->migrations)) {
            return 0;
        }
        
        return max(array_keys($this->migrations));
    }
    
    /**
     * Get next version number for new migration
     * 
     * @return int Next version number
     */
    private function getNextVersion(): int {
        return $this->getLatestVersion() + 1;
    }
    
    /**
     * Generate migration template content
     * 
     * @param string $className Class name
     * @param string $name Human readable name
     * @return string Migration template
     */
    private function generateMigrationTemplate(string $className, string $name): string {
        $date = date('Y-m-d H:i:s');
        
        return "<?php

/**
 * Migration: {$name}
 * Created: {$date}
 */

use System\\Framework\\Migration;

class {$className} extends Migration {
    
    /**
     * Apply the migration
     */
    public function up(): void {
        // TODO: Implement schema changes
        // Example:
        // \$this->tables->table('example_table')
        //     ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
        //     ->column('name')->type('VARCHAR(100)')->notNull(true)
        //     ->create();
    }
    
    /**
     * Rollback the migration  
     */
    public function down(): void {
        // TODO: Implement rollback logic
        // Example:
        // \$this->tables->drop('example_table');
    }
    
    /**
     * Get migration description
     */
    public function getDescription(): string {
        return '{$name}';
    }
}
";
    }
    
    /**
     * Convert string to CamelCase
     * 
     * @param string $string Input string
     * @return string CamelCase string
     */
    private function toCamelCase(string $string): string {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));
    }
    
    /**
     * Convert string to snake_case
     * 
     * @param string $string Input string
     * @return string snake_case string
     */
    private function toSnakeCase(string $string): string {
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '_', $string));
    }
    
    /**
     * Log informational message
     * 
     * @param string $message Message to log
     */
    private function log(string $message): void {
        if ($this->debug) {
            error_log('[MigrationManager] ' . $message);
        }
    }
    
    /**
     * Log error message
     * 
     * @param string $error Error message
     */
    private function logError(string $error): void {
        error_log('[MigrationManager ERROR] ' . $error);
    }
}