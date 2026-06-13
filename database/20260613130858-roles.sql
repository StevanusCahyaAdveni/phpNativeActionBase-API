-- ============================================
-- Table: roles
-- Description: Auto-generated table structure
-- Generated: 2026-06-13 13:08:58
-- ============================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `role_name` VARCHAR(255) NOT NULL COMMENT 'Nama Role',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `roles` (`id`, `role_name`) VALUES
-- ('sample-uuid-here', 'Sample Nama Role');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
