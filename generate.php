<?php

/**
 * PHP File Generator CLI
 * Usage: php generate.php nama-file
 * Example: php generate.php user/profile
 */

// Warna untuk terminal
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RED', "\033[31m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

function createDirectory($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo COLOR_GREEN . "✓ Folder created: " . COLOR_RESET . $path . "\n";
        return true;
    }
    return false;
}

function createPageFile($filePath, $fileName)
{
    $content = <<<'PHP'
<?php
/**
 * Page: {fileName}
 * Created: {date}
 */
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{title}</h4>
            </div>
            <div class="card-body">
                <p>Content for {fileName}</p>
            </div>
        </div>
    </div>
</div>
PHP;

    $title = ucwords(str_replace(['-', '_'], ' ', basename($fileName)));
    $content = str_replace('{fileName}', $fileName, $content);
    $content = str_replace('{title}', $title, $content);
    $content = str_replace('{date}', date('Y-m-d H:i:s'), $content);

    file_put_contents($filePath, $content);
    echo COLOR_GREEN . "✓ Page created: " . COLOR_RESET . $filePath . "\n";
}

function createActionFile($filePath, $fileName)
{
    $content = <<<'PHP'
<?php
/**
 * Action: {fileName}
 * Created: {date}
 */

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ========== CREATE ==========
    if (isset($_POST['addData'])) {
        // include '../functions/upload_file.php';
        
        $id = generate_uuid();
        // $name = sani($_POST['name']);
        
        $query = "INSERT INTO table_name (id) VALUES (?)";
        $params = [$id];
        $types = 's';
        $insertResult = executeSecure($con, $query, $params, $types);
        
        if ($insertResult) {
            createLog($con, $_SESSION['admin']['email'], 'Successful data addition');
            redirectWithMessage('../?hal={redirect}', 'Data berhasil ditambahkan!', 'success');
        }
        
        redirectWithMessage('../?hal={redirect}', 'Gagal menambahkan data.', 'error');
    }
    
    // ========== UPDATE ==========
    if (isset($_POST['updateData'])) {
        // include '../functions/upload_file.php';
        
        $id = sani($_POST['id']);
        // $name = sani($_POST['name']);
        
        $query = "UPDATE table_name SET col = ? WHERE id = ?";
        $params = ['val', $id];
        $types = 'ss';
        $updateResult = executeSecure($con, $query, $params, $types);
        
        if ($updateResult) {
            createLog($con, $_SESSION['admin']['email'], 'Successful data update');
            redirectWithMessage('../?hal={redirect}', 'Data berhasil diperbarui!', 'success');
        }
        
        redirectWithMessage('../?hal={redirect}', 'Gagal memperbarui data.', 'error');
    }
    exit;
} 
// ========== DELETE (GET) ==========
elseif (isset($_GET['deleteData'])) {
    $id = sani($_GET['deleteData']);
    
    $deleteResult = executeSecure($con, "DELETE FROM table_name WHERE id = ?", [$id], 's');
    
    if ($deleteResult) {
        createLog($con, $_SESSION['admin']['email'], 'Successful data deletion');
        redirectWithMessage('../?hal={redirect}', 'Data berhasil dihapus!', 'success');
    }
    
    redirectWithMessage('../?hal={redirect}', 'Gagal menghapus data.', 'error');
    exit;
} else {
    // If accessed directly, redirect to homepage
    redirectWithMessage('../../index.php', 'Akses tidak valid.', 'error');
}
PHP;

    $redirect = str_replace('/', '_', $fileName);
    $content = str_replace('{fileName}', $fileName, $content);
    $content = str_replace('{redirect}', $redirect, $content);
    $content = str_replace('{date}', date('Y-m-d H:i:s'), $content);

    file_put_contents($filePath, $content);
    echo COLOR_GREEN . "✓ Action created: " . COLOR_RESET . $filePath . "\n";
}

