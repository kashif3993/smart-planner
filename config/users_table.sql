-- ============================================================
--  Smart Event Planner — Users Table
--  Run this in phpMyAdmin on database: smart_event_planner
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(100) NOT NULL,
  `email`         VARCHAR(191) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `profile_image` VARCHAR(255)          DEFAULT NULL,
  `role`          ENUM('user','admin')  DEFAULT 'user',
  `is_active`     TINYINT(1)            DEFAULT 1,
  `created_at`    TIMESTAMP             DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP             DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  INDEX `idx_role`      (`role`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
