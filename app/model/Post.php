<?php

/**
* @package      Post Model - Relationship Example
* @author       YoYo
* @copyright    Copyright (c) 2025, script-php.ro
* @link         https://script-php.ro
*/

namespace App\Model;

use System\Framework\Orm;

class Post extends Orm {

    protected static $table = 'posts';
    protected static $softDelete = true; // Enable soft deletes
    
    protected static $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'status',
        'views',
        'published_at'
    ];
    
    protected static $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'views' => 'int',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    // ==================== RELATIONSHIPS ====================
    
    /**
     * Get the author (User) of this post
     */
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get all comments for this post
     */
    public function comments() {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Get approved comments only
     */
    public function approvedComments() {
        return $this->hasMany(Comment::class)
            ->where('status', 'approved');
    }
    
    /**
     * Get tags for this post (many-to-many)
     */
    public function tags() {
        return $this->belongsToMany(Tag::class, 'post_tag')->get();
    }
    
    // ==================== SCOPES ====================
    
    /**
     * Get only published posts
     */
    public static function published() {
        return static::query()
            ->where('status', 'published')
            ->whereNotNull('published_at');
    }
    
    /**
     * Get popular posts (views > 1000)
     */
    public static function popular() {
        return static::query()->where('views', '>', 1000);
    }
    
    /**
     * Get recent posts
     */
    public static function recent($days = 7) {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return static::query()->where('created_at', '>', $date);
    }
    
    // ==================== EVENTS ====================
    
    /**
     * Before creating post
     */
    protected function creating() {
        // Auto-generate slug from title
        if (empty($this->attributes['slug']) && !empty($this->attributes['title'])) {
            $this->attributes['slug'] = $this->generateSlug($this->attributes['title']);
        }
        
        // Set default status
        if (empty($this->attributes['status'])) {
            $this->attributes['status'] = 'draft';
        }
        
        // Initialize views
        if (!isset($this->attributes['views'])) {
            $this->attributes['views'] = 0;
        }
    }
    
    /**
     * After creating post
     */
    protected function created() {
        // You can add logic here like:
        // - Clear cache
        // - Send notifications
        // - Index for search
    }
    
    /**
     * Before updating post
     */
    protected function updating() {
        // Update slug if title changed
        if (isset($this->attributes['title']) && 
            $this->attributes['title'] !== $this->original['title']) {
            $this->attributes['slug'] = $this->generateSlug($this->attributes['title']);
        }
    }
    
    /**
     * Before deleting post
     */
    protected function deleting() {
        // Delete related comments when post is deleted
        // Note: Only fires on instance->delete(), not query->delete()
        if ($this->exists) {
            Comment::where('post_id', $this->id)->delete();
        }
    }
    
    // ==================== HELPERS ====================
    
    /**
     * Generate URL-friendly slug
     */
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
    
    /**
     * Check if post is published
     */
    public function isPublished() {
        return $this->status === 'published' && 
               !empty($this->published_at) && 
               strtotime($this->published_at) <= time();
    }
    
    /**
     * Get excerpt from content
     */
    public function getExcerpt($length = 150) {
        $text = strip_tags($this->content);
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
    
    /**
     * Get reading time in minutes
     */
    public function getReadingTime() {
        $words = str_word_count(strip_tags($this->content));
        $minutes = ceil($words / 200); // Average reading speed: 200 words/min
        return $minutes;
    }

}
