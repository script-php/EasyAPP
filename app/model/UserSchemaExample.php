<?php

namespace App\Model;

use System\Framework\Orm;

/**
 * Example demonstrating schema inspection features
 */
class UserSchemaExample extends Orm {

    protected static $table = 'users';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;

    /**
     * Example 1: Display table schema
     */
    public static function displaySchema() {
        echo "=== Table Schema ===<br><br>";
        
        $schema = static::getTableSchema();
        
        echo "Table: {$schema['table']}<br>";
        echo "Primary Key: {$schema['primaryKey']}<br>";
        echo "Columns: " . count($schema['columns']) . "<br>";
        echo "Indexes: " . count($schema['indexes']) . "<br>";
        echo "Foreign Keys: " . count($schema['foreignKeys']) . "<br><br>";
        
        return $schema;
    }

    /**
     * Example 2: List all columns with details
     */
    public static function listColumns() {
        echo "=== Columns ===<br><br>";
        
        $columns = static::getColumns();
        
        foreach ($columns as $column) {
            echo "Column: {$column['name']}<br>";
            echo "  Type: {$column['fullType']}<br>";
            echo "  PHP Type: {$column['phpType']}<br>";
            echo "  Nullable: " . ($column['nullable'] ? 'YES' : 'NO') . "<br>";
            echo "  Default: " . ($column['defaultValue'] ?? 'NULL') . "<br>";
            
            if ($column['isPrimaryKey']) {
                echo "  ★ PRIMARY KEY<br>";
            }
            if ($column['isUnique']) {
                echo "  ★ UNIQUE<br>";
            }
            if ($column['isAutoIncrement']) {
                echo "  ★ AUTO INCREMENT<br>";
            }
            if ($column['comment']) {
                echo "  Comment: {$column['comment']}<br>";
            }
            
            echo "<br>";
        }
    }

    /**
     * Example 3: Check specific columns
     */
    public static function checkColumns() {
        echo "=== Column Checks ===<br><br>";
        
        // Check if columns exist
        $columnsToCheck = ['id', 'email', 'name', 'password', 'deleted_at'];
        
        foreach ($columnsToCheck as $columnName) {
            $exists = static::hasColumn($columnName);
            echo "{$columnName}: " . ($exists ? '✓ EXISTS' : '✗ MISSING') . "<br>";
            
            if ($exists) {
                $column = static::getColumn($columnName);
                echo "  → Type: {$column['type']}, Nullable: " . 
                     ($column['nullable'] ? 'YES' : 'NO') . "<br>";
            }
        }
        
        echo "<br>";
    }

    /**
     * Example 4: Display indexes
     */
    public static function listIndexes() {
        echo "=== Indexes ===<br><br>";
        
        $indexes = static::getIndexes();
        
        if (empty($indexes)) {
            echo "No indexes found.<br><br>";
            return;
        }
        
        foreach ($indexes as $index) {
            echo "Index: {$index['name']}<br>";
            echo "  Columns: " . implode(', ', $index['columns']) . "<br>";
            echo "  Type: {$index['type']}<br>";
            echo "  Unique: " . ($index['unique'] ? 'YES' : 'NO') . "<br>";
            
            if ($index['primary']) {
                echo "  ★ PRIMARY KEY INDEX<br>";
            }
            
            echo "<br>";
        }
    }

    /**
     * Example 5: Display foreign keys
     */
    public static function listForeignKeys() {
        echo "=== Foreign Keys ===<br><br>";
        
        $foreignKeys = static::getForeignKeys();
        
        if (empty($foreignKeys)) {
            echo "No foreign keys found.<br><br>";
            return;
        }
        
        foreach ($foreignKeys as $fk) {
            echo "Foreign Key: {$fk['name']}<br>";
            echo "  Column: " . implode(', ', $fk['columns']) . "<br>";
            echo "  References: {$fk['referencedTable']}(" . 
                 implode(', ', $fk['referencedColumns']) . ")<br>";
            echo "  On Update: {$fk['onUpdate']}<br>";
            echo "  On Delete: {$fk['onDelete']}<br><br>";
        }
    }

    /**
     * Example 6: Display table statistics
     */
    public static function showStats() {
        echo "=== Table Statistics ===<br><br>";
        
        $stats = static::getTableStats();
        
        if (empty($stats)) {
            echo "Statistics not available.<br><br>";
            return;
        }
        
        echo "Rows: " . number_format($stats['rowCount']) . "<br>";
        echo "Average Row Length: {$stats['avgRowLength']} bytes<br>";
        echo "Data Size: {$stats['dataSizeMB']} MB<br>";
        echo "Index Size: {$stats['indexSizeMB']} MB<br>";
        echo "Total Size: {$stats['totalSizeMB']} MB<br>";
        
        if (isset($stats['autoIncrement'])) {
            echo "Next Auto Increment: {$stats['autoIncrement']}<br>";
        }
        
        echo "Storage Engine: {$stats['engine']}<br>";
        echo "Collation: {$stats['collation']}<br>";
        
        if (isset($stats['createdAt'])) {
            echo "Created: {$stats['createdAt']}<br>";
        }
        if (isset($stats['updatedAt'])) {
            echo "Updated: {$stats['updatedAt']}<br>";
        }
        
        echo "<br>";
    }

