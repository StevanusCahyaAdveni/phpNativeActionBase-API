<?php
/**
 * CSRF Protection Helper
 * Menangani pembuatan token, validasi, dan penyisipan otomatis menggunakan Output Buffering.
 */

// 1. Generate CSRF Token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // Gunakan random_bytes agar sangat aman (CSPRNG)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 2. Output Buffering Callback untuk Auto-Inject
function csrf_auto_inject($buffer) {
    // Abaikan jika buffer kosong
    if (empty($buffer)) {
        return $buffer;
    }
    
    // Pastikan token sudah di-generate
    $token = generate_csrf_token();
    
    // Siapkan tag HTML input tersembunyi
    $hiddenInput = "\n    <input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">\n";
    
    // Cari tag pembuka form dengan method post (case-insensitive)
    // Regex ini menangkap <form ... method="post" ... > atau method='post' atau method=post
    $pattern = '/(<form\b[^>]*?method\s*=\s*(["\']?)post\2[^>]*>)/i';
    
    // Sisipkan $hiddenInput tepat setelah tag form pembuka
    $injectedBuffer = preg_replace($pattern, "$1" . $hiddenInput, $buffer);
    
    return $injectedBuffer;
}

// 3. Validasi Token
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    // Gunakan hash_equals untuk mencegah timing attack
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Pastikan token dibuat jika fungsi ini di-include
generate_csrf_token();

?>