function createMigrationFile($basePath, $tableName)
{
    $databaseDir = $basePath . '/database/';
    createDirectory($databaseDir);
    
    $timestamp = date('YmdHis');
    $sqlFileName = $timestamp . "-" . $tableName . ".sql";
    $sqlPath = $databaseDir . $sqlFileName;
    
    $content = "-- Migration: $tableName\n";
    $content .= "-- Created at: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
    $content .= "  `id` VARCHAR(36) NOT NULL PRIMARY KEY,\n";
    $content .= "  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n";
    $content .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
    
    file_put_contents($sqlPath, $content);
    echo COLOR_GREEN . "✓ Migration created: " . COLOR_RESET . $sqlPath . "\n";
}

// Main execution
$isMigrationOnly = false;
$fileName = '';

if ($argc >= 2) {
    if ($argv[1] === '-m') {
        $isMigrationOnly = true;
        if (isset($argv[2])) {
            $fileName = $argv[2];
        } else {
            echo COLOR_RED . "Error: " . COLOR_RESET . "Please provide a table name for migration\n";
            echo COLOR_YELLOW . "Usage: " . COLOR_RESET . "php generate.php -m table_name\n";
            exit(1);
        }
    } else {
        $fileName = $argv[1];
    }
} else {
    echo COLOR_RED . "Error: " . COLOR_RESET . "Please provide a file name\n";
    echo COLOR_YELLOW . "Usage: " . COLOR_RESET . "php generate.php nama-file ATAU php generate.php -m nama-table\n";
    exit(1);
}

// $fileName is already correctly assigned based on the arguments
$basePath = __DIR__;

echo COLOR_BLUE . "\n=== PHP File Generator ===" . COLOR_RESET . "\n";

if ($isMigrationOnly) {
    echo "Generating migration for: " . COLOR_YELLOW . $fileName . COLOR_RESET . "\n\n";
    createMigrationFile($basePath, $fileName);
    echo "\n" . COLOR_GREEN . "✓ Migration generation completed!" . COLOR_RESET . "\n\n";
    exit(0);
}

echo "Generating files for: " . COLOR_YELLOW . $fileName . COLOR_RESET . "\n\n";

// Process pages folder
$pagesPath = $basePath . '/pages/';
if (strpos($fileName, '/') !== false) {
    // Ada folder, buat folder dulu
    $pathParts = explode('/', $fileName);
    $file = array_pop($pathParts);
    $folderPath = $pagesPath . implode('/', $pathParts);

    createDirectory($folderPath);
    $pageFile = $folderPath . '/' . $file . '.php';
} else {
    $pageFile = $pagesPath . $fileName . '.php';
}

// Check if file already exists
if (file_exists($pageFile)) {
    echo COLOR_RED . "✗ Page file already exists: " . COLOR_RESET . $pageFile . "\n";
} else {
    createPageFile($pageFile, $fileName);
}

// Process actions folder
$actionsPath = $basePath . '/actions/pages/';
if (strpos($fileName, '/') !== false) {
    // Ada folder, buat folder dulu
    $pathParts = explode('/', $fileName);
    $file = array_pop($pathParts);
    $folderPath = $actionsPath . implode('/', $pathParts);

    createDirectory($folderPath);
    $actionFile = $folderPath . '/' . $file . '.php';
} else {
    $actionFile = $actionsPath . $fileName . '.php';
}

// Check if file already exists
if (file_exists($actionFile)) {
    echo COLOR_RED . "✗ Action file already exists: " . COLOR_RESET . $actionFile . "\n";
} else {
    createActionFile($actionFile, $fileName);
}

echo "\n" . COLOR_GREEN . "✓ Generation completed!" . COLOR_RESET . "\n";
echo COLOR_YELLOW . "Page URL: " . COLOR_RESET . "index.php?hal=" . str_replace('/', '_', $fileName) . "\n";
echo COLOR_YELLOW . "Action URL: " . COLOR_RESET . "actions/index.php?hal=" . str_replace('/', '_', $fileName) . "\n\n";
