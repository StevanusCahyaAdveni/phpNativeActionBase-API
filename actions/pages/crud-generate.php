<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data
    $direktori = sani($_POST['direktori']);
    $tableName = sani($_POST['nama_table']);
    $columns = json_decode($_POST['columns'], true);
    $hasFileUpload = isset($_POST['has_file_upload']);
    $hasPassword = isset($_POST['has_password']);
    $hasTimestamps = isset($_POST['has_timestamps']);
    $hasApi = isset($_POST['has_api']);

    // Validasi
    if (empty($direktori) || empty($tableName) || empty($columns)) {
        $_SESSION['message'] = 'Semua field harus diisi!';
        $_SESSION['message_type'] = 'error';
        echo "
            <script>
                window.location.href = '../?hal=crud_generate';
            </script>
        ";
        exit;
    }

    // Function to generate SQL
    function generateSQL($tableName, $columns, $hasPassword, $hasFileUpload, $hasTimestamps)
    {
        $sql = "-- ============================================\n";
        $sql .= "-- Table: $tableName\n";
        $sql .= "-- Description: Auto-generated table structure\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- ============================================\n\n";

        $sql .= "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
        $sql .= "  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',\n";

        foreach ($columns as $column) {
            $type = strtoupper($column['type']);
            $name = $column['name'];
            $label = $column['label'];

            if ($type == 'INT') {
                $sql .= "  `$name` INT(11) NOT NULL COMMENT '$label',\n";
            } elseif ($type == 'TEXT') {
                $sql .= "  `$name` TEXT NOT NULL COMMENT '$label',\n";
            } elseif ($type == 'DATE') {
                $sql .= "  `$name` DATE NOT NULL COMMENT '$label',\n";
            } elseif ($type == 'DATETIME') {
                $sql .= "  `$name` DATETIME NOT NULL COMMENT '$label',\n";
            } else {
                $sql .= "  `$name` VARCHAR(255) NOT NULL COMMENT '$label',\n";
            }
        }

        if ($hasPassword) {
            $sql .= "  `password` VARCHAR(255) NOT NULL COMMENT 'Password hash (bcrypt)',\n";
        }

        if ($hasFileUpload) {
            $sql .= "  `photo` VARCHAR(255) DEFAULT NULL COMMENT 'Photo file path',\n";
        }

        if ($hasTimestamps) {
            $sql .= "  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',\n";
            $sql .= "  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',\n";
        }

        $sql .= "  PRIMARY KEY (`id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';\n\n";

        $sql .= "-- ============================================\n";
        $sql .= "-- Sample Data (Commented)\n";
        $sql .= "-- ============================================\n";
        $sql .= "-- INSERT INTO `$tableName` (`id`";

        foreach ($columns as $column) {
            $sql .= ", `{$column['name']}`";
        }

        if ($hasPassword) {
            $sql .= ", `password`";
        }

        if ($hasFileUpload) {
            $sql .= ", `photo`";
        }

        $sql .= ") VALUES\n";
        $sql .= "-- ('sample-uuid-here'";

        foreach ($columns as $column) {
            $sql .= ", 'Sample {$column['label']}'";
        }

        if ($hasPassword) {
            $sql .= ", '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'";
        }

        if ($hasFileUpload) {
            $sql .= ", 'assets/images/photos/sample.jpg'";
        }

        $sql .= ");\n\n";

        $sql .= "-- ============================================\n";
        $sql .= "-- Notes:\n";
        $sql .= "-- - Primary key uses UUID v4 format (36 characters)\n";
        $sql .= "-- - All VARCHAR fields use utf8mb4_unicode_ci collation\n";
        if ($hasPassword) {
            $sql .= "-- - Password uses bcrypt hashing (PASSWORD_DEFAULT)\n";
        }
        if ($hasFileUpload) {
            $sql .= "-- - Photo paths are relative to project root\n";
        }
        $sql .= "-- ============================================\n";

        return $sql;
    }

    // Function to generate Page
    function generatePage($direktori, $tableName, $columns, $hasFileUpload, $hasPassword)
    {
        // Parse direktori (e.g., users/user-management)
        $parts = explode('/', $direktori);
        $moduleName = $parts[0];
        $featureName = isset($parts[1]) ? $parts[1] : $parts[0];
        $routeName = str_replace('/', '_', $direktori);

        $page = "<?php\n";
        $page .= "include 'functions/pagination.php';\n";
        $page .= "\$search = isset(\$_GET['search']) ? sani(\$_GET['search']) : '';\n";
        $page .= "\$whereClause = '';\n";
        $page .= "\$params = [];\n";
        $page .= "\$types = '';\n\n";
        $page .= "if (!empty(\$search)) {\n";
        $page .= "    \$searchPattern = '%' . \$search . '%';\n";
        $searchCols = [];
        foreach ($columns as $column) {
            $searchCols[] = "{$column['name']} LIKE ?";
        }
        $page .= "    \$whereClause = \" AND (" . implode(" OR ", $searchCols) . ")\";\n";
        $page .= "    for(\$i=0; \$i<" . count($columns) . "; \$i++) {\n";
        $page .= "        \$params[] = \$searchPattern;\n";
        $page .= "        \$types .= 's';\n";
        $page .= "    }\n";
        $page .= "}\n\n";
        $page .= "\$query = \"SELECT * FROM $tableName WHERE 1 = 1 \" . \$whereClause . \" ORDER BY id DESC\";\n";
        $page .= "\$pagination = makePagination(\$con, \$query, \$params, \$types, 10);\n";
        $page .= "?>\n\n";

        $page .= "<!-- Alert Message -->\n";
        $page .= "<?php if (isset(\$_SESSION['message'])): ?>\n";
        $page .= "    <div class=\"alert alert-<?= \$_SESSION['message_type'] ?> alert-dismissible fade show\" role=\"alert\">\n";
        $page .= "        <?= \$_SESSION['message'] ?>\n";
        $page .= "        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>\n";
        $page .= "    </div>\n";
        $page .= "    <?php unset(\$_SESSION['message']); unset(\$_SESSION['message_type']); ?>\n";
        $page .= "<?php endif; ?>\n\n";

        $page .= "<!-- Header Section -->\n";
        $page .= "<div class=\"page-heading\">\n";
        $page .= "    <!-- Action Buttons -->\n";
        $page .= "    <p align=\"right\">\n";
        $page .= "        <button type=\"button\" class=\"btn shadow-sm btn-sm btn-primary \" data-bs-toggle=\"modal\" data-bs-target=\"#addModal\">\n";
        $page .= "            <i class=\"bi bi-plus-circle\"></i> Add New\n";
        $page .= "        </button>\n";
        $page .= "    </p>\n";
        $page .= "    <section class=\"section\">\n";
        $page .= "        <!-- Search Form -->\n";
        $page .= "        <div class=\"card p-2 mb-1 shadow-sm\">\n";
        $page .= "            <form method=\"GET\" action=\"\">\n";
        $page .= "                <input type=\"hidden\" name=\"hal\" value=\"$routeName\">\n";
        $page .= "                <div class=\"row g-1\">\n";
        $page .= "                    <div class=\"col-10\">\n";
        $page .= "                        <input type=\"text\" class=\"form-control form-control-sm\" name=\"search\" placeholder=\"Search...\" value=\"<?= \$_GET['search'] ?? '' ?>\">\n";
        $page .= "                    </div>\n";
        $page .= "                    <div class=\"col-2\">\n";
        $page .= "                        <button type=\"submit\" class=\"btn btn-sm btn-primary w-100\"><i class=\"bi bi-search\"></i></button>\n";
        $page .= "                    </div>\n";
        $page .= "                </div>\n";
        $page .= "            </form>\n";
        $page .= "        </div>\n\n";

        $page .= "        <!-- Data Table -->\n";
        $page .= "        <div class=\"card p-2 mb-1 shadow-sm\">\n";
        $page .= "            <div class=\"table-responsive\">\n";
        $page .= "                <table class=\"table table-sm table-hover table-striped\" style=\"font-size: 12px;\">\n";
        $page .= "                    <thead>\n";
        $page .= "                        <tr>\n";
        $page .= "                            <th>No</th>\n";
        foreach ($columns as $column) {
            $page .= "                            <th>{$column['label']}</th>\n";
        }
        if ($hasFileUpload) {
            $page .= "                            <th>Photo</th>\n";
        }
        $page .= "                            <th>Actions</th>\n";
        $page .= "                        </tr>\n";
        $page .= "                    </thead>\n";
        $page .= "                    <tbody>\n";
        $page .= "                        <?php\n";
        $page .= "                        \$no = \$pagination['from'];\n";
        $page .= "                        foreach (\$pagination['data'] as \$row): ?>\n";
        $page .= "                            <tr class=\"pt-1 pb-1\">\n";
        $page .= "                                <td><?= \$no++ ?></td>\n";
        foreach ($columns as $column) {
            $page .= "                                <td><?= htmlspecialchars(\$row['{$column['name']}']) ?></td>\n";
        }
        if ($hasFileUpload) {
            $page .= "                                <td>\n";
            $page .= "                                    <?php if (!empty(\$row['photo'])): ?>\n";
            $page .= "                                        <img src=\"<?= \$row['photo'] ?>\" alt=\"Photo\" style=\"width: 50px; height: 50px; object-fit: cover;\">\n";
            $page .= "                                    <?php else: ?>\n";
            $page .= "                                        <span class=\"text-muted\">No photo</span>\n";
            $page .= "                                    <?php endif; ?>\n";
            $page .= "                                </td>\n";
        }
        $page .= "                                <td>\n";
        $page .= "                                    <button type=\"button\" class=\"btn btn-sm btn-warning\" onclick=\"upData(\n";
        $page .= "                                        '<?= \$row['id'] ?>',\n";
        foreach ($columns as $i => $column) {
            $page .= "                                        '<?= htmlspecialchars(\$row['{$column['name']}']) ?>'";
            if ($i < count($columns) - 1 || $hasPassword || $hasFileUpload) {
                $page .= ",\n";
            } else {
                $page .= "\n";
            }
        }
        if ($hasPassword) {
            $page .= "                                        '<?= \$row['password'] ?>'";
            if ($hasFileUpload) {
                $page .= ",\n";
            } else {
                $page .= "\n";
            }
        }
        if ($hasFileUpload) {
            $page .= "                                        '<?= \$row['photo'] ?>'\n";
        }
        $page .= "                                    )\">\n";
        $page .= "                                        <i class=\"bi bi-pencil\"></i>\n";
        $page .= "                                    </button>\n";
        $page .= "                                    <a href=\"actions/?hal=$routeName&delete=<?= \$row['id'] ?>\" class=\"btn btn-sm btn-danger\" onclick=\"return confirm('Are you sure?')\">\n";
        $page .= "                                        <i class=\"bi bi-trash\"></i>\n";
        $page .= "                                    </a>\n";
        $page .= "                                </td>\n";
        $page .= "                            </tr>\n";
        $page .= "                        <?php endforeach; ?>\n";
        $page .= "                    </tbody>\n";
        $page .= "                </table>\n";
        $page .= "            </div>\n";
        $page .= "            <!-- Pagination -->\n";
        $page .= "            <div class=\"d-flex justify-content-between align-items-center mt-3\">\n";
        $page .= "                <?= showPaginationInfo(\$pagination); ?>\n";
        $page .= "                <?= showPagination(\$pagination['total_pages'], \$pagination['current_page']); ?>\n";
        $page .= "            </div>\n";
        $page .= "        </div>\n";
        $page .= "    </section>\n";
        $page .= "</div>\n\n";

        // Add Modal
        $page .= "<!-- Add Modal -->\n";
        $page .= "<div class=\"modal fade\" id=\"addModal\" tabindex=\"-1\">\n";
        $page .= "    <div class=\"modal-dialog\">\n";
        $page .= "        <div class=\"modal-content\">\n";
        $page .= "            <div class=\"modal-header\">\n";
        $page .= "                <h5 class=\"modal-title\">Add New " . ucfirst($featureName) . "</h5>\n";
        $page .= "                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"></button>\n";
        $page .= "            </div>\n";
        $page .= "            <form action=\"actions/?hal=$routeName\" method=\"POST\"" . ($hasFileUpload ? " enctype=\"multipart/form-data\"" : "") . ">\n";
        $page .= "                <div class=\"modal-body\">\n";

        foreach ($columns as $column) {
            $page .= "                    <div class=\"mb-3\">\n";
            $page .= "                        <label class=\"form-label\">{$column['label']}</label>\n";
            if ($column['type'] == 'text') {
                $page .= "                        <textarea class=\"form-control\" name=\"{$column['name']}\" required></textarea>\n";
            } elseif ($column['type'] == 'date') {
                $page .= "                        <input type=\"date\" class=\"form-control\" name=\"{$column['name']}\" required>\n";
            } elseif ($column['type'] == 'datetime') {
                $page .= "                        <input type=\"datetime-local\" class=\"form-control\" name=\"{$column['name']}\" required>\n";
            } elseif ($column['type'] == 'int') {
                $page .= "                        <input type=\"number\" class=\"form-control\" name=\"{$column['name']}\" required>\n";
            } else {
                $page .= "                        <input type=\"text\" class=\"form-control\" name=\"{$column['name']}\" required>\n";
            }
            $page .= "                    </div>\n";
        }

        if ($hasPassword) {
            $page .= "                    <div class=\"mb-3\">\n";
            $page .= "                        <label class=\"form-label\">Password</label>\n";
            $page .= "                        <input type=\"password\" class=\"form-control\" name=\"password\" required>\n";
            $page .= "                    </div>\n";
        }

        if ($hasFileUpload) {
            $page .= "                    <div class=\"mb-3\">\n";
            $page .= "                        <label class=\"form-label\">Photo</label>\n";
            $page .= "                        <input type=\"file\" class=\"form-control\" name=\"photo\" accept=\"image/*\">\n";
            $page .= "                    </div>\n";
        }

        $page .= "                </div>\n";
        $page .= "                <div class=\"modal-footer\">\n";
        $page .= "                    <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Close</button>\n";
        $page .= "                    <button type=\"submit\" name=\"addData\" class=\"btn btn-primary\">Save</button>\n";
        $page .= "                </div>\n";
        $page .= "            </form>\n";
        $page .= "        </div>\n";
        $page .= "    </div>\n";
        $page .= "</div>\n\n";

        // Edit Modal
        $page .= "<!-- Edit Modal -->\n";
        $page .= "<div class=\"modal fade\" id=\"editModal\" tabindex=\"-1\">\n";
        $page .= "    <div class=\"modal-dialog\">\n";
        $page .= "        <div class=\"modal-content\">\n";
        $page .= "            <div class=\"modal-header\">\n";
        $page .= "                <h5 class=\"modal-title\">Edit " . ucfirst($featureName) . "</h5>\n";
        $page .= "                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"></button>\n";
        $page .= "            </div>\n";
        $page .= "            <form action=\"actions/?hal=$routeName\" method=\"POST\"" . ($hasFileUpload ? " enctype=\"multipart/form-data\"" : "") . ">\n";
        $page .= "                <div class=\"modal-body\">\n";
        $page .= "                    <input type=\"hidden\" name=\"id\" id=\"edit_id\">\n";

        foreach ($columns as $column) {
            $page .= "                    <div class=\"mb-3\">\n";
            $page .= "                        <label class=\"form-label\">{$column['label']}</label>\n";
            if ($column['type'] == 'text') {
                $page .= "                        <textarea class=\"form-control\" name=\"{$column['name']}\" id=\"edit_{$column['name']}\" required></textarea>\n";
            } elseif ($column['type'] == 'date') {
                $page .= "                        <input type=\"date\" class=\"form-control\" name=\"{$column['name']}\" id=\"edit_{$column['name']}\" required>\n";
            } elseif ($column['type'] == 'datetime') {
                $page .= "                        <input type=\"datetime-local\" class=\"form-control\" name=\"{$column['name']}\" id=\"edit_{$column['name']}\" required>\n";
            } elseif ($column['type'] == 'int') {
                $page .= "                        <input type=\"number\" class=\"form-control\" name=\"{$column['name']}\" id=\"edit_{$column['name']}\" required>\n";
            } else {
                $page .= "                        <input type=\"text\" class=\"form-control\" name=\"{$column['name']}\" id=\"edit_{$column['name']}\" required>\n";
            }
            $page .= "                    </div>\n";
        }

        if ($hasPassword) {
            $page .= "                    <input type=\"hidden\" name=\"password_old\" id=\"edit_password_old\">\n";
            $page .= "                    <div class=\"mb-3\">\n";
            $page .= "                        <label class=\"form-label\">Password (leave blank to keep current)</label>\n";
            $page .= "                        <input type=\"password\" class=\"form-control\" name=\"password\" id=\"edit_password\">\n";
            $page .= "                    </div>\n";
        }

        if ($hasFileUpload) {
            $page .= "                    <div class=\"mb-3\">\n";
            $page .= "                        <label class=\"form-label\">Photo</label>\n";
            $page .= "                        <input type=\"file\" class=\"form-control\" name=\"photo\" id=\"edit_photo\" accept=\"image/*\">\n";
            $page .= "                        <small class=\"text-muted\">Current photo will be kept if no new file uploaded</small>\n";
            $page .= "                    </div>\n";
        }

        $page .= "                </div>\n";
        $page .= "                <div class=\"modal-footer\">\n";
        $page .= "                    <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Close</button>\n";
        $page .= "                    <button type=\"submit\" name=\"updateData\" class=\"btn btn-primary\">Update</button>\n";
        $page .= "                </div>\n";
        $page .= "            </form>\n";
        $page .= "        </div>\n";
        $page .= "    </div>\n";
        $page .= "</div>\n\n";

        // JavaScript
        $page .= "<script>\n";
        $page .= "function upData(id";
        foreach ($columns as $column) {
            $page .= ", " . $column['name'];
        }
        if ($hasPassword) {
            $page .= ", password";
        }
        if ($hasFileUpload) {
            $page .= ", photo";
        }
        $page .= ") {\n";
        $page .= "    document.getElementById('edit_id').value = id;\n";
        foreach ($columns as $column) {
            $page .= "    document.getElementById('edit_{$column['name']}').value = {$column['name']};\n";
        }
        if ($hasPassword) {
            $page .= "    document.getElementById('edit_password_old').value = password;\n";
        }
        $page .= "    var editModal = new bootstrap.Modal(document.getElementById('editModal'));\n";
        $page .= "    editModal.show();\n";
        $page .= "}\n";
        $page .= "</script>\n";

        return $page;
    }

    // Function to generate Action
    function generateAction($direktori, $tableName, $columns, $hasFileUpload, $hasPassword)
    {
        // Parse direktori untuk menentukan path prefix
        $parts = explode('/', $direktori);
        $routeName = str_replace('/', '_', $direktori);

        // Hitung berapa kali perlu '../' berdasarkan subdirektori
        $depth = count($parts) - 1; // Kurangi 1 karena file action ada di actions/pages/[module]/
        $pathPrefix = str_repeat('../', $depth);

        $action = "<?php\n\n";
        $action .= "// Check if form is submitted\n";
        $action .= "if (\$_SERVER['REQUEST_METHOD'] === 'POST') {\n";

        // ADD
        $action .= "    if (isset(\$_POST['addData'])) {\n";
        if ($hasFileUpload) {
            $action .= "        include '{$pathPrefix}functions/upload_file.php';\n";
        }
        $action .= "        \$id = generate_uuid();\n";

        foreach ($columns as $column) {
            $action .= "        \${$column['name']} = sani(\$_POST['{$column['name']}']);\n";
        }

        if ($hasPassword) {
            $action .= "        \$password = sani(\$_POST['password']);\n";
        }

        if ($hasFileUpload) {
            $action .= "\n        \$result = uploadFile(\$_FILES['photo'], '{$pathPrefix}assets/images/photos/', 5 * 1024 * 1024);\n\n";
            $action .= "        if (\$result['success']) {\n";
            $action .= "            echo \"Upload berhasil: \" . \$result['file_path'];\n";
            $action .= "            \$photo = str_replace('{$pathPrefix}', '', \$result['file_path']);\n";
            $action .= "        } else {\n";
            $action .= "            echo \"Upload gagal: \" . \$result['message'];\n";
            $action .= "        }\n\n";
        }

        // Build INSERT query
        $insertCols = ['id'];
        $insertVals = ['?'];
        $insertTypes = 's';
        $insertParams = ['$id'];

        foreach ($columns as $column) {
            $insertCols[] = $column['name'];
            $insertVals[] = '?';
            $insertParams[] = '$' . $column['name'];
            $insertTypes .= 's';
        }

        if ($hasPassword) {
            $insertCols[] = 'password';
            $insertVals[] = '?';
            $insertParams[] = 'password_hash($password, PASSWORD_DEFAULT)';
            $insertTypes .= 's';
        }

        if ($hasFileUpload) {
            $insertCols[] = 'photo';
            $insertVals[] = '?';
            $insertParams[] = '$photo';
            $insertTypes .= 's';
        }

        $action .= "        \$query = \"INSERT INTO $tableName (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ")\";\n";
        $action .= "        \$params = [" . implode(', ', $insertParams) . "];\n";
        $action .= "        \$types = '$insertTypes';\n";
        $action .= "        \$insertResult = executeSecure(\$con, \$query, \$params, \$types);\n\n";
        $action .= "        if (\$insertResult) {\n";
        $action .= "            \$_SESSION['message'] = 'Data berhasil ditambahkan!';\n";
        $action .= "            \$_SESSION['message_type'] = 'success';\n";
        $action .= "        } else {\n";
        $action .= "            \$_SESSION['message'] = 'Terjadi kesalahan saat menambahkan data.';\n";
        $action .= "            \$_SESSION['message_type'] = 'error';\n";
        $action .= "        }\n";
        $action .= "        echo \"\n";
        $action .= "            <script>\n";
        $action .= "                window.location.href = '../?hal=$routeName';\n";
        $action .= "            </script>\n";
        $action .= "        \";\n";
        $action .= "    }\n\n";

        // UPDATE
        $action .= "    if (isset(\$_POST['updateData'])) {\n";
        if ($hasFileUpload) {
            $action .= "        include '{$pathPrefix}functions/upload_file.php';\n";
        }
        $action .= "        \$id = sani(\$_POST['id']);\n";

        foreach ($columns as $column) {
            $action .= "        \${$column['name']} = sani(\$_POST['{$column['name']}']);\n";
        }

        if ($hasPassword) {
            $action .= "        \$password = sani(\$_POST['password']);\n";
            $action .= "        \$password_old = sani(\$_POST['password_old']);\n";
        }

        if ($hasFileUpload) {
            $action .= "\n        \$resultGetSingleData = querySecure(\$con, \"SELECT photo FROM $tableName WHERE id = ?\", [\$id], 's');\n";
            $action .= "        \$singleData = mysqli_fetch_assoc(\$resultGetSingleData);\n\n";
            $action .= "        \$photo_old = \$singleData['photo'];\n\n";
            $action .= "        // Default: gunakan foto lama\n";
            $action .= "        \$photo = \$photo_old;\n\n";
            $action .= "        // Cek apakah ada file yang diupload\n";
            $action .= "        if (isset(\$_FILES['photo']) && \$_FILES['photo']['error'] === UPLOAD_ERR_OK && !empty(\$_FILES['photo']['name']) && \$_FILES['photo']['size'] > 0 && isset(\$_FILES['photo']['name'])) {\n";
            $action .= "            \$result = uploadFile(\$_FILES['photo'], '{$pathPrefix}assets/images/photos/', 5 * 1024 * 1024);\n\n";
            $action .= "            if (\$result['success']) {\n";
            $action .= "                // Hapus foto lama jika ada dan berbeda\n";
            $action .= "                if (!empty(\$photo_old) && file_exists('{$pathPrefix}' . \$photo_old)) {\n";
            $action .= "                    unlink('{$pathPrefix}' . \$photo_old);\n";
            $action .= "                }\n";
            $action .= "                \$photo = str_replace('{$pathPrefix}', '', \$result['file_path']);\n";
            $action .= "            } else {\n";
            $action .= "                \$_SESSION['message'] = 'Upload gagal: ' . \$result['message'];\n";
            $action .= "                \$_SESSION['message_type'] = 'error';\n";
            $action .= "            }\n";
            $action .= "        }\n";
            $action .= "        \$photo == 'undefined' ? \$photo = \$photo_old : '';\n\n";
        }

        if ($hasPassword) {
            $action .= "        // Handle password\n";
            $action .= "        if (!empty(\$password) && \$password != '') {\n";
            $action .= "            \$password_hashed = password_hash(\$password, PASSWORD_DEFAULT);\n";
            $action .= "        } else {\n";
            $action .= "            \$password_hashed = \$password_old;\n";
            $action .= "        }\n\n";
        }

        // Build UPDATE query
        $updateSets = [];
        $updateParams = [];
        $updateTypes = '';

        foreach ($columns as $column) {
            $updateSets[] = $column['name'] . " = ?";
            $updateParams[] = '$' . $column['name'];
            $updateTypes .= 's';
        }

        if ($hasPassword) {
            $updateSets[] = "password = ?";
            $updateParams[] = '$password_hashed';
            $updateTypes .= 's';
        }

        if ($hasFileUpload) {
            $updateSets[] = "photo = ?";
            $updateParams[] = '$photo';
            $updateTypes .= 's';
        }

        $updateParams[] = '$id';
        $updateTypes .= 's';

        $action .= "        \$query = \"UPDATE $tableName SET " . implode(', ', $updateSets) . " WHERE id = ?\";\n";
        $action .= "        \$params = [" . implode(', ', $updateParams) . "];\n";
        $action .= "        \$types = '$updateTypes';\n";
        $action .= "        \$updateResult = executeSecure(\$con, \$query, \$params, \$types);\n\n";
        $action .= "        if (\$updateResult) {\n";
        $action .= "            \$_SESSION['message'] = 'Data berhasil diperbarui!';\n";
        $action .= "            \$_SESSION['message_type'] = 'success';\n";
        $action .= "        } else {\n";
        $action .= "            \$_SESSION['message'] = 'Terjadi kesalahan saat memperbarui data.';\n";
        $action .= "            \$_SESSION['message_type'] = 'error';\n";
        $action .= "        }\n";
        $action .= "        echo \"\n";
        $action .= "            <script>\n";
        $action .= "                window.location.href = '../?hal=$routeName';\n";
        $action .= "            </script>\n";
        $action .= "        \";\n";
        $action .= "    }\n";
        $action .= "    exit;\n";
        $action .= "} elseif (isset(\$_GET['delete'])) {\n";

        // DELETE
        $action .= "    \$id = sani(\$_GET['delete']);\n\n";

        if ($hasFileUpload) {
            $action .= "    // Dapatkan data untuk menghapus foto\n";
            $action .= "    \$resultGetData = querySecure(\$con, \"SELECT photo FROM $tableName WHERE id = ?\", [\$id], 's');\n";
            $action .= "    \$data = mysqli_fetch_assoc(\$resultGetData);\n";
            $action .= "    \$photo = \$data['photo'];\n\n";
        }

        $action .= "    // Hapus data\n";
        $action .= "    \$deleteResult = executeSecure(\$con, \"DELETE FROM $tableName WHERE id = ?\", [\$id], 's');\n\n";
        $action .= "    if (\$deleteResult) {\n";

        if ($hasFileUpload) {
            $action .= "        // Hapus foto jika ada\n";
            $action .= "        if (!empty(\$photo) && file_exists('{$pathPrefix}' . \$photo)) {\n";
            $action .= "            unlink('{$pathPrefix}' . \$photo);\n";
            $action .= "        }\n";
        }

        $action .= "        \$_SESSION['message'] = 'Data berhasil dihapus!';\n";
        $action .= "        \$_SESSION['message_type'] = 'success';\n";
        $action .= "    } else {\n";
        $action .= "        \$_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';\n";
        $action .= "        \$_SESSION['message_type'] = 'error';\n";
        $action .= "    }\n";
        $action .= "    echo \"\n";
        $action .= "            <script>\n";
        $action .= "                window.location.href = '../?hal=$routeName';\n";
        $action .= "            </script>\n";
        $action .= "        \";\n";
        $action .= "    exit;\n";
        $action .= "} else {\n";
        $action .= "    // If accessed directly, redirect to homepage\n";
        $action .= "    header('Location: ../../index.php');\n";
        $action .= "    exit;\n";
        $action .= "}\n";

        return $action;
    }

    // Function to generate API
    function generateApi($direktori, $tableName, $columns, $hasFileUpload, $hasPassword)
    {
        $parts = explode('/', $direktori);
        $routeName = str_replace('/', '_', $direktori);

        $api = "<?php\n";
        $api .= "/**\n";
        $api .= " * API Endpoint: $routeName\n";
        $api .= " * Route: api/index.php?hal=$routeName\n";
        $api .= " */\n\n";
        
        if ($hasFileUpload) {
            $api .= "include_once '../functions/upload_file.php';\n\n";
        }
        
        $api .= "\$method = \$_SERVER['REQUEST_METHOD'];\n\n";
        $api .= "switch (\$method) {\n";
        
        // GET
        $api .= "    case 'GET':\n";
        $api .= "        if (isset(\$_GET['id'])) {\n";
        $api .= "            \$id = sani(\$_GET['id']);\n";
        $api .= "            \$res = querySecure(\$con, \"SELECT * FROM $tableName WHERE id = ?\", [\$id], 's');\n";
        $api .= "            \$data = mysqli_fetch_assoc(\$res);\n";
        $api .= "            if (\$data) apiResponseSuccess('Data found', \$data);\n";
        $api .= "            else apiResponseError('NOT_FOUND', 'Data not found', null, 404);\n";
        $api .= "        } else {\n";
        $api .= "            \$search = sani(\$_GET['search'] ?? '');\n";
        $api .= "            \$whereClause = \"\";\n";
        $api .= "            \$params = [];\n";
        $api .= "            \$types = \"\";\n";
        $api .= "            if (!empty(\$search)) {\n";
        $searchCols = [];
        foreach ($columns as $column) {
            $searchCols[] = "{$column['name']} LIKE ?";
        }
        $api .= "                \$whereClause = \" AND (" . implode(" OR ", $searchCols) . ")\";\n";
        $api .= "                \$searchWildcard = '%' . \$search . '%';\n";
        $api .= "                for(\$i=0; \$i<" . count($columns) . "; \$i++) {\n";
        $api .= "                    \$params[] = \$searchWildcard;\n";
        $api .= "                    \$types .= 's';\n";
        $api .= "                }\n";
        $api .= "            }\n";
        $api .= "            \$limit = isset(\$_GET['limit']) ? (int) sani(\$_GET['limit']) : 10;\n";
        if (isset($_POST['has_timestamps'])) {
            $api .= "            \$query = \"SELECT * FROM $tableName WHERE 1 = 1 \" . \$whereClause . \" ORDER BY created_at DESC\";\n";
        } else {
            $api .= "            \$query = \"SELECT * FROM $tableName WHERE 1 = 1 \" . \$whereClause;\n";
        }
        $api .= "            \$pagination = makePagination(\$con, \$query, \$params, \$types, \$limit);\n";
        $api .= "            apiResponseSuccess('List data', \$pagination);\n";
        $api .= "        }\n";
        $api .= "        break;\n\n";

        // POST
        $api .= "    case 'POST':\n";
        $api .= "        \$input = sani(getApiInput());\n";
        $api .= "        \$id = generate_uuid();\n";
        
        $insertCols = ['id'];
        $insertVals = ['?'];
        $insertParams = ['$id'];
        $insertTypes = 's';
        
        foreach ($columns as $column) {
            $api .= "        \${$column['name']} = \$input['{$column['name']}'] ?? '';\n";
            $insertCols[] = $column['name'];
            $insertVals[] = '?';
            $insertParams[] = '$' . $column['name'];
            $insertTypes .= 's';
        }
        
        if ($hasPassword) {
            $api .= "        \$password = \$input['password'] ?? '';\n";
            $api .= "        \$hashedPassword = password_hash(\$password, PASSWORD_DEFAULT);\n";
            $insertCols[] = 'password';
            $insertVals[] = '?';
            $insertParams[] = '$hashedPassword';
            $insertTypes .= 's';
        }
        if ($hasFileUpload) {
            $api .= "        \$photoPath = null;\n";
            $api .= "        if (isset(\$_FILES['photo']) && \$_FILES['photo']['error'] === UPLOAD_ERR_OK) {\n";
            $api .= "            \$uploadResult = uploadFile(\$_FILES['photo'], '../assets/images/photos/', 5 * 1024 * 1024);\n";
            $api .= "            if (\$uploadResult['success']) {\n";
            $api .= "                \$photoPath = str_replace('../', '', \$uploadResult['file_path']);\n";
            $api .= "            } else {\n";
            $api .= "                apiResponseError('UPLOAD_ERROR', \$uploadResult['message'], null, 400);\n";
            $api .= "            }\n";
            $api .= "        }\n";
            
            $insertCols[] = 'photo';
            $insertVals[] = '?';
            $insertParams[] = '$photoPath';
            $insertTypes .= 's';
        }
        
        $api .= "        \$result = executeSecure(\$con, \n";
        $api .= "            \"INSERT INTO $tableName (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ")\",\n";
        $api .= "            [" . implode(', ', $insertParams) . "],\n";
        $api .= "            '$insertTypes'\n";
        $api .= "        );\n";
        $api .= "        if (\$result) apiResponseSuccess('Data created', ['id' => \$id], 201);\n";
        $api .= "        else apiResponseError('INTERNAL_ERROR', 'Failed to create data', null, 500);\n";
        $api .= "        break;\n\n";
        
        // PUT
        $api .= "    case 'PUT':\n";
        $api .= "        \$input = sani(getApiInput());\n";
        $api .= "        \$id = \$input['id'] ?? '';\n";
        $api .= "        if (empty(\$id)) apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', ['id' => ['The id field is required.']], 400);\n";
        
        $updateSets = [];
        $updateParams = [];
        $updateTypes = '';
        foreach ($columns as $column) {
            $api .= "        \${$column['name']} = \$input['{$column['name']}'] ?? '';\n";
            $updateSets[] = "{$column['name']} = ?";
            $updateParams[] = '$' . $column['name'];
            $updateTypes .= 's';
        }
        $updateParams[] = '$id';
        $updateTypes .= 's';
        
        $api .= "        \$result = executeSecure(\$con, \n";
        $api .= "            \"UPDATE $tableName SET " . implode(', ', $updateSets) . " WHERE id = ?\",\n";
        $api .= "            [" . implode(', ', $updateParams) . "],\n";
        $api .= "            '$updateTypes'\n";
        $api .= "        );\n";
        $api .= "        if (\$result) apiResponseSuccess('Data updated');\n";
        $api .= "        else apiResponseError('INTERNAL_ERROR', 'Failed to update', null, 500);\n";
        $api .= "        break;\n\n";

        // DELETE
        $api .= "    case 'DELETE':\n";
        $api .= "        \$input = sani(getApiInput());\n";
        $api .= "        \$id = \$input['id'] ?? (\$_GET['id'] ?? '');\n";
        $api .= "        if (empty(\$id)) apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', ['id' => ['The id field is required.']], 400);\n";
        $api .= "        \$result = executeSecure(\$con, \"DELETE FROM $tableName WHERE id = ?\", [\$id], 's');\n";
        $api .= "        if (\$result) apiResponseSuccess('Data deleted');\n";
        $api .= "        else apiResponseError('INTERNAL_ERROR', 'Failed to delete', null, 500);\n";
        $api .= "        break;\n\n";
        
        $api .= "    default:\n";
        $api .= "        apiResponseError('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);\n";
        $api .= "        break;\n";
        $api .= "}\n";
        $api .= "?>";
        
        return $api;
    }

    // Generate SQL content
    $sqlContent = generateSQL($tableName, $columns, $hasPassword, $hasFileUpload, $hasTimestamps);

    // Generate Page content
    $pageContent = generatePage($direktori, $tableName, $columns, $hasFileUpload, $hasPassword);

    // Generate Action content
    $actionContent = generateAction($direktori, $tableName, $columns, $hasFileUpload, $hasPassword);

    // Create directories
    $rootPath = dirname(dirname(__DIR__));
    $databaseDir = $rootPath . "/database";
    
    // Parse direktori untuk page path
    $parts = explode('/', $direktori);
    if (count($parts) > 1) {
        $pageDir = $rootPath . "/pages/" . $parts[0];
        $actionDir = $rootPath . "/actions/pages/" . $parts[0];
        $apiDir = $rootPath . "/api/endpoints/" . $parts[0];
        $pageFile = $parts[1] . ".php";
        $actionFile = $parts[1] . ".php";
        $apiFile = $parts[1] . ".php";
    } else {
        $pageDir = $rootPath . "/pages";
        $actionDir = $rootPath . "/actions/pages";
        $apiDir = $rootPath . "/api/endpoints";
        $pageFile = $parts[0] . ".php";
        $actionFile = $parts[0] . ".php";
        $apiFile = $parts[0] . ".php";
    }

    if (!is_dir($databaseDir)) {
        mkdir($databaseDir, 0755, true);
    }
    if (!is_dir($pageDir)) {
        mkdir($pageDir, 0755, true);
    }
    if (!is_dir($actionDir)) {
        mkdir($actionDir, 0755, true);
    }
    if ($hasApi && !is_dir($apiDir)) {
        mkdir($apiDir, 0755, true);
    }

    // Generate filename: datetimePembuatan-namatable.sql
    $timestamp = date('YmdHis');
    $sqlFileName = $timestamp . "-" . $tableName . ".sql";

    // Write SQL file
    $sqlPath = $databaseDir . "/" . $sqlFileName;
    file_put_contents($sqlPath, $sqlContent);

    // Write Page file
    $pagePath = $pageDir . "/" . $pageFile;
    file_put_contents($pagePath, $pageContent);

    // Write Action file
    $actionPath = $actionDir . "/" . $actionFile;
    file_put_contents($actionPath, $actionContent);
    
    $message = 'Semua files berhasil digenerate! SQL: database/' . $sqlFileName . ' | Page: pages/' . $direktori . '.php | Action: actions/pages/' . $direktori . '.php';

    // Generate & Write API file
    if ($hasApi) {
        $apiContent = generateApi($direktori, $tableName, $columns, $hasFileUpload, $hasPassword);
        $apiPath = $apiDir . "/" . $apiFile;
        file_put_contents($apiPath, $apiContent);
        $message .= ' | API: api/endpoints/' . $direktori . '.php';
    }

    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = 'success';

    echo "
        <script>
            window.location.href = '../?hal=crud-generate';
        </script>
    ";
    exit;
} else {
    header('Location: ../../index.php');
    exit;
}
