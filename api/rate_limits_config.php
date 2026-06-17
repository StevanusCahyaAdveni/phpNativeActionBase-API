<?php
/**
 * Konfigurasi Rate Limit API
 * Format: 'endpoint_hal' => ['limit' => X, 'window' => Y]
 * - limit: batas jumlah request
 * - window: rentang waktu dalam detik
 */
$apiRateLimits = [
    'auth' => ['limit' => 5, 'window' => 60],        // 5 request per menit untuk login
    'users' => ['limit' => 30, 'window' => 60],      // 30 request per menit untuk endpoint users
    'default' => ['limit' => 60, 'window' => 60]     // Default: 60 request per menit untuk endpoint lain
];
?>
