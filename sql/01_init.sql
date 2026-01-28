-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `icon_filename` VARCHAR(255) DEFAULT NULL,
  `cover_filename` VARCHAR(255) DEFAULT NULL,
  `introduction` TEXT DEFAULT NULL,
  `birthday` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BBS entries table
CREATE TABLE IF NOT EXISTS `bbs_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `image_filename` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_bbs_entries_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User relationships (follow) table
CREATE TABLE IF NOT EXISTS `user_relationships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `follower_id` INT UNSIGNED NOT NULL,
  `followee_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_follower_followee` (`follower_id`, `followee_id`),
  KEY `idx_followee_id` (`followee_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_user_relationships_follower_id` 
    FOREIGN KEY (`follower_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_user_relationships_followee_id` 
    FOREIGN KEY (`followee_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `chk_no_self_follow` 
    CHECK (`follower_id` != `followee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Access logs table
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_access_logs_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample users (passwords are 'password123')
INSERT INTO `users` (`name`, `email`, `password`) VALUES
('田中太郎', 'tanaka@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('山田花子', 'yamada@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('佐藤次郎', 'sato@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert sample posts
INSERT INTO `bbs_entries` (`user_id`, `body`) VALUES
(1, 'はじめまして！よろしくお願いします。'),
(2, '今日はいい天気ですね！'),
(3, 'プログラミング楽しい！')
ON DUPLICATE KEY UPDATE `id`=`id`;
