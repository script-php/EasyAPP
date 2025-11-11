<?php

/**
* @package      User Model - ORM Example
* @author       YoYo
* @copyright    Copyright (c) 2025, script-php.ro
* @link         https://script-php.ro
*/

namespace App\Model;

use System\Framework\Orm;

class User extends Orm {

    /**
     * Table name (optional - auto-detected as 'users')
     */
    protected static $table = 'users';

    /**
     * Primary key (default is 'id')
     */
    protected static $primaryKey = 'id';

    /**
     * Enable/disable timestamps (default is true)
     */
    protected static $timestamps = true;

    /**
     * Fillable columns (whitelist for mass assignment)
     */
    protected static $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role'
    ];

    /**
     * Guarded columns (blacklist - alternative to fillable)
     */
    protected static $guarded = ['id'];

    /**
     * Hidden columns (excluded from toArray/toJson)
     */
    protected static $hidden = ['password'];

    /**
     * Attribute casting
     */
    protected static $casts = [
        'id' => 'int',
        'status' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Example: Accessor - get full name
     * Define method: get{AttributeName}Attribute()
     */
    public function getFullNameAttribute() {
        return $this->attributes['name'] ?? 'Guest';
    }

    /**
     * Example: Mutator - hash password before saving
     * Define method: set{AttributeName}Attribute($value)
     */
    public function setPasswordAttribute($value) {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Example: Check if user is admin
     */
    public function isAdmin() {
        return $this->role === 'admin';
    }

    /**
     * Example: Scope - get only active users
     * You can call this as: User::active()->get()
     */
    public static function active() {
        return static::query()->where('status', 1);
    }

    // ==================== RELATIONSHIPS ====================
    
    /**
     * Get all posts by this user
     */
    public function posts() {
        return $this->hasMany(Post::class);
    }
    
    /**
     * Get all comments by this user
     */
    public function comments() {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Get user profile (one-to-one)
     */
    public function profile() {
        return $this->hasOne(Profile::class);
    }
    
    /**
     * Get user roles (many-to-many)
     */
    public function roles() {
        return $this->belongsToMany(Role::class, 'role_user')->get();
    }

}
