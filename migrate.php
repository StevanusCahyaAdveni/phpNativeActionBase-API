<?php
/**
 * Database Migration CLI
 * Usage: php migrate.php
 */

// Warna untuk terminal
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RED', "\033[31m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

// Hanya izinkan eksekusi via CLI
if (php_sapi_name() !== 'cli') {
    die("Script ini hanya dapat dijalankan melalui terminal/CLI.");
}

echo COLOR_BLUE . "=====================================\n";
echo "   Database Migration CLI Tool\n";
echo "=====================================\n" . COLOR_RESET;

// Include konfigurasi dan fungsi UUID
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/generate_uuid.php';

// Pastikan koneksi berhasil
if (!isset($con) || !$con) {
    echo COLOR_RED . "✗ Koneksi database gagal.\n" . COLOR_RESET;
    exit(1);
}

// 1. Buat tabel migrations jika belum ada
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` VARCHAR(36) NOT NULL PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (!mysqli_query($con, $createTableQuery)) {
    echo COLOR_RED . "✗ Gagal membuat tabel migrations: " . mysqli_error($con) . "\n" . COLOR_RESET;
    exit(1);
}

// 2. Ambil daftar migrasi yang sudah dieksekusi
$executedMigrations = [];
$result = mysqli_query($con, "SELECT migration FROM migrations");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $executedMigrations[] = $row['migration'];
    }
}

// 3. Pindai folder database untuk mencari file .sql
$databaseDir = __DIR__ . '/database/';
if (!is_dir($databaseDir)) {
    echo COLOR_YELLOW . "! Folder database/ tidak ditemukan. Tidak ada migrasi yang dijalankan.\n" . COLOR_RESET;
    exit(0);
}

$sqlFiles = glob($databaseDir . '*.sql');
if (empty($sqlFiles)) {
    echo COLOR_YELLOW . "! Tidak ada file .sql di folder database/.\n" . COLOR_RESET;
    exit(0);
}

// Urutkan file berdasarkan nama (alphabetical / chronological karena ada timestamp)
sort($sqlFiles);

$migratedCount = 0;

foreach ($sqlFiles as $file) {
    $filename = basename($file);

    // Lewati jika sudah dieksekusi
    if (in_array($filename, $executedMigrations)) {
        continue;
    }

    echo "Mengeksekusi: " . COLOR_YELLOW . $filename . COLOR_RESET . "... ";

    $sqlContent = file_get_contents($file);
    if (empty(trim($sqlContent))) {
        echo COLOR_YELLOW . "Dilewati (File kosong)\n" . COLOR_RESET;
        continue;
    }

    // Eksekusi multi-query
    if (mysqli_multi_query($con, $sqlContent)) {
        // Harus consume semua result agar bisa menjalankan query berikutnya
        do {
            if ($res = mysqli_store_result($con)) {
                mysqli_free_result($res);
            }
        } while (mysqli_more_results($con) && mysqli_next_result($con));
        
        // Cek apakah ada error di tengah multi_query
        if (mysqli_error($con)) {
            echo COLOR_RED . "Gagal!\n" . COLOR_RESET;
            echo COLOR_RED . "✗ Error: " . mysqli_error($con) . "\n" . COLOR_RESET;
            break; // Hentikan proses jika ada error
        }

        // Catat ke tabel migrations
        $id = generate_uuid();
        $stmt = mysqli_prepare($con, "INSERT INTO migrations (id, migration) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $id, $filename);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo COLOR_GREEN . "Berhasil!\n" . COLOR_RESET;
        $migratedCount++;
    } else {
        echo COLOR_RED . "Gagal!\n" . COLOR_RESET;
        echo COLOR_RED . "✗ Error: " . mysqli_error($con) . "\n" . COLOR_RESET;
        break; // Hentikan proses jika ada error
    }
}

if ($migratedCount > 0) {
    echo "\n" . COLOR_GREEN . "✓ Selesai. " . $migratedCount . " migrasi berhasil dijalankan.\n" . COLOR_RESET;
} else {
    echo "\n" . COLOR_GREEN . "✓ Tidak ada migrasi baru untuk dijalankan.\n" . COLOR_RESET;
}

mysqli_close($con);
?>
