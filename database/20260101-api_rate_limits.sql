CREATE TABLE IF NOT EXISTS `api_rate_limits` (
    `id` VARCHAR(36) NOT NULL PRIMARY KEY,
    `identifier` VARCHAR(100) NOT NULL COMMENT 'IP Address atau User ID',
    `endpoint` VARCHAR(100) NOT NULL COMMENT 'Nama endpoint (hal)',
    `request_count` INT NOT NULL DEFAULT 1,
    `reset_time` INT NOT NULL COMMENT 'Unix timestamp kapan limit di-reset'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
