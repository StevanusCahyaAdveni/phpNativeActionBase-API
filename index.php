<?php
session_start();
include 'config.php';
include 'functions/sanitasi.php';
include 'functions/secure_query.php';
include 'functions/redirect.php';
include 'functions/auto-routing.php';
include 'functions/auto-cek-login-html.php';
include 'functions/csrf.php';

// Deteksi Request AJAX dari SPA Engine
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    ob_end_clean(); // Bersihkan buffer utama
    ob_start('csrf_auto_inject'); // Mulai buffer baru untuk injeksi CSRF
    include $content;
    $htmlContent = ob_get_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'title' => $textTitle,
        'html' => $htmlContent
    ]);
    exit;
}

// Mulai Output Buffering dengan callback auto-inject CSRF untuk request normal
ob_start('csrf_auto_inject');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $textTitle; ?> - <?= $_SESSION['admin']['fullname'] ?></title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.css">

    <link rel="stylesheet" href="assets/vendors/iconly/bold.css">

    <link rel="stylesheet" href="assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">
</head>

<body>
    <div id="app">
        <?php include 'sidebar.php'; ?>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block" style="max-width: 100px;">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading mb-0">
                <h3><?php echo $textTitle; ?></h3>
            </div>
            <div class="page-content">
                <?php include $content; ?>
            </div>

            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p><?= date('Y'); ?> &copy; Sadewa</p>
                    </div>
                    <div class="float-end">
                        <p>Crafted with <span class="text-danger"><i class="bi bi-heart"></i></span> by <a
                                href="">Stevanus Cahya Adveni</a></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

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

    <!-- Template Script -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="assets/js/pages/dashboard.js"></script>

    <script src="assets/js/main.js"></script>
    
    <!-- DataTables & SweetAlert2 -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Framework -->
    <script src="assets/js/upImage.js"></script>
    <script src="assets/js/spa.js"></script>
</body>

</html>