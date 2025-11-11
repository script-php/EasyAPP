-- ============================================
-- ORM Example Database Schema
-- ============================================
-- This file contains table structures for the ORM examples
-- Run this to create tables for User, Post, Comment models

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin', 'moderator') DEFAULT 'user',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`),
  INDEX `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posts table
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `content` TEXT NOT NULL,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `views` INT(11) DEFAULT 0,
  `published_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_published_at` (`published_at`),
  INDEX `idx_deleted_at` (`deleted_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments table
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `post_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `parent_id` INT(11) NULL,
  `content` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_post_id` (`post_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_parent_id` (`parent_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_deleted_at` (`deleted_at`),
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Profiles table (one-to-one with users)
CREATE TABLE IF NOT EXISTS `profiles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL UNIQUE,
  `bio` TEXT NULL,
  `avatar` VARCHAR(255) NULL,
  `website` VARCHAR(255) NULL,
  `location` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tags table
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Post-Tag pivot table (many-to-many)
CREATE TABLE IF NOT EXISTS `post_tag` (
  `post_id` INT(11) NOT NULL,
  `tag_id` INT(11) NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  INDEX `idx_post_id` (`post_id`),
  INDEX `idx_tag_id` (`tag_id`),
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Roles table
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User-Role pivot table (many-to-many)
CREATE TABLE IF NOT EXISTS `role_user` (
  `user_id` INT(11) NOT NULL,
  `role_id` INT(11) NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_role_id` (`role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Sample Data (Optional)
-- ============================================

-- Insert sample users
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW(), NOW()),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, NOW(), NOW()),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, NOW(), NOW());

-- Insert sample posts
INSERT INTO `posts` (`user_id`, `title`, `slug`, `content`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'Getting Started with ORM', 'getting-started-with-orm', 'This is a comprehensive guide to using the ORM...', 'published', 150, NOW(), NOW(), NOW()),
(2, 'Building Your First App', 'building-your-first-app', 'Learn how to build your first application...', 'published', 89, NOW(), NOW(), NOW()),
(2, 'Advanced Relationships', 'advanced-relationships', 'Deep dive into model relationships...', 'draft', 0, NULL, NOW(), NOW());

-- Insert sample comments
INSERT INTO `comments` (`post_id`, `user_id`, `parent_id`, `content`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'Great article! Very helpful.', 'approved', NOW(), NOW()),
(1, 3, NULL, 'Thanks for sharing!', 'approved', NOW(), NOW()),
(1, 1, 1, 'Glad you found it useful!', 'approved', NOW(), NOW());

-- Insert sample tags
INSERT INTO `tags` (`name`, `slug`, `created_at`, `updated_at`) VALUES
('PHP', 'php', NOW(), NOW()),
('ORM', 'orm', NOW(), NOW()),
('Tutorial', 'tutorial', NOW(), NOW()),
('Beginner', 'beginner', NOW(), NOW());

-- Link posts to tags
INSERT INTO `post_tag` (`post_id`, `tag_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(2, 1), (2, 3), (2, 4);

-- Insert sample roles
INSERT INTO `roles` (`name`, `description`, `created_at`, `updated_at`) VALUES
('Administrator', 'Full system access', NOW(), NOW()),
('Moderator', 'Can moderate content', NOW(), NOW()),
('Editor', 'Can edit posts', NOW(), NOW());

-- Assign roles to users
INSERT INTO `role_user` (`user_id`, `role_id`) VALUES
(1, 1), -- Admin has Administrator role
(2, 3), -- John has Editor role
(3, 3); -- Jane has Editor role

-- ============================================
-- Verification Queries
-- ============================================
-- Run these to verify the setup

-- SELECT * FROM users;
-- SELECT * FROM posts;
-- SELECT * FROM comments;
-- SELECT * FROM tags;
-- SELECT * FROM post_tag;
-- SELECT * FROM roles;
-- SELECT * FROM role_user;
