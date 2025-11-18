<?php
/**
 * Migration: Test Diff-Based Execution
 * 
 * This migration demonstrates how diff-based execution works by making
 * various schema changes and showing which operations are executed.
 * 
 * Run this migration multiple times to see:
 * 1. First run: All changes executed
 * 2. Subsequent runs: Only actual changes made (re-ordering positioning, etc.)
 */

use System\Framework\Migration;

class Migration_004_TestDiffBasedExecution extends Migration {
    
    /**
     * Apply the migration
     */
    public function up(): void {
        // TODO: Implement schema changes
        Example:

        $this->tables->table('users_migration')
        ->tableComment('User account management')
        ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
        ->column('username')->type('VARCHAR(50)')->notNull(true)
        ->column('alternative_name')->type('VARCHAR(100)')->after('username')
        ->column('email')->type('VARCHAR(100)')->notNull(true)
        ->column('email_backup')->type('VARCHAR(100)')->notNull(true)
        ->column('password_hash')->type('VARCHAR(255)')->notNull(true)
        ->column('status')->enum(['active', 'inactive', 'suspended'])->default('active')
        ->column('created_at')->timestamp(true)
        ->column('updated_at')->timestamp(true)->onUpdate('CURRENT_TIMESTAMP')
        ->index('idx_username', ['username'])
        ->index('idx_email', ['email'])
        ->uniqueComposite(['username'], 'unique_username')
        ->uniqueComposite(['email'], 'unique_email')
        ->create();
        
    }
    
    /**
     * Rollback the migration  
     */
    public function down(): void {
        // Migration 002 only adds a column to users_migration (created in migration 001)
        // Define the table structure as it should be after rollback (without alternative_name)
        $this->tables->table('users_migration')
        ->tableComment('User account management')
        ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
        ->column('username')->type('VARCHAR(50)')->notNull(true)
        ->column('alternative_name')->type('VARCHAR(100)')->after('username')
        ->column('email')->type('VARCHAR(100)')->notNull(true)
        ->column('password_hash')->type('VARCHAR(255)')->notNull(true)
        ->column('status')->enum(['active', 'inactive', 'suspended'])->default('active')
        ->column('created_at')->timestamp(true)
        ->column('updated_at')->timestamp(true)->onUpdate('CURRENT_TIMESTAMP')
        ->index('idx_username', ['username'])
        ->index('idx_email', ['email'])
        ->uniqueComposite(['username'], 'unique_username')
        ->uniqueComposite(['email'], 'unique_email')
        ->create();
    }
}
