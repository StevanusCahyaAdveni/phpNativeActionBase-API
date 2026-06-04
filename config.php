<?php
// Deteksi URL Lengkap
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$fullUrl = $protocol . $domain . $requestUri;

// Konfigurasi Database (Local vs Server)
if (strpos($fullUrl, 'example.com') !== false) {
    // Konfigurasi Server / Production (Ubah sesuai kredensial server Anda)
    $dbHost = 'mysql.example.com';
    $dbUser = 'prod_user';
    $dbPass = 'prod_password';
    $dbName = 'prod_database';
} else {
    // Konfigurasi Local / Development
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'php_native_action';
}

// Definisikan konstanta agar kompatibel dengan kode lain
define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);

// Inisialisasi koneksi database
$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$con) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset untuk keamanan (mencegah SQL injection via charset)
mysqli_set_charset($con, "utf8mb4");

// Set timezone (sesuaikan dengan timezone Anda)
date_default_timezone_set('Asia/Jakarta');

$appName = "Little PHP Framework";

// Secret key untuk penandatanganan Custom JWT (REST API)
define('JWT_SECRET', 'super_secret_key_change_me_in_production_123456');
?>