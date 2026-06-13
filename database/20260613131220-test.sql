-- ============================================
-- Table: test
-- Description: Auto-generated table structure
-- Generated: 2026-06-13 13:12:20
-- ============================================

CREATE TABLE IF NOT EXISTS `test` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `test` VARCHAR(255) NOT NULL COMMENT 'test',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test` (`id`, `test`) VALUES
-- ('sample-uuid-here', 'Sample test');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
