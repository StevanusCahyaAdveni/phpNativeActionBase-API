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