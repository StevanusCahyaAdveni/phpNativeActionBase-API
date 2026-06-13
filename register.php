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
    <title>Register - Create Account</title>

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
            padding: 2rem 0;
        }

        .register-card {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            overflow: hidden;
        }

        .register-header {
            background: linear-gradient(90deg, #2d499d, #3f5491);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .register-body {
            padding: 2.5rem;
            background: white;
        }

        .btn-register {
            background: linear-gradient(90deg, #2d499d, #3f5491);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-register:hover {
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

        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="register-card">
                    <div class="register-header">
                        <h3 class="mb-1" style="color: white;">Create Account</h3>
                        <p class="mb-0">Sign up to get started</p>
                    </div>
                    <div class="register-body">
                        <?php
                        if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                                <?= $_SESSION['message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                        <?php endif; ?>

                        <form action="actions/register.php" method="POST" id="registerForm" class="ajax-form">
                            <div class="mb-3">
                                <label for="fullname" class="form-label">
                                    Full Name
                                </label>
                                <input type="text" class="form-control" id="fullname" name="fullname"
                                    placeholder="Enter your full name" required autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Choose a username" required>
                                <small class="text-muted">Only letters, numbers, and underscore allowed</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="Enter your email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Create a password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2" id="passwordStrength"></div>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    Confirm Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                        placeholder="Confirm your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                                    </button>
                                </div>
                                <small id="passwordMatch" class="text-muted"></small>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-decoration-none">Terms & Conditions</a>
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-register">
                                    Create Account
                                </button>
                            </div>

                            <div class="register-footer">
                                <small>
                                    Already have an account? <a href="login.php" class="text-decoration-none">Sign In</a>
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

        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const toggleIcon = document.getElementById('toggleConfirmIcon');

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

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;

            if (password.length >= 6) strength += 25;
            if (password.length >= 10) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/\d/.test(password)) strength += 25;

            strengthBar.style.width = strength + '%';

            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        });

        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');

            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchText.textContent = '✓ Passwords match';
                    matchText.className = 'text-success';
                } else {
                    matchText.textContent = '✗ Passwords do not match';
                    matchText.className = 'text-danger';
                }
            } else {
                matchText.textContent = '';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (typeof showToast !== 'undefined') showToast('Passwords do not match!', 'error');
                else alert('Passwords do not match!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (typeof showToast !== 'undefined') showToast('Password must be at least 6 characters!', 'error');
                else alert('Password must be at least 6 characters!');
                return false;
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
    <script src="assets/js/main.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/spa.js"></script>
</body>

</html>