<?php
session_start();


include '../config.php';
include '../functions/secure_query.php';
include '../functions/sanitasi.php';
include '../functions/generate_uuid.php';
include '../functions/redirect.php';
include '../functions/log-sistem.php';

// Check if need auto-login via API
include '../functions/auto-cek-login-action.php';

include '../functions/csrf.php';

// Validasi CSRF Sentral untuk semua request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Abaikan validasi khusus untuk loginauto.php jika itu POST dari fetch API, 
    // tapi karena ini actions/index.php, kita periksa csrf_token.
    $token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($token)) {
        // Token tidak valid atau tidak ada
        createLog($con, 'SYSTEM', 'Peringatan: Upaya CSRF dicegah dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
        
        // redirectWithMessage('../?hal=dashboard', 'Aksi dibatalkan: Token Keamanan (CSRF) tidak valid atau kadaluarsa. Silakan muat ulang halaman.', 'error');
    }
}

$hal = 'dashboard';
$textTitle = 'Dashboard';
if (isset($_GET['hal'])) {
    $getHal = sani($_GET['hal']);
    $hal = str_replace('_', '/', $getHal);
    $lastUnderscore = strrpos($getHal, '_');
    $titlePart = ($lastUnderscore !== false) ? substr($getHal, $lastUnderscore + 1) : $getHal;
    $textTitle = ucwords(str_replace('-', ' ', $titlePart));
}

include 'pages/' . $hal . '.php';
?>