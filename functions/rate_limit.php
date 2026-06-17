<?php
/**
 * Fungsi Rate Limiting untuk API
 * Mengecek apakah request dari identifier (IP/User) ke endpoint tertentu melebihi limit.
 * 
 * @param mysqli $con Koneksi database
 * @param string $endpoint Nama endpoint (hal)
 * @param string $identifier IP address atau User ID
 * @param int $limit Maksimal jumlah request
 * @param int $window Waktu reset dalam detik
 * @return bool True jika diizinkan, False jika limit tercapai
 */
function checkApiRateLimit($con, $endpoint, $identifier, $limit, $window) {
    $now = time();

    // Pastikan function generate_uuid dan executeSecure/querySecure sudah diinclude sebelum memanggil ini
    $result = querySecure($con, "SELECT * FROM api_rate_limits WHERE identifier = ? AND endpoint = ?", [$identifier, $endpoint], 'ss');
    $record = mysqli_fetch_assoc($result);

    if (!$record) {
        // Belum ada record, buat baru
        $id = generate_uuid();
        $resetTime = $now + $window;
        executeSecure($con, 
            "INSERT INTO api_rate_limits (id, identifier, endpoint, request_count, reset_time) VALUES (?, ?, ?, 1, ?)", 
            [$id, $identifier, $endpoint, $resetTime], 
            'sssi'
        );
        return true;
    } else {
        if ($now > $record['reset_time']) {
            // Waktu sudah terlewat, reset hitungan
            $resetTime = $now + $window;
            executeSecure($con, 
                "UPDATE api_rate_limits SET request_count = 1, reset_time = ? WHERE id = ?", 
                [$resetTime, $record['id']], 
                'is'
            );
            return true;
        } else {
            // Masih dalam rentang waktu window
            if ($record['request_count'] < $limit) {
                // Tambah hitungan
                executeSecure($con, 
                    "UPDATE api_rate_limits SET request_count = request_count + 1 WHERE id = ?", 
                    [$record['id']], 
                    's'
                );
                return true;
            } else {
                // Limit tercapai
                return false;
            }
        }
    }
}
?>
