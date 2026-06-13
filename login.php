<?php 
if(session_status() === PHP_SESSION_NONE) session_start();
include 'functions/csrf.php'; 
ob_start('csrf_auto_inject'); 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.css">

    <link rel="stylesheet" href="assets/vendors/iconly/bold.css">

    <link rel="stylesheet" href="assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(90deg, #2d499d, #3f5491);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(90deg, #2d499d, #3f5491);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-body {
            padding: 2.5rem;
            background: white;
        }

        .btn-login {
            background: linear-gradient(90deg, #2d499d, #3f5491);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background: linear-gradient(90deg, #243a7d, #324076);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(45, 73, 157, 0.4);
        }

        .form-control:focus {
            border-color: #2d499d;
            box-shadow: 0 0 0 0.2rem rgba(45, 73, 157, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <h3 class="mb-1" style="color: white;">Log-in.</h3>
                        <p class="mb-0">Sign in to your account</p>
                    </div>
                    <div class="login-body">
                        <?php

                        // Redirect jika sudah login
                        if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
                            header('Location: index.php');
                            exit;
                        }

                        if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                                <?= $_SESSION['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                        <?php endif; ?>

                        <form action="actions/login.php" method="POST" id="loginForm" class="ajax-form">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Enter your email" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    Sign In
                                </button>
                            </div>

                            <div class="login-footer">
                                <small>
                                    Don't have an account? <a href="register.php" class="text-decoration-none">Sign Up</a>
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?= date('Y') ?> Sadewa. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });

        // Check if need to clear localStorage (when login failed)
        <?php if (isset($_SESSION['clear_remember'])): ?>
            localStorage.removeItem('remember_email');
            localStorage.removeItem('remember_password');
            <?php unset($_SESSION['clear_remember']); ?>
        <?php endif; ?>

        // Auto-login jika ada localStorage
        window.addEventListener('DOMContentLoaded', async function() {
            const savedEmail = localStorage.getItem('remember_email');
            const savedPassword = localStorage.getItem('remember_password');

            if (savedEmail && savedPassword) {
                // Ada localStorage, coba hit API untuk auto-login
                try {
                    const response = await fetch('actions/loginauto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            email: savedEmail,
                            password: savedPassword
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Login berhasil via API, redirect ke index.php
                        window.location.href = 'index.php';
                    } else {
                        // Login gagal, clear localStorage
                        if (result.clear_storage) {
                            localStorage.removeItem('remember_email');
                            localStorage.removeItem('remember_password');
                        }
                        // Tetap di halaman login, tampilkan form
                        console.log('Auto-login failed:', result.message);
                    }
                } catch (error) {
                    // Error saat fetch API, tetap tampilkan form login
                    console.error('Auto-login error:', error);
                }
            }
            // Jika tidak ada localStorage, form login akan tampil normal
        });

        // Save or clear credentials to/from localStorage on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const rememberCheckbox = document.getElementById('remember');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (rememberCheckbox.checked) {
                // Save credentials to localStorage
                localStorage.setItem('remember_email', email);
                localStorage.setItem('remember_password', password);
            } else {
                // Clear credentials from localStorage
                localStorage.removeItem('remember_email');
                localStorage.removeItem('remember_password');
            }
        });
    </script>

    <!-- Bootstrap Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <div id="spaToast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="spaToastMessage">
                    Message here
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="assets/js/pages/dashboard.js"></script>

    <script src="assets/js/main.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/spa.js"></script>
</body>

</html>