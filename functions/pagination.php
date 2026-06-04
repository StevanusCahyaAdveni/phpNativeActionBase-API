<?php
/**
 * Pagination Helper Functions
 * Created: 2025-12-11
 */

/**
 * Membuat pagination dari query database
 * 
 * @param mysqli $con Koneksi database
 * @param string $query Query SQL (tanpa LIMIT)
 * @param int $jumlahLimit Jumlah data per halaman
 * @return array ['data' => hasil query, 'total_pages' => total halaman, 'current_page' => halaman saat ini]
 */
function makePagination($con, $query, $params = [], $types = '', $jumlahLimit = 10)
{
    // Get current page from URL
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $currentPage = max(1, $currentPage); // Minimal halaman 1

    // Hitung total data menggunakan querySecure
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as count_table";
    $countResult = querySecure($con, $countQuery, $params, $types);
    $totalData = 0;

    if ($countResult) {
        $countRow = mysqli_fetch_assoc($countResult);
        $totalData = (int)$countRow['total'];
    }

    // Hitung total halaman
    $totalPages = ceil($totalData / $jumlahLimit);

    // Pastikan current page tidak melebihi total pages
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }

    // Hitung offset
    $offset = ($currentPage - 1) * $jumlahLimit;

    // Query dengan LIMIT menggunakan prepared statement
    $limitedQuery = "$query LIMIT ? OFFSET ?";
    
    $limitParams = $params;
    $limitParams[] = $jumlahLimit;
    $limitParams[] = $offset;
    $limitTypes = $types . 'ii';
    
    $result = querySecure($con, $limitedQuery, $limitParams, $limitTypes);

    // Simpan data ke array
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }

    return [
        'data' => $data,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'total_data' => $totalData,
        'per_page' => $jumlahLimit,
        'from' => $totalData > 0 ? $offset + 1 : 0,
        'to' => min($offset + $jumlahLimit, $totalData)
    ];
}

/**
 * Menampilkan HTML pagination dengan Bootstrap 5
 * 
 * @param int $totalPages Total halaman
 * @param int $currentPage Halaman saat ini
 * @param int $maxLinks Jumlah maksimal link halaman yang ditampilkan (default 5)
 * @return string HTML pagination
 */
function showPagination($totalPages, $currentPage = 1, $maxLinks = 5)
{
    // Jika hanya 1 halaman atau kurang, tidak perlu pagination
    if ($totalPages <= 1) {
        return '';
    }

    // Get current URL without 'page' parameter
    $currentUrl = getCurrentUrlWithoutPage();

    // Tentukan apakah URL sudah ada query string
    $separator = (strpos($currentUrl, '?') !== false) ? '&' : '?';

    $html = '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center">';

    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $currentUrl . $separator . 'page=' . $prevPage . '" aria-label="Previous">';
        $html .= '<span aria-hidden="true">&laquo;</span>';
        $html .= '</a>';
        $html .= '</li>';
    } else {
        $html .= '<li class="page-item disabled">';
        $html .= '<span class="page-link">&laquo;</span>';
        $html .= '</li>';
    }

    // Hitung range halaman yang akan ditampilkan
    $startPage = 1;
    $endPage = min($maxLinks, $totalPages);

    // Jika current page di tengah-tengah, sesuaikan range
    if ($currentPage > ceil($maxLinks / 2)) {
        $startPage = $currentPage - floor($maxLinks / 2);
        $endPage = $currentPage + floor($maxLinks / 2);

        // Jika endPage melebihi totalPages
        if ($endPage > $totalPages) {
            $endPage = $totalPages;
            $startPage = max(1, $totalPages - $maxLinks + 1);
        }
    }

    // Tampilkan halaman 1 jika tidak termasuk dalam range
    if ($startPage > 1) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $currentUrl . $separator . 'page=1">1</a>';
        $html .= '</li>';

        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Tampilkan range halaman
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active" aria-current="page">';
            $html .= '<span class="page-link">' . $i . '</span>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $currentUrl . $separator . 'page=' . $i . '">' . $i . '</a>';
            $html .= '</li>';
        }
    }

    // Tampilkan halaman terakhir jika tidak termasuk dalam range
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $currentUrl . $separator . 'page=' . $totalPages . '">' . $totalPages . '</a>';
        $html .= '</li>';
    }

    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link" href="' . $currentUrl . $separator . 'page=' . $nextPage . '" aria-label="Next">';
        $html .= '<span aria-hidden="true">&raquo;</span>';
        $html .= '</a>';
        $html .= '</li>';
    } else {
        $html .= '<li class="page-item disabled">';
        $html .= '<span class="page-link">&raquo;</span>';
        $html .= '</li>';
    }

    $html .= '</ul>';
    $html .= '</nav>';

    return $html;
}

/**
 * Mendapatkan URL saat ini tanpa parameter 'page'
 * 
 * @return string URL tanpa parameter page
 */
function getCurrentUrlWithoutPage()
{
    // Get current script name
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $scriptName = basename($scriptName);

    // Get all GET parameters except 'page'
    $params = $_GET;
    unset($params['page']);

    // Build URL
    $url = $scriptName;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    return $url;
}

/**
 * Menampilkan informasi pagination (menampilkan X sampai Y dari Z data)
 * 
 * @param array $pagination Hasil dari makePagination()
 * @return string HTML info pagination
 */
function showPaginationInfo($pagination)
{
    if ($pagination['total_data'] == 0) {
        return '<p class="text-muted mb-0">Tidak ada data</p>';
    }

    return '<p class="text-muted mb-0">Menampilkan ' . $pagination['from'] . ' sampai ' .
        $pagination['to'] . ' dari ' . $pagination['total_data'] . ' data</p>';
}

/**
 * ============================================
 * CONTOH PENGGUNAAN PAGINATION
 * ============================================
 */

/*
// 1. Include file yang diperlukan
include 'functions/pagination.php';

// 2. Buat query dasar (TANPA LIMIT)
$query = "SELECT * FROM users WHERE status = 'active' ORDER BY created_at DESC";

// 3. Gunakan fungsi makePagination
$pagination = makePagination($con, $query, [], '', 10); // 10 data per halaman

// 4. Tampilkan data dalam tabel
?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (count($pagination['data']) > 0) {
                $no = $pagination['from'];
                foreach($pagination['data'] as $row): 
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
            <?php 
                endforeach;
            } else {
                echo '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// 5. Tampilkan informasi pagination
echo '<div class="d-flex justify-content-between align-items-center mt-3">';
echo showPaginationInfo($pagination);
echo '</div>';

// 6. Tampilkan navigasi pagination
echo showPagination($pagination['total_pages'], $pagination['current_page']);

// ============================================
// CONTOH DENGAN QUERY KOMPLEKS
// ============================================

// Query dengan JOIN dan WHERE
$query = "SELECT u.*, r.role_name 
          FROM users u 
          LEFT JOIN roles r ON u.role_id = r.id 
          WHERE u.deleted_at IS NULL 
          ORDER BY u.created_at DESC";

$pagination = makePagination($con, $query, [], '', 15);

// Akses data
foreach($pagination['data'] as $user) {
    echo $user['name'] . ' - ' . $user['role_name'];
}

// ============================================
// CONTOH DENGAN SEARCH
// ============================================

// Ambil keyword dari GET parameter
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

// Query dengan kondisi search
if (!empty($search)) {
    $searchParam = "%$search%";
    $query = "SELECT * FROM users 
              WHERE (name LIKE ? OR email LIKE ?) 
              AND deleted_at IS NULL 
              ORDER BY created_at DESC";
    $params = [$searchParam, $searchParam];
    $types = 'ss';
} else {
    $query = "SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC";
    $params = [];
    $types = '';
}

$pagination = makePagination($con, $query, $params, $types, 20);

// Tampilkan dengan form search
?>
<form method="GET" class="mb-3">
    <input type="hidden" name="hal" value="<?php echo $_GET['hal'] ?? 'users'; ?>">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Cari nama atau email..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Cari</button>
    </div>
</form>

<?php
// Tampilkan data dan pagination seperti contoh sebelumnya
echo showPagination($pagination['total_pages'], $pagination['current_page']);

*/
