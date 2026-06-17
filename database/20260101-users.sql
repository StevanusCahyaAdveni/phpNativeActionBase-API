-- ============================================
-- Table: users
-- Description: Tabel untuk menyimpan data pengguna
-- Created: 2025-12-16
-- ============================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(36) NOT NULL COMMENT 'UUID primary key',
  `fullname` varchar(500) NOT NULL COMMENT 'Nama lengkap user',
  `username` varchar(500) NOT NULL COMMENT 'Username untuk login',
  `email` varchar(250) NOT NULL COMMENT 'Email user',
  `password` text NOT NULL COMMENT 'Password terenkripsi',
  `photo_profile` text DEFAULT NULL COMMENT 'Path foto profil user',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan akun',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel data pengguna';

-- ============================================
-- Sample Data (Optional)
-- Password: password123 (sudah di-hash dengan password_hash)
-- ============================================

-- INSERT INTO `users` (`id`, `fullname`, `username`, `email`, `password`, `photo_profile`, `created_at`) VALUES
-- (UUID(), 'Admin User', 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NOW()),
-- (UUID(), 'John Doe', 'johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NOW());

-- ============================================
-- Notes:
-- - id menggunakan UUID (36 karakter)
-- - email dan username harus unique
-- - password disimpan dalam bentuk hash (gunakan password_hash() di PHP)
-- - photo_profile nullable, bisa diisi path atau NULL
-- - created_at otomatis terisi saat insert
-- ============================================
