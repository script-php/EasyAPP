<?php

/**
 * Migration: Create Initial User System
 * Created: 2025-01-01 00:00:00
 */

use System\Framework\Migration;

class Migration_001_CreateInitialUserSystem extends Migration {
    
    /**
     * Apply the migration
     */
    public function up(): void {
        // Create users table
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
            
        // Create user_profiles table
        $this->tables->table('user_profiles_migration')
            ->column('id')->type('INT(11)')->autoIncrement(true)->primary('`id`')
            ->column('user_id')->type('INT(11)')->notNull(true)
            ->column('first_name')->type('VARCHAR(50)')
            ->column('last_name')->type('VARCHAR(50)')
            ->column('phone')->type('VARCHAR(20)')
            ->column('avatar')->type('VARCHAR(255)')
            ->column('bio')->text()
            ->column('metadata')->json()
            ->foreign('fk_user_profiles_user_id_test', 'user_id', 'users_migration', 'id', true) // CASCADE delete
            ->index('idx_user', ['user_id'])
            ->create();
            
        $this->log('Created initial user system with users_migration and user_profiles_migration tables');
    }
    
    /**
     * Rollback the migration  
     */
    public function down(): void {
        // Drop tables in reverse dependency order
        $this->tables->drop('user_profiles_migration');
        $this->tables->drop('users_migration');
        
        $this->log('Dropped user system tables');
    }
    
    /**
     * Get migration description
     */
    public function getDescription(): string {
        return 'Create Initial User System - users_migration and user_profiles_migration tables';
    }
    
    /**
     * Check dependencies
     */
    public function checkDependencies(string $direction = 'up'): bool {
        if ($direction === 'up') {
            // Ensure we don't already have users table
            return !$this->tableExists('users_migration');
        } else {
            // Ensure tables exist before dropping
            return $this->tableExists('users_migration') && $this->tableExists('user_profiles_migration');
        }
    }
}