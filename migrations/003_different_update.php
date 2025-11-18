<?php

/**
 * Migration: test
 * Created: 2025-11-18 12:24:54
 */

use System\Framework\Migration;

class Migration_003_DifferentUpdate extends Migration {
    
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
