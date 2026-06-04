<?php

/**
 * Action: Register
 * Handle user registration
 */

session_start();
include '../functions/sanitasi.php';
include '../functions/secure_query.php';
include '../functions/generate_uuid.php';
include '../config.php';
include '../functions/log-sistem.php';


include '../functions/csrf.php';

// Cek apakah form di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $_SESSION['message'] = 'Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.';
        $_SESSION['message_type'] = 'error';
        header('Location: ../register.php');
        exit;
    }

    // Sanitasi input
    $fullname = sani($_POST['fullname']);
    $username = sani($_POST['username']);
    $email = sani($_POST['email']);
    $password = sani($_POST['password']);
    $confirmPassword = sani($_POST['confirm_password']);

    // Validasi input kosong
    if (empty($fullname) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['message'] = 'Semua field harus diisi!';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: empty fields');
        header('Location: ../register.php');
        exit;
    }

    // Validasi format email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Format email tidak valid!';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: invalid email format');
        header('Location: ../register.php');
        exit;
    }

    // Validasi username (hanya huruf, angka, underscore)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $_SESSION['message'] = 'Username hanya boleh mengandung huruf, angka, dan underscore!';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: invalid username format');
        header('Location: ../register.php');
        exit;
    }

    // Validasi panjang username
    if (strlen($username) < 3 || strlen($username) > 50) {
        $_SESSION['message'] = 'Username harus antara 3-50 karakter!';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: username length invalid');
        header('Location: ../register.php');
        exit;
    }

    // Validasi panjang password
    if (strlen($password) < 6) {
        $_SESSION['message'] = 'Password minimal 6 karakter!';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: password too short');
        header('Location: ../register.php');
        exit;
    }

    // Validasi password match
    if ($password !== $confirmPassword) {
        $_SESSION['message'] = 'Password dan konfirmasi password tidak cocok!';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: password mismatch');
        header('Location: ../register.php');
        exit;
    }

    // Cek apakah email sudah terdaftar
    $checkEmail = querySecure(
        $con,
        "SELECT id FROM users WHERE email = ?",
        [$email],
        's'
    );

    if ($checkEmail && mysqli_num_rows($checkEmail) > 0) {
        $_SESSION['message'] = 'Email sudah terdaftar! Silakan gunakan email lain.';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: email already registered');
        header('Location: ../register.php');
        exit;
    }

    // Cek apakah username sudah terdaftar
    $checkUsername = querySecure(
        $con,
        "SELECT id FROM users WHERE username = ?",
        [$username],
        's'
    );

    if ($checkUsername && mysqli_num_rows($checkUsername) > 0) {
        $_SESSION['message'] = 'Username sudah digunakan! Silakan pilih username lain.';
        $_SESSION['message_type'] = 'error';
        createLog($con, $email, 'Failed registration attempt: username already taken');
        header('Location: ../register.php');
        exit;
    }

    // Generate UUID untuk id
    $userId = generate_uuid();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert data user
    $result = executeSecure(
        $con,
        "INSERT INTO users (id, fullname, username, email, password, photo_profile, created_at) 
         VALUES (?, ?, ?, ?, ?, NULL, NOW())",
        [$userId, $fullname, $username, $email, $hashedPassword],
        'sssss'
    );
    createLog($con, $email, 'Successful registration');
    $_SESSION['message'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
    $_SESSION['message_type'] = 'success';
    header('Location: ../login.php');
    exit;
    // if ($result) {
    // } else {
    //     $_SESSION['message'] = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
    //     $_SESSION['message_type'] = 'error';
    //     header('Location: ../register.php');
    //     exit;
    // }
} else {
    // Jika diakses langsung tanpa POST, redirect ke register
    header('Location: ../register.php');
    exit;
}
