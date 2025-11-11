<?php

namespace System;

use System\Framework\MigrationManager;

class Cli {
    private $version = '1.0.0';
    private $commands = [];
    private $registry;
    private $migrationManager;
    
    public function __construct($registry) {
        // Initialize framework
        $this->registry = $registry;
        
        // Initialize migration manager
        if (defined('CONFIG_DB_DRIVER')) {
            $this->registry->set('db', new Framework\Db(
                CONFIG_DB_DRIVER,
                CONFIG_DB_HOSTNAME, 
                CONFIG_DB_DATABASE,
                CONFIG_DB_USERNAME,
                CONFIG_DB_PASSWORD,
                CONFIG_DB_PORT,
                '',  // encoding
                ''   // options
            ));
        }

        $this->migrationManager = new MigrationManager($this->registry);

        $this->registerCommands();
    }
    
    private function registerCommands() {
        $this->commands = [
            // Framework Generation Commands
            'make:controller' => [$this, 'makeController'],
            'make:model' => [$this, 'makeModel'],
            'make:model:table' => [$this, 'makeModelFromTable'],
            'make:models' => [$this, 'makeModelsFromAllTables'],
            'make:service' => [$this, 'makeService'],
            'make:migration' => [$this, 'makeMigration'],
            
            // Database Migration Commands
            'migrate' => [$this, 'migrate'],
            'migrate:status' => [$this, 'migrateStatus'],
            'migrate:rollback' => [$this, 'migrateRollback'],
            'migrate:create' => [$this, 'makeMigration'],
            
            // Test Commands
            'test' => [$this, 'runTests'],
            'test:unit' => [$this, 'runUnitTests'],
            'test:integration' => [$this, 'runIntegrationTests'],
            
            // Cache Commands
            'cache:clear' => [$this, 'clearCache'],
            
            // Development Server
            'serve' => [$this, 'serve'],
            
            // Utility Commands
            'help' => [$this, 'showHelp'],
            '--version' => [$this, 'showVersion'],
            '--help' => [$this, 'showHelp'],
        ];
    }
    
    public function run($argv) {
        if (count($argv) < 2) {
            $this->showHelp();
            return;
        }
        
        $command = $argv[1];
        $args = array_slice($argv, 2);
        
        // Handle special flags
        if (in_array('--help', $args) || in_array('-h', $args)) {
            $this->showCommandHelp($command);
            return;
        }
        
        if (!isset($this->commands[$command])) {
            $this->error("Unknown command: {$command}");
            $this->showHelp();
            return;
        }
        
        try {
            call_user_func($this->commands[$command], $args);
        } catch (Exception $e) {
            $this->error("Command failed: " . $e->getMessage());
            if (defined('CONFIG_DEBUG') && CONFIG_DEBUG) {
                $this->error("Stack trace: " . $e->getTraceAsString());
            }
        }
    }
    
    // ============================================================================
    // MIGRATION COMMANDS
    // ============================================================================
    
    public function migrate($args) {
        $dryRun = in_array('--dry-run', $args);
        $targetVersion = null;
        
        // Check for --to=X parameter
        foreach ($args as $arg) {
            if (strpos($arg, '--to=') === 0) {
                $targetVersion = (int)substr($arg, 5);
                break;
            }
        }
        
        $this->info("Running Migrations");
        $this->info("=" . str_repeat("=", 50));
        
        if ($dryRun) {
            $this->warning("DRY RUN MODE - No changes will be applied");
        }
        
        $results = $this->migrationManager->migrate($targetVersion, $dryRun);
        $this->displayMigrationResults($results, 'executed');
    }
    
    public function migrateStatus($args) {
        $this->info("Migration Status");
        $this->info("=" . str_repeat("=", 70));
        
        $status = $this->migrationManager->getStatus();
        
        $this->info("Database: " . CONFIG_DB_DATABASE);
        $this->info("Current Version: " . $status['current_version']);
        $this->info("Latest Version: " . $status['latest_version']);
        $this->info("Total Migrations: " . $status['total_migrations']);
        $this->info("Applied: " . $status['applied_migrations']);
        $this->info("Pending: " . $status['pending_migrations']);
        $this->info("");
        
        if (empty($status['migrations'])) {
            $this->info("No migrations found.");
            return;
        }
        
        // Table header
        $this->info(sprintf("%-8s %-10s %-40s %-20s", "Version", "Status", "Description", "Applied At"));
        $this->info(str_repeat("-", 80));
        
        // Migration list
        foreach ($status['migrations'] as $version => $migration) {
            $statusIcon = $migration['applied'] ? "Applied" : "Pending";
            $appliedAt = $migration['applied_at'] ?: '-';
            $description = strlen($migration['description']) > 38 
                ? substr($migration['description'], 0, 35) . '...'
                : $migration['description'];
                
            $this->info(sprintf("%-8s %-10s %-40s %-20s", 
                $version, 
                $statusIcon, 
                $description,
                $appliedAt
            ));
        }
    }
    
    public function migrateRollback($args) {
        if (empty($args[0])) {
            $this->error("Target version is required. Usage: easy migrate:rollback 3");
            return;
        }
        
        $targetVersion = (int)$args[0];
        $dryRun = in_array('--dry-run', $args);
        
        $this->info("Rolling Back Migrations");
        $this->info("=" . str_repeat("=", 50));
        $this->info("Rolling back to version: " . $targetVersion);
        
        if ($dryRun) {
            $this->warning("DRY RUN MODE - No changes will be applied");
        }
        
        $results = $this->migrationManager->rollback($targetVersion, $dryRun);
        $this->displayMigrationResults($results, 'rolled_back');
    }
    
    public function makeMigration($args) {
        if (empty($args[0])) {
            $this->error("Migration name is required. Usage: easy make:migration CreateUsersTable");
            return;
        }
        
        $name = $args[0];
        
        $this->info("Creating New Migration");
        $this->info("=" . str_repeat("=", 50));
        
        $filepath = $this->migrationManager->createMigration($name);
        
        $this->success("Created migration file: " . basename($filepath));
        $this->info("Path: " . $filepath);
        $this->info("");
        $this->info("Next steps:");
        $this->info("1. Edit the migration file to implement up() and down() methods");
        $this->info("2. Run 'easy migrate' to apply the migration");
    }
    
    private function displayMigrationResults($results, $type) {
        if (empty($results[$type]) && empty($results['errors'])) {
            $message = $type === 'executed' ? "No pending migrations found" : "No migrations to rollback";
            $this->success($message);
            return;
        }
        
        // Show successful operations
        foreach ($results[$type] as $migration) {
            $icon = $migration['dry_run'] ? "[DRY RUN]" : "[SUCCESS]";
            if ($type === 'rolled_back') $icon = $migration['dry_run'] ? "[DRY RUN]" : "[ROLLBACK]";
            
            $this->success("{$icon} {$migration['class_name']} ({$migration['execution_time']}ms)");
            $this->info("   " . $migration['description']);
        }
        
        // Show errors
        foreach ($results['errors'] as $migration) {
            $this->error("[ERROR] {$migration['class_name']} - {$migration['error']}");
        }
        
        // Summary
        $this->info("");
        $this->info("Summary:");
        $operationLabel = $type === 'executed' ? 'Executed' : 'Rolled back';
        $this->info("  {$operationLabel}: " . count($results[$type]));
        $this->info("  Errors: " . count($results['errors']));
        $this->info("  Total time: {$results['total_time']}ms");
        
        if (count($results['errors']) > 0) {
            $message = $type === 'executed' ? "Some migrations failed!" : "Some rollbacks failed!";
            $this->error($message);
        } else if (count($results[$type]) > 0) {
            $message = $type === 'executed' ? "All migrations completed successfully!" : "Rollback completed successfully!";
            $this->success($message);
        }
    }
    
    // ============================================================================
    // GENERATOR COMMANDS
    // ============================================================================
    