    /**
     * Example 7: Generate validation rules from schema
     */
    public static function generateValidationRules() {
        echo "=== Auto-Generated Validation Rules ===<br><br>";
        
        $columns = static::getColumns();
        $rules = [];
        
        foreach ($columns as $column) {
            // Skip auto-increment and timestamp columns
            if ($column['isAutoIncrement'] || 
                in_array($column['name'], ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            $ruleString = '';
            
            // Required if not nullable
            if (!$column['nullable']) {
                $ruleString .= 'required';
            }
            
            // Type-specific rules
            switch ($column['type']) {
                case 'varchar':
                case 'text':
                    if ($column['maxLength']) {
                        $ruleString .= ($ruleString ? '|' : '') . "maxLength:{$column['maxLength']}";
                    }
                    if ($column['name'] === 'email') {
                        $ruleString .= '|email|unique';
                    }
                    break;
                    
                case 'int':
                case 'bigint':
                case 'smallint':
                    $ruleString .= ($ruleString ? '|' : '') . 'integer';
                    if ($column['name'] === 'age') {
                        $ruleString .= '|min:0|max:150';
                    }
                    break;
                    
                case 'decimal':
                case 'float':
                case 'double':
                    $ruleString .= ($ruleString ? '|' : '') . 'float';
                    break;
                    
                case 'date':
                    $ruleString .= ($ruleString ? '|' : '') . 'date';
                    break;
            }
            
            if ($ruleString) {
                $rules[] = "        ['{$column['name']}', '{$ruleString}'],";
                echo "Column '{$column['name']}': {$ruleString}<br>";
            }
        }
        
        echo "<br>";
        echo "// Copy this to your model's rules() method:<br>";
        echo "public function rules() {<br>";
        echo "    return [<br>";
        echo implode("<br>", $rules) . "<br>";
        echo "    ];<br>";
        echo "}<br><br>";
    }

    /**
     * Example 8: Generate API documentation
     */
    public static function generateApiDoc() {
        echo "=== API Documentation ===<br><br>";
        
        $table = static::getTable();
        $columns = static::getColumns();
        
        echo "# {$table} Resource<br><br>";
        echo "## Endpoints<br><br>";
        echo "- GET    /api/{$table}      - List all {$table}<br>";
        echo "- GET    /api/{$table}/{id} - Get single {$table}<br>";
        echo "- POST   /api/{$table}      - Create {$table}<br>";
        echo "- PUT    /api/{$table}/{id} - Update {$table}<br>";
        echo "- DELETE /api/{$table}/{id} - Delete {$table}<br><br>";
        
        echo "## Fields<br><br>";
        echo "| Field | Type | Required | Description |<br>";
        echo "|-------|------|----------|-------------|<br>";
        
        foreach ($columns as $column) {
            $required = !$column['nullable'] && !$column['isAutoIncrement'] ? 'Yes' : 'No';
            $description = $column['comment'] ?: '-';
            
            echo "| {$column['name']} ";
            echo "| {$column['phpType']} ";
            echo "| {$required} ";
            echo "| {$description} |<br>";
        }
        
        echo "<br>";
    }

    /**
     * Example 9: Full schema report
     */
    public static function fullReport() {
        echo "<br>";
        echo "╔════════════════════════════════════════════════════════════════╗<br>";
        echo "║            COMPLETE SCHEMA INSPECTION REPORT                  ║<br>";
        echo "╚════════════════════════════════════════════════════════════════╝<br>";
        echo "<br>";
        
        static::displaySchema();
        static::showStats();
        static::listColumns();
        static::listIndexes();
        static::listForeignKeys();
        static::generateValidationRules();
        static::generateApiDoc();
        
        echo "Report completed!<br><br>";
    }
}

// ==============================================================
// USAGE EXAMPLES
// ==============================================================

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "<br>";
    echo "Schema Inspection Examples<br>";
    echo "==========================<br><br>";
    
    // Example 1: Full report
    echo "Example 1: Full Schema Report<br>";
    echo "------------------------------<br>";
    UserSchemaExample::fullReport();
    
    // Example 2: Quick column check
    echo "<br>Example 2: Quick Column Check<br>";
    echo "------------------------------<br>";
    UserSchemaExample::checkColumns();
    
    // Example 3: Just statistics
    echo "<br>Example 3: Table Statistics Only<br>";
    echo "--------------------------------<br>";
    UserSchemaExample::showStats();
    
    // Example 4: Generate validation rules
    echo "<br>Example 4: Auto-Generate Validation<br>";
    echo "-----------------------------------<br>";
    UserSchemaExample::generateValidationRules();
    
    // Example 5: API documentation
    echo "<br>Example 5: API Documentation<br>";
    echo "----------------------------<br>";
    UserSchemaExample::generateApiDoc();
    
    // Example 6: Check if column exists before using
    echo "<br>Example 6: Safe Column Access<br>";
    echo "-----------------------------<br>";
    if (UserSchemaExample::hasColumn('email')) {
        $column = UserSchemaExample::getColumn('email');
        echo "Email column found:<br>";
        echo "  Type: {$column['type']}<br>";
        echo "  Max Length: {$column['maxLength']}<br>";
        echo "  Unique: " . ($column['isUnique'] ? 'YES' : 'NO') . "<br>";
    }
    
    echo "<br>";
}
