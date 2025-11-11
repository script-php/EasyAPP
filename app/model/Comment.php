<?php

/**
* @package      Comment Model - Relationship Example
* @author       YoYo
* @copyright    Copyright (c) 2025, script-php.ro
* @link         https://script-php.ro
*/

namespace App\Model;

use System\Framework\Orm;

class Comment extends Orm {

    protected static $table = 'comments';
    protected static $softDelete = true;
    
    protected static $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'status'
    ];
    
    protected static $casts = [
        'id' => 'int',
        'post_id' => 'int',
        'user_id' => 'int',
        'parent_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // ==================== RELATIONSHIPS ====================
    
    /**
     * Get the post this comment belongs to
     */
    public function post() {
        return $this->belongsTo(Post::class, 'post_id');
    }
    
    /**
     * Get the user who wrote this comment
     */
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get the parent comment (for replies)
     */
    public function parent() {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
    
    /**
     * Get all replies to this comment
     */
    public function replies() {
        return $this->hasMany(Comment::class, 'parent_id');
    }
    
    // ==================== SCOPES ====================
    
    /**
     * Get only approved comments
     */
    public static function approved() {
        return static::query()->where('status', 'approved');
    }
    
    /**
     * Get only pending comments
     */
    public static function pending() {
        return static::query()->where('status', 'pending');
    }
    
    /**
     * Get top-level comments (not replies)
     */
    public static function topLevel() {
        return static::query()->whereNull('parent_id');
    }
    
    // ==================== EVENTS ====================
    
    /**
     * Before creating comment
     */
    protected function creating() {
        // Set default status
        if (empty($this->attributes['status'])) {
            $this->attributes['status'] = 'pending';
        }
    }
    
    /**
     * After creating comment
     */
    protected function created() {
        // You could:
        // - Send notification to post author
        // - Send notification to parent comment author (if reply)
        // - Update post comment count
    }
    
    // ==================== HELPERS ====================
    
    /**
     * Check if comment is approved
     */
    public function isApproved() {
        return $this->status === 'approved';
    }
    
    /**
     * Check if this is a reply
     */
    public function isReply() {
        return !empty($this->parent_id);
    }
    
    /**
     * Approve comment
     */
    public function approve() {
        $this->status = 'approved';
        return $this->save();
    }
    
    /**
     * Reject comment
     */
    public function reject() {
        $this->status = 'rejected';
        return $this->save();
    }

}