    public function makeController($args) {
        if (empty($args[0])) {
            $this->error("Controller name is required. Usage: easy make:controller UserController");
            return;
        }
        
        $name = $args[0];
        $className = 'Controller' . ucfirst(str_replace('Controller', '', $name));
        $filename = PATH . 'app/controller/' . strtolower(str_replace('Controller', '', $name)) . '.php';
        
        if (file_exists($filename)) {
            $this->error("Controller already exists: {$filename}");
            return;
        }
        
        $template = $this->getControllerTemplate($className, strtolower(str_replace('Controller', '', $name)));
        
        if (file_put_contents($filename, $template)) {
            $this->success("Controller created: {$filename}");
        } else {
            $this->error("Failed to create controller");
        }
    }
    
    public function makeModel($args) {
        if (empty($args[0])) {
            $this->error("Model name is required. Usage: easy make:model User");
            return;
        }
        
        $name = $args[0];
        $className = ucfirst($name);
        $filename = PATH . 'app/model/' . ucfirst($name) . '.php';
        
        if (file_exists($filename)) {
            $this->error("Model already exists: {$filename}");
            return;
        }
        
        $template = $this->getOrmModelTemplate($className);
        
        if (file_put_contents($filename, $template)) {
            $this->success("Model created: {$filename}");
            $this->info("Using ORM base class. Edit the model to add:");
            $this->info("  - Fillable columns");
            $this->info("  - Relationships");
            $this->info("  - Custom methods");
        } else {
            $this->error("Failed to create model");
        }
    }
    
    public function makeModelFromTable($args) {
        if (empty($args[0])) {
            $this->error("Table name is required. Usage: easy make:model:table users");
            return;
        }
        
        $tableName = $args[0];
        $force = in_array('--force', $args);
        
        $this->info("Generating Model from Table: {$tableName}");
        $this->info("=" . str_repeat("=", 50));
        
        if (!$this->registry->has('db')) {
            $this->error("Database connection not available. Check your .env configuration.");
            return;
        }
        
        $db = $this->registry->get('db');
        
        // Check if table exists
        try {
            $result = $db->query("SHOW TABLES LIKE ?", [$tableName]);
            if (empty($result->rows)) {
                $this->error("Table '{$tableName}' does not exist in the database.");
                return;
            }
        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage());
            return;
        }
        
        // Get table columns
        $columns = $this->getTableColumns($tableName);
        if (empty($columns)) {
            $this->error("Could not retrieve columns for table '{$tableName}'");
            return;
        }
        
        // Generate model class name
        $className = $this->tableNameToClassName($tableName);
        $filename = PATH . 'app/model/' . $className . '.php';
        
        if (file_exists($filename) && !$force) {
            $this->error("Model already exists: {$filename}");
            $this->info("Use --force to overwrite");
            return;
        }
        
        // Detect relationships
        $foreignKeys = $this->detectForeignKeys($tableName);
        
        // Generate model
        $template = $this->generateOrmModelFromTable($className, $tableName, $columns, $foreignKeys);
        
