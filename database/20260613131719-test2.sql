-- ============================================
-- Table: test2
-- Description: Auto-generated table structure
-- Generated: 2026-06-13 13:17:19
-- ============================================

CREATE TABLE IF NOT EXISTS `test2` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `test2` VARCHAR(255) NOT NULL COMMENT 'test2',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test2` (`id`, `test2`) VALUES
-- ('sample-uuid-here', 'Sample test2');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