        if (file_put_contents($filename, $template)) {
            $this->success("Model created: {$filename}");
            $this->info("");
            $this->info("Model Details:");
            $this->info("  Class: {$className}");
            $this->info("  Table: {$tableName}");
            $this->info("  Columns: " . count($columns));
            if (!empty($foreignKeys)) {
                $this->info("  Relationships: " . count($foreignKeys));
            }
        } else {
            $this->error("Failed to create model");
        }
    }
    
    public function makeModelsFromAllTables($args) {
        $this->info("Generating Models from All Tables");
        $this->info("=" . str_repeat("=", 50));
        
        if (!$this->registry->has('db')) {
            $this->error("Database connection not available. Check your .env configuration.");
            return;
        }
        
        $force = in_array('--force', $args);
        $db = $this->registry->get('db');
        
        // Get all tables
        try {
            $result = $db->query("SHOW TABLES");
            $tables = [];
            foreach ($result->rows as $row) {
                $tables[] = array_values($row)[0];
            }
        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage());
            return;
        }
        
        if (empty($tables)) {
            $this->warning("No tables found in the database.");
            return;
        }
        
        $this->info("Found " . count($tables) . " tables");
        $this->info("");
        
        $created = 0;
        $skipped = 0;
        
        foreach ($tables as $tableName) {
            // Skip migrations table
            if ($tableName === 'migrations') {
                continue;
            }
            
            $className = $this->tableNameToClassName($tableName);
            $filename = PATH . 'app/model/' . $className . '.php';
            
            if (file_exists($filename) && !$force) {
                $this->warning("Skipped (exists): {$className} ({$tableName})");
                $skipped++;
                continue;
            }
            
            $columns = $this->getTableColumns($tableName);
            $foreignKeys = $this->detectForeignKeys($tableName);
            $template = $this->generateOrmModelFromTable($className, $tableName, $columns, $foreignKeys);
            
            if (file_put_contents($filename, $template)) {
                $this->success("Created: {$className} ({$tableName})");
                $created++;
            } else {
                $this->error("Failed: {$className} ({$tableName})");
            }
        }
        
        $this->info("");
        $this->info("Summary:");
        $this->info("  Created: {$created}");
        $this->info("  Skipped: {$skipped}");
        
        if ($created > 0) {
            $this->success("Models generated successfully!");
        }
    }
    
    public function makeService($args) {
        if (empty($args[0])) {
            $this->error("Service name is required. Usage: easy make:service UserService");
            return;
        }
        
        $name = $args[0];
        $className = ucfirst($name);
        $filename = PATH . 'app/service/' . strtolower($name) . '.php';
        
        if (file_exists($filename)) {
            $this->error("Service already exists: {$filename}");
            return;
        }
        
        $template = $this->getServiceTemplate($className);
        
        if (file_put_contents($filename, $template)) {
            $this->success("Service created: {$filename}");
        } else {
            $this->error("Failed to create service");
        }
    }
    
    // ============================================================================
    // TEST COMMANDS
    // ============================================================================
    
    public function runTests($args) {
        $this->info("Running All Tests");
        $this->info("=" . str_repeat("=", 50));
        
        $testDir = PATH . 'tests/';
        if (!is_dir($testDir)) {
            $this->error("Tests directory not found: {$testDir}");
            return;
        }
        
        $testFiles = glob($testDir . '*Test.php');
        
        if (empty($testFiles)) {
            $this->warning("No test files found in {$testDir}");
            return;
        }
        
        $this->info("Found " . count($testFiles) . " test file(s)");
        $this->info("");
        
        $totalPassed = 0;
        $totalFailed = 0;
        
        foreach ($testFiles as $file) {
            $result = $this->runTestFile($file);
            $totalPassed += $result['passed'];
            $totalFailed += $result['failed'];
        }
        
        $this->showTestSummary($totalPassed, $totalFailed, 'All Tests');
    }
    
    public function runUnitTests($args) {
        $this->info("Running Unit Tests");
        $this->info("=" . str_repeat("=", 50));
        $this->info("Unit tests focus on individual components in isolation");
        $this->info("- Fast execution (milliseconds)");
        $this->info("- No external dependencies (database, APIs)");
        $this->info("- Pure logic validation");
        $this->info("");
        
        $testFiles = $this->findTestFiles('unit');
        
        if (empty($testFiles)) {
            $this->warning("No unit test files found");
            return;
        }
        
        $this->info("Found " . count($testFiles) . " unit test file(s)");
        $this->info("");
        
        $totalPassed = 0;
        $totalFailed = 0;
        
        foreach ($testFiles as $file) {
            $result = $this->runTestFile($file);
            $totalPassed += $result['passed'];
            $totalFailed += $result['failed'];
        }
        
        $this->showTestSummary($totalPassed, $totalFailed, 'Unit Tests');
    }
    
    public function runIntegrationTests($args) {
        $this->info("Running Integration Tests");
        $this->info("=" . str_repeat("=", 50));
        $this->info("Integration tests verify component interaction");
        $this->info("- Slower execution (seconds)");
        $this->info("- Uses real dependencies (database, files)");
        $this->info("- End-to-end workflow validation");
        $this->info("");
        
        $testFiles = $this->findTestFiles('integration');
        
        if (empty($testFiles)) {
            $this->warning("No integration test files found");
            return;
        }
        
        $this->info("Found " . count($testFiles) . " integration test file(s)");
        $this->info("");
        
        $totalPassed = 0;
        $totalFailed = 0;
        
        foreach ($testFiles as $file) {
            $result = $this->runTestFile($file);
            $totalPassed += $result['passed'];
            $totalFailed += $result['failed'];
        }
        
        $this->showTestSummary($totalPassed, $totalFailed, 'Integration Tests');
    }
    
    /**
     * Find test files based on type
     */
    private function findTestFiles($type = 'all') {
        $testDir = PATH . 'tests/';
        if (!is_dir($testDir)) {
            return [];
        }
        
        $allFiles = glob($testDir . '*Test.php');
        
        if ($type === 'all') {
            return $allFiles;
        }
        
        $filteredFiles = [];
        foreach ($allFiles as $file) {
            $testType = $this->determineTestType($file);
            if ($testType === $type) {
                $filteredFiles[] = $file;
            }
        }
        
        return $filteredFiles;
    }
    
    /**
     * Determine test type based on file content or naming convention
     */
    private function determineTestType($file) {
        $fileName = basename($file);
        
        // Check naming conventions first
        if (strpos($fileName, 'Unit') !== false || strpos($fileName, 'unit') !== false) {
            return 'unit';
        }
        
        if (strpos($fileName, 'Integration') !== false || strpos($fileName, 'integration') !== false) {
            return 'integration';
        }
        
        // Check file content for hints
        $content = file_get_contents($file);
        
        // Integration test indicators
        if (strpos($content, '$this->db') !== false ||
            strpos($content, 'database') !== false ||
            strpos($content, 'Integration') !== false ||
            strpos($content, '$this->load->model') !== false) {
            return 'integration';
        }
        
        // Default to unit test for simple assertion-based tests
        return 'unit';
    }
    
    /**
     * Run a single test file
     */
    private function runTestFile($testFile) {
        $className = basename($testFile, '.php');
        $fileName = basename($testFile);
        
        try {
            // Include the test file
            require_once $testFile;
            
            // Check if class exists
            if (!class_exists($className)) {
                $this->error("Running: {$fileName} [ERROR] Class {$className} not found");
                return ['passed' => 0, 'failed' => 1];
            }
            
            // Create test instance with registry
            $testInstance = new $className($this->registry);
            
            // Run the test
            if (method_exists($testInstance, 'run')) {
                $this->info("Running: {$fileName}");
                $success = $testInstance->run();
                
                if ($success) {
                    $this->success("  [PASSED]");
                    return ['passed' => 1, 'failed' => 0];
                } else {
                    $this->error("  [FAILED]");
                    return ['passed' => 0, 'failed' => 1];
                }
            } else {
                $this->error("Running: {$fileName} [ERROR] No run() method found");
                return ['passed' => 0, 'failed' => 1];
            }
            
        } catch (Exception $e) {
            $this->error("Running: {$fileName} [ERROR] " . $e->getMessage());
            return ['passed' => 0, 'failed' => 1];
        }
    }
    
    /**
     * Show test summary
     */
    private function showTestSummary($passed, $failed, $testType) {
        $total = $passed + $failed;
        
        $this->info("");
        $this->info("=" . str_repeat("=", 50));
        $this->info("{$testType} Summary:");
        $this->info("=" . str_repeat("=", 50));
        
        if ($total === 0) {
            $this->warning("No tests were executed.");
            return;
        }
        
        $this->info("Total Tests: {$total}");
        
        if ($passed > 0) {
            $this->success("Passed: {$passed}");
        }
        
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        } else {
            $this->info("Failed: {$failed}");
        }
        
        // Overall result
        $this->info("");
        if ($failed === 0) {
            $this->success("All {$testType} completed successfully!");
        } else {
            $this->error("Some {$testType} failed!");
            exit(1); // Exit with error code for CI/CD
        }
    }
    
    // ============================================================================
    // UTILITY COMMANDS
    // ============================================================================
    
    public function clearCache($args) {
        $this->info("Clearing Cache");
        $this->info("=" . str_repeat("=", 20));
        
        $cacheDir = PATH . 'storage/cache/';
        
        if (!is_dir($cacheDir)) {
            $this->warning("Cache directory not found: {$cacheDir}");
            return;
        }
        
        $files = glob($cacheDir . '*');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $cleared++;
                }
            }
        }
        
        $this->success("Cleared {$cleared} cache files");
    }
    
    public function serve($args) {
        $host = '127.0.0.1';
        $port = 8000;
        
        // Parse arguments
        foreach ($args as $arg) {
            if (strpos($arg, '--host=') === 0) {
                $host = substr($arg, 7);
            } elseif (strpos($arg, '--port=') === 0) {
                $port = (int)substr($arg, 7);
            }
        }
        
        $this->info("EasyAPP Development Server");
        $this->info("=" . str_repeat("=", 30));
        $this->success("Server starting at http://{$host}:{$port}");
        $this->info("Press Ctrl+C to stop");
        $this->info("");
        
        // Start PHP built-in server
        $command = "php -S {$host}:{$port} -t " . PATH;
        passthru($command);
    }
    
    // ============================================================================
    // HELP COMMANDS
    // ============================================================================
    
    public function showHelp($args = []) {
        $this->info("EasyAPP CLI Tool");
        $this->info("=" . str_repeat("=", 50));
        $this->info("");
        
        $this->info("USAGE:");
        $this->info("  easy <command> [options] [arguments]");
        $this->info("");
        
        $this->info("AVAILABLE COMMANDS:");
        $this->info("");
        
        $this->info("Migration Commands:");
        $this->info("  migrate                    Run all pending migrations");
        $this->info("  migrate --to=5             Migrate to specific version");
        $this->info("  migrate --dry-run          Preview migration changes");
        $this->info("  migrate:status             Show migration status");
        $this->info("  migrate:rollback <version> Rollback to specific version");
        $this->info("  migrate:create <name>      Create new migration");
        $this->info("");
        
        $this->info("Generator Commands:");
        $this->info("  make:controller <name>     Create new controller");
        $this->info("  make:model <name>          Create empty ORM model");
        $this->info("  make:model:table <table>   Generate model from database table");
        $this->info("  make:models                Generate models for all tables");
        $this->info("  make:service <name>        Create new service");
        $this->info("  make:migration <name>      Create new migration");
        $this->info("");
        
        $this->info("Test Commands:");
        $this->info("  test                       Run all tests");
        $this->info("  test:unit                  Run unit tests only");
        $this->info("  test:integration           Run integration tests only");
        $this->info("");
        
        $this->info("Utility Commands:");
        $this->info("  cache:clear                Clear application cache");
        $this->info("  serve                      Start development server");
        $this->info("  serve --host=0.0.0.0       Start server on all interfaces");
        $this->info("  serve --port=9000          Start server on custom port");
        $this->info("");
        
        $this->info("Help Commands:");
        $this->info("  help                       Show this help message");
        $this->info("  <command> --help           Show help for specific command");
        $this->info("  --version                  Show version information");
    }
    
    public function showCommandHelp($command) {
        switch ($command) {
            case 'migrate':
                $this->info("Migration Command Help");
                $this->info("Usage: easy migrate [options]");
                $this->info("");
                $this->info("Options:");
                $this->info("  --to=<version>    Migrate to specific version");
                $this->info("  --dry-run         Show what would be executed");
                break;
                
            case 'migrate:rollback':
                $this->info("Rollback Command Help");
                $this->info("Usage: easy migrate:rollback <version> [options]");
                $this->info("");
                $this->info("Arguments:");
                $this->info("  version           Target version to rollback to");
                $this->info("Options:");
                $this->info("  --dry-run         Show what would be executed");
                break;
                
            default:
                $this->showHelp();
        }
    }
    
    public function showVersion($args) {
        $this->info("EasyAPP CLI Tool v" . $this->version);
        $this->info("EasyAPP Framework v" . $this->registry->version);
        $this->info("Copyright (c) 2022, script-php.ro");
    }
    
    // ============================================================================
    // TEMPLATE GENERATORS
    // ============================================================================
    
    private function getControllerTemplate($className, $name) {
        return "<?php\n\n/**\n * {$className}\n * Generated by EasyAPP CLI\n */\n\nclass {$className} extends Controller {\n    \n    public function index() {\n        // Controller logic here\n        \$this->load->view('{$name}/index');\n    }\n    \n}\n";
    }
    
    private function getOrmModelTemplate($className) {
        $tableName = strtolower($className) . 's';
        return "<?php\n\nnamespace App\\Model;\n\nuse System\\Framework\\Orm;\n\n/**\n * {$className} Model\n * Generated by EasyAPP CLI\n */\nclass {$className} extends Orm {\n    \n    protected static \$table = '{$tableName}';\n    \n    protected static \$fillable = [\n        // Add fillable columns here\n    ];\n    \n    protected static \$casts = [\n        'id' => 'int',\n    ];\n    \n    // Add relationships here\n    \n}\n";
    }
    
    private function generateOrmModelFromTable($className, $tableName, $columns, $foreignKeys = []) {
        $fillable = [];
        $casts = [];
        $hidden = [];
        $hasSoftDelete = false;
        $hasTimestamps = false;
        
        foreach ($columns as $column) {
            $name = $column['Field'];
            $type = strtolower($column['Type']);
            
            // Skip auto-increment primary key
            if ($column['Key'] === 'PRI' && $column['Extra'] === 'auto_increment') {
                continue;
            }
            
            // Check for soft deletes
            if ($name === 'deleted_at') {
                $hasSoftDelete = true;
                continue;
            }
            
            // Check for timestamps
            if (in_array($name, ['created_at', 'updated_at'])) {
                $hasTimestamps = true;
                continue;
            }
            
            // Add to fillable
            $fillable[] = $name;
            
            // Detect type casting
            if (strpos($type, 'int') !== false) {
                $casts[$name] = 'int';
            } elseif (strpos($type, 'decimal') !== false || strpos($type, 'float') !== false || strpos($type, 'double') !== false) {
                $casts[$name] = 'float';
            } elseif (strpos($type, 'tinyint(1)') !== false || strpos($type, 'boolean') !== false) {
                $casts[$name] = 'bool';
            } elseif (strpos($type, 'json') !== false) {
                $casts[$name] = 'json';
            } elseif (strpos($type, 'date') !== false || strpos($type, 'time') !== false) {
                $casts[$name] = 'datetime';
            }
            
            // Detect hidden fields
            if (strpos($name, 'password') !== false || strpos($name, 'token') !== false || strpos($name, 'secret') !== false) {
                $hidden[] = $name;
            }
        }
        
        // Build template
        $template = "<?php\n\n";
        $template .= "namespace App\\Model;\n\n";
        $template .= "use System\\Framework\\Orm;\n\n";
        $template .= "/**\n";
        $template .= " * {$className} Model\n";
        $template .= " * Generated by EasyAPP CLI from table: {$tableName}\n";
        $template .= " */\n";
        $template .= "class {$className} extends Orm {\n\n";
        
        // Table name
        $template .= "    protected static \$table = '{$tableName}';\n";
        
        // Soft delete
        if ($hasSoftDelete) {
            $template .= "    protected static \$softDelete = true;\n";
        }
        
        // Timestamps
        $template .= "    protected static \$timestamps = " . ($hasTimestamps ? 'true' : 'false') . ";\n";
        $template .= "    \n";
        
        // Fillable
        if (!empty($fillable)) {
            $template .= "    protected static \$fillable = [\n";
            foreach ($fillable as $field) {
                $template .= "        '{$field}',\n";
            }
            $template .= "    ];\n    \n";
        }
        
        // Hidden
        if (!empty($hidden)) {
            $template .= "    protected static \$hidden = [\n";
            foreach ($hidden as $field) {
                $template .= "        '{$field}',\n";
            }
            $template .= "    ];\n    \n";
        }
        
        // Casts
        if (!empty($casts)) {
            $template .= "    protected static \$casts = [\n";
            $template .= "        'id' => 'int',\n";
            foreach ($casts as $field => $cast) {
                if ($field !== 'id') {
                    $template .= "        '{$field}' => '{$cast}',\n";
                }
            }
            $template .= "    ];\n";
        }
        
        // Relationships
        if (!empty($foreignKeys)) {
            $template .= "    \n";
            $template .= "    // ==================== RELATIONSHIPS ====================\n";
            $template .= "    \n";
            
            foreach ($foreignKeys as $fk) {
                $relatedClass = $this->tableNameToClassName($fk['referenced_table']);
                $methodName = lcfirst($relatedClass);
                $template .= "    /**\n";
                $template .= "     * Get the {$relatedClass} that this {$className} belongs to\n";
                $template .= "     */\n";
                $template .= "    public function {$methodName}() {\n";
                $template .= "        return \$this->belongsTo({$relatedClass}::class, '{$fk['column']}');\n";
                $template .= "    }\n";
                $template .= "    \n";
            }
        }
        
        // Add example relationship comments
        $template .= "    // Example relationships:\n";
        $template .= "    // public function posts() {\n";
        $template .= "    //     return \$this->hasMany(Post::class);\n";
        $template .= "    // }\n";
        $template .= "    \n";
        
        $template .= "}\n";
        
        return $template;
    }
    
    /**
     * Get table columns information
     */
    private function getTableColumns($tableName) {
        $db = $this->registry->get('db');
        try {
            $result = $db->query("DESCRIBE `{$tableName}`");
            return $result->rows;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Detect foreign keys in table
     */
    private function detectForeignKeys($tableName) {
        $db = $this->registry->get('db');
        $foreignKeys = [];
        
        try {
            $sql = "SELECT 
                        COLUMN_NAME as 'column',
                        REFERENCED_TABLE_NAME as 'referenced_table',
                        REFERENCED_COLUMN_NAME as 'referenced_column'
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL";
            
            $result = $db->query($sql, [CONFIG_DB_DATABASE, $tableName]);
            
            if (!empty($result->rows)) {
                foreach ($result->rows as $row) {
                    $foreignKeys[] = [
                        'column' => $row['column'],
                        'referenced_table' => $row['referenced_table'],
                        'referenced_column' => $row['referenced_column']
                    ];
                }
            }
        } catch (\Exception $e) {
            // Foreign key detection failed, continue without them
        }
        
        return $foreignKeys;
    }
    
    /**
     * Convert table name to class name
     */
    private function tableNameToClassName($tableName) {
        // Remove plural 's' if exists
        $singular = $tableName;
        if (substr($tableName, -1) === 's') {
            $singular = substr($tableName, 0, -1);
        }
        
        // Convert snake_case to PascalCase
        $parts = explode('_', $singular);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        return $className;
    }
    
    private function getModelTemplate($className, $name) {
        return "<?php\n\n/**\n * {$className}\n * Generated by EasyAPP CLI\n */\n\nclass {$className} extends Model {\n    \n    protected \$table = '{$name}';\n    \n    public function __construct() {\n        parent::__construct();\n    }\n    \n}\n";
    }
    
    private function getServiceTemplate($className) {
        return "<?php\n\n/**\n * {$className}\n * Generated by EasyAPP CLI\n */\n\nclass {$className} extends Service {\n    \n    public function __construct() {\n        parent::__construct();\n    }\n    \n}\n";
    }
    
    // ============================================================================
    // OUTPUT HELPERS
    // ============================================================================
    
    private function info($message) {
        echo $message . PHP_EOL;
    }
    
    private function success($message) {
        echo "\033[32m" . $message . "\033[0m" . PHP_EOL;
    }
    
    private function warning($message) {
        echo "\033[33m" . $message . "\033[0m" . PHP_EOL;
    }
    
    private function error($message) {
        echo "\033[31m" . $message . "\033[0m" . PHP_EOL;
    }
}