# Prompt untuk AI/Copilot: PHP Native Action-Based Framework

## 📋 Overview Arsitektur

Anda sedang bekerja dengan PHP native framework yang menggunakan:
- **Routing otomatis**: URL dengan underscore → file dengan slash (`users_user-management` → `pages/users/user-management.php`)
- **SPA No-Load Architecture (PJAX)**: Sistem navigasi mulus tanpa reload halaman (`spa.js`). Form menggunakan `class="ajax-form"` dan divalidasi dengan JSON response.
- **Action-based pattern**: Setiap view punya mirror action handler di `actions/pages/`. Action **TIDAK PERNAH DI-INCLUDE**, ia murni bekerja sebagai HTTP Endpoint yang di-HIT dari form HTML (via AJAX) dan me-redirect/merespons menggunakan JSON.
- **Security-first**: Semua query menggunakan prepared statements, semua input di-sanitasi, dan ada CSRF Auto-Injection via Output Buffering.
- **DataTables Native**: Pengurutan, pencarian, dan paginasi data 100% bergantung pada DataTables di client-side. Tidak ada lagi pencarian manual menggunakan query string PHP.
- **UUID primary keys**: Tidak ada auto-increment integer, semua ID adalah UUID v4.
- **Session + localStorage hybrid auth**: Session untuk security, localStorage untuk convenience
- **Database Migration**: Sistem eksekusi SQL otomatis via terminal (`php migrate.php` & `php generate.php -m`).

---

## 🗂️ Struktur Folder (Wajib Dipahami)

```
Root/
├── actions/                    # Mirror dari pages/ untuk handlers
│   ├── index.php              # Router action (sama seperti index.php)
│   ├── login.php              # Handler login POST
│   ├── register.php           # Handler register POST
│   ├── logout.php             # Handler logout GET
│   └── pages/                 # Mirror exact dari pages/
│       └── users/
│           └── user-management.php  # Handler CRUD users
│
├── pages/                     # View files (konten halaman)
│   ├── dashboard.php
│   └── users/
│       └── user-management.php      # View CRUD users
│
├── functions/                 # Helper functions (WAJIB DIGUNAKAN)
│   ├── sanitasi.php          # sani() - Input sanitization
│   ├── secure_query.php      # querySecure(), executeSecure()
│   ├── generate_uuid.php     # generate_uuid()
│   ├── pagination.php        # makePagination(), showPagination()
│   ├── redirect.php          # redirectWithMessage(), showAlert()
│   ├── upload_file.php       # uploadFile() dengan kompresi
│   ├── auto-routing.php      # Variable-based routing system
│   ├── auto-cek-login-html.php   # Auto-login check untuk HTML pages
│   └── auto-cek-login-action.php # Auto-login check untuk action files
│
├── assets/                    # Static files
│   ├── css/
│   ├── js/
│   └── images/               # Upload destination
│
├── config.php                # Database connection ($con)
├── index.php                 # Entry point + router utama
├── sidebar.php               # Navigation menu
├── login.php                 # Login view
├── register.php              # Register view
└── generate.php              # CLI untuk generate files
```

---

## 🔄 Routing System (CRITICAL)

### URL ke File Conversion

**Rule utama:**
1. Underscore (`_`) di URL → Slash (`/`) di path file
2. Dash (`-`) di URL → Spasi di page title
3. Default page: `dashboard`
4. Routing menggunakan **variable $content** bukan function

**Contoh:**
```
URL: index.php?hal=users_user-management
→ File: pages/users/user-management.php
→ Title: User Management

URL: index.php?hal=admin_settings_config
→ File: pages/admin/settings/config.php
→ Title: Config
```

### Auto-Routing Implementation (auto-routing.php)

```php
<?php
$hal = 'dashboard';
$textTitle = 'Dashboard';
if (isset($_GET['hal'])) {
    $getHal = sani($_GET['hal']);
    $hal = str_replace('_', '/', $getHal);
    $lastUnderscore = strrpos($getHal, '_');
    $titlePart = ($lastUnderscore !== false) ? substr($getHal, $lastUnderscore + 1) : $getHal;
    $textTitle = ucwords(str_replace('-', ' ', $titlePart));
}
$content = 'pages/' . $hal . '.php';
?>
```

**Variable penting:**
- `$hal` = path file tanpa pages/ dan .php
- `$textTitle` = title halaman
- `$content` = full path ke file page untuk di-include

### Struktur Mirror (View ↔ Action)

**KONSEP KRITIS: VIEW VS ACTION**
File Action (`actions/...`) **TIDAK PERNAH DI-INCLUDE** di dalam View (`pages/...`). Action bertindak murni sebagai URL Endpoint yang di-*hit* melalui *form submission* (`action="..."`) atau tautan GET. Setelah diproses, Action akan selalu mengembalikan pengguna ke View menggunakan *Redirect* (`header('Location: ...')`).

**WAJIB mengikuti pattern ini:**
```
View:   pages/users/user-management.php
Action: actions/pages/users/user-management.php
```

**Form action HARUS ke actions dengan parameter hal:**
```html
<form action="actions/?hal=users_user-management" method="POST">
```

**Delete link juga ke actions:**
```html
<a href="actions/?hal=users_user-management&deleteUser=<?= $id ?>">
```

---

## 🌐 REST API Hybrid Framework

Framework ini mendukung Endpoint REST API yang berada di folder `api/endpoints/`. Router utamanya adalah `api/index.php`.

**Aturan Penulisan API:**
1. **Gunakan Helper Response:**
   Selalu gunakan `apiResponseSuccess()` dan `apiResponseError()` dari `functions/api.php` untuk membalas request. Jangan gunakan `echo json_encode` secara manual.
2. **Gunakan getApiInput():**
   Untuk membaca *Body Request* (baik dari JSON maupun `multipart/form-data`), gunakan fungsi `getApiInput()` lalu sanitasi.
   Contoh: `$input = sani(getApiInput());`
3. **Validasi Error (400):**
   Gunakan struktur array asosiatif jika ada validasi field.
   Contoh:
   ```php
   apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', [
       'email' => ['The email field is required.']
   ], 400);
   ```
4. **JWT Auth Otomatis:**
   Endpoint yang memerlukan *login* tidak perlu mengecek token secara manual. Token akan otomatis dicek oleh `api/index.php`. Payload *user* yang sukses masuk bisa diambil dari `$GLOBALS['api_user']`.

---

## 🔐 Security Patterns (MANDATORY)

### 1. CSRF Auto-Injection Protection

**SELURUH FORM POST otomatis terlindungi.**
Framework ini menggunakan **Output Buffering** (`ob_start()`) di `index.php`, `login.php`, dan `register.php` untuk otomatis mencari tag `<form method="POST">` dan menyuntikkan token `<input type="hidden" name="csrf_token">` ke dalamnya.
- Anda **TIDAK PERLU** menambah token CSRF secara manual di HTML.
- **Validasi Terpusat**: Setiap kali request POST ditembak ke `actions/index.php`, `actions/login.php`, atau `actions/register.php`, sistem akan langsung memvalidasi token CSRF. Jika gagal, akan di-redirect.

### 2. Input Sanitization

**SELALU gunakan sani() untuk SEMUA input:**
```php
// ✅ CORRECT
$name = sani($_POST['name']);
$email = sani($_GET['email']);
$search = sani($_REQUEST['search']);

// ❌ WRONG - NEVER do this
$name = $_POST['name'];
```

**sani() handle array otomatis:**
```php
$data = sani($_POST); // Semua value di array akan di-sanitize
```

### 2. Database Queries (WAJIB Prepared Statements)

**NEVER gunakan query langsung:**
```php
// ❌ WRONG - Vulnerable to SQL injection
$query = "SELECT * FROM users WHERE id = '$id'";
mysqli_query($con, $query);
```

**ALWAYS gunakan querySecure() untuk SELECT:**
```php
// ✅ CORRECT
$result = querySecure($con, 
    "SELECT * FROM users WHERE id = ?", 
    [$id], 
    's'  // s = string
);
$user = mysqli_fetch_assoc($result);
```

**ALWAYS gunakan executeSecure() untuk INSERT/UPDATE/DELETE:**
```php
// ✅ CORRECT - INSERT
$result = executeSecure($con,
    "INSERT INTO users (id, name, email) VALUES (?, ?, ?)",
    [$id, $name, $email],
    'sss'  // s s s = string string string
);

// ✅ CORRECT - UPDATE
$result = executeSecure($con,
    "UPDATE users SET name = ?, email = ? WHERE id = ?",
    [$name, $email, $id],
    'sss'
);

// ✅ CORRECT - DELETE
$result = executeSecure($con,
    "DELETE FROM users WHERE id = ?",
    [$id],
    's'
);
```

**Type codes untuk bind_param:**
- `s` = string
- `i` = integer
- `d` = double/float
- `b` = blob

### 3. Password Handling

**ALWAYS hash password sebelum save:**
```php
// ✅ CORRECT - Saat register/add user
$password = password_hash(sani($_POST['password']), PASSWORD_DEFAULT);

// ✅ CORRECT - Saat login verify
$result = querySecure($con, "SELECT * FROM users WHERE email = ?", [$email], 's');
$user = mysqli_fetch_assoc($result);

if ($user && password_verify($inputPassword, $user['password'])) {
    // Login success
}

// ✅ CORRECT - Saat update (optional)
$password_new = sani($_POST['password']);
$password = !empty($password_new) 
    ? password_hash($password_new, PASSWORD_DEFAULT) 
    : $password_old;  // Keep old if empty
```

### 4. Auto-Login System (Modular Approach)

**Framework menggunakan 3 file terpisah untuk auto-login:**

**1. functions/auto-cek-login-html.php** - Untuk halaman HTML (index.php):
```php
// Cek session, jika tidak login:
// 1. Tampilkan loading screen
// 2. JavaScript cek localStorage
// 3. Jika ada credentials → hit loginauto.php API
// 4. Jika berhasil → reload page
// 5. Jika gagal → redirect ke login.php
```

**2. functions/auto-cek-login-action.php** - Untuk action handlers (actions/index.php):
```php
// Cek session, jika tidak login:
// 1. Cek POST auto_login parameter
// 2. Verify credentials
// 3. Set session atau exit
```

**3. actions/loginauto.php** - API endpoint untuk auto-login:
```php
// Accept POST JSON request
// Return JSON response:
// { success: true/false, message: string, clear_storage: bool }
```

**Integration di index.php:**
```php
include 'functions/auto-cek-login-html.php'; // After routing
```

**Integration di actions/index.php:**
```php
include '../functions/auto-cek-login-action.php'; // After includes
```

**Security features:**
- API menggunakan JSON POST (bukan form)
- Password tetap di-verify dengan password_verify()
- Auto-clear localStorage jika credentials salah
- Session flag untuk clear localStorage

### 5. UUID Primary Keys

**ALWAYS gunakan UUID untuk primary key:**
```php
// ✅ CORRECT
$id = generate_uuid();
$result = executeSecure($con,
    "INSERT INTO users (id, name) VALUES (?, ?)",
    [$id, $name],
    'ss'
);

// ❌ WRONG - Never use auto-increment ID exposure
```

**Database schema HARUS:**
```sql
CREATE TABLE users (
    id VARCHAR(36) NOT NULL PRIMARY KEY,  -- UUID v4
    -- other fields
);
```

---

## 📝 CRUD Pattern (Template Wajib)

### View File Template

**Location:** `pages/module/feature.php`

```php
<?php
// Build query (No pagination/search manually. Let DataTables do it)
$query = "SELECT * FROM users ORDER BY id DESC";
$result = querySecure($con, $query, [], '');
?>

<!-- Button add new -->
<button data-bs-toggle="modal" data-bs-target="#addModal" class="btn btn-primary mb-3">Add New</button>

<!-- Data table with class 'datatable' for auto-initialization -->
<div class="table-responsive">
    <table class="table table-striped datatable">
        <thead>
            <tr>
                <th>No</th>
                <th>Name</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($result as $row): 
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $row['name'] ?></td>
                    <td>
                        <button onclick="editData('<?= $row['id'] ?>')" class="btn btn-sm btn-warning">Edit</button>
                        <!-- Hapus dengan class delete-btn untuk SweetAlert2 -->
                        <a href="actions/?hal=module_feature&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger delete-btn">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Add (Form MUST have class="ajax-form") -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <form action="actions/?hal=module_feature" method="POST" enctype="multipart/form-data" class="ajax-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="name" class="form-control" required>
                    <input type="email" name="email" class="form-control mt-2" required>
                    <input type="file" name="photo" class="form-control mt-2">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addData" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit (Form MUST have class="ajax-form") -->
<div class="modal fade" id="editModal">
    <!-- Same structure as Add Modal, but with name="updateData" on button -->
</div>

<script>
function editData(id) {
    // Fetch data via AJAX or set from PHP
    // Populate modal fields
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}
</script>
```

### Action File Template

**Location:** `actions/pages/module/feature.php`

**CRITICAL: Calculate relative path based on folder depth:**
- `actions/pages/users/` → root = `../../../` (3 levels)
- `actions/pages/admin/settings/` → root = `../../../../` (4 levels)

```php
<?php
/**
 * Action: module/feature
 * Relative path depth: 3 (actions/ → pages/ → module/)
 */

session_start();

// Calculate relative path (count folder depth)
$depth = 3; // actions/ (1) → pages/ (2) → module/ (3)
$rootPath = str_repeat('../', $depth);

include $rootPath . 'functions/sanitasi.php';
include $rootPath . 'functions/secure_query.php';
include $rootPath . 'functions/generate_uuid.php';
include $rootPath . 'config.php';

// POST Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ========== CREATE ==========
    if (isset($_POST['addData'])) {
        include $rootPath . 'functions/upload_file.php';
        
        $id = generate_uuid();
        $name = sani($_POST['name']);
        $email = sani($_POST['email']);
        
        // Handle file upload
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES['photo'], $rootPath . 'assets/images/photos/', 2 * 1024 * 1024);
            if ($result['success']) {
                $photo = str_replace($rootPath, '', $result['file_path']);
            }
        }
        
        // Insert with prepared statement
        $insertResult = executeSecure($con,
            "INSERT INTO table_name (id, name, email, photo) VALUES (?, ?, ?, ?)",
            [$id, $name, $email, $photo],
            'ssss'
        );
        
        if ($insertResult) {
            redirectWithMessage('../?hal=module_feature', 'Data successfully added!', 'success');
        } else {
            redirectWithMessage('../?hal=module_feature', 'Failed to add data!', 'error');
        }
    }
    
    // ========== UPDATE ==========
    if (isset($_POST['updateData'])) {
        include $rootPath . 'functions/upload_file.php';
        
        $id = sani($_POST['id']);
        $name = sani($_POST['name']);
        $email = sani($_POST['email']);
        $photo_old = sani($_POST['photo_old']);
        
        // Default: keep old photo
        $photo = $photo_old;
        
        // Handle new upload
        if (isset($_FILES['photo']) && 
            $_FILES['photo']['error'] === UPLOAD_ERR_OK && 
            !empty($_FILES['photo']['name'])) {
            
            $result = uploadFile($_FILES['photo'], $rootPath . 'assets/images/photos/', 2 * 1024 * 1024);
            
            if ($result['success']) {
                // Delete old file
                if (!empty($photo_old) && file_exists($rootPath . $photo_old)) {
                    unlink($rootPath . $photo_old);
                }
                $photo = str_replace($rootPath, '', $result['file_path']);
            }
        }
        
        // Update with prepared statement
        $updateResult = executeSecure($con,
            "UPDATE table_name SET name = ?, email = ?, photo = ? WHERE id = ?",
            [$name, $email, $photo, $id],
            'ssss'
        );
        
        if ($updateResult) {
            redirectWithMessage('../?hal=module_feature', 'Data successfully updated!', 'success');
        } else {
            redirectWithMessage('../?hal=module_feature', 'Failed to update data!', 'error');
        }
    }
    
} 
// ========== DELETE (GET) ==========
elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);
    
    // Get current data for file deletion
    $resultData = querySecure($con, "SELECT photo FROM table_name WHERE id = ?", [$id], 's');
    $data = mysqli_fetch_assoc($resultData);
    
    // Delete record
    $deleteResult = executeSecure($con, "DELETE FROM table_name WHERE id = ?", [$id], 's');
    
    if ($deleteResult) {
        // Delete associated file
        if (!empty($data['photo']) && file_exists($rootPath . $data['photo'])) {
            unlink($rootPath . $data['photo']);
        }
        
        redirectWithMessage('../?hal=module_feature', 'Data successfully deleted!', 'success');
    } else {
        redirectWithMessage('../?hal=module_feature', 'Failed to delete data!', 'error');
    }
// ========== Invalid Request ==========
else {
    header('Location: ' . $rootPath . 'index.php');
    exit;
}
?>
```

---

## 📤 File Upload Pattern

**ALWAYS gunakan uploadFile() function:**
```php
include '../../../functions/upload_file.php';

// Check if file uploaded
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $result = uploadFile(
        $_FILES['photo'], 
        '../../../assets/images/photos/',  // Target dir with trailing slash
        2 * 1024 * 1024,                    // Max 2MB
        ['jpg', 'jpeg', 'png']              // Optional: allowed types
    );
    
    if ($result['success']) {
        $photo = str_replace('../../../', '', $result['file_path']);
        // Save $photo to database
    } else {
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = 'error';
    }
}
```

**Update pattern (dengan delete old file):**
```php
// Get current file from database
$resultData = querySecure($con, "SELECT photo FROM table WHERE id = ?", [$id], 's');
$currentData = mysqli_fetch_assoc($resultData);
$photo = $currentData['photo']; // Default: keep old

// Check for new upload
if (isset($_FILES['photo']) && 
    $_FILES['photo']['error'] === UPLOAD_ERR_OK && 
    !empty($_FILES['photo']['name'])) {
    
    $result = uploadFile($_FILES['photo'], '../../../assets/images/photos/', 2 * 1024 * 1024);
    
    if ($result['success']) {
        // Delete old file
        if (!empty($photo) && file_exists('../../../' . $photo)) {
            unlink('../../../' . $photo);
        }
        $photo = str_replace('../../../', '', $result['file_path']);
    }
}

// Continue with UPDATE query...
```

---

## 🔄 Pagination & Table Pattern

**ALWAYS gunakan DataTables Native:**
Jangan lagi menggunakan fungsi manual `makePagination()` atau form pencarian manual di PHP. Biarkan DataTables di Client-Side yang mengurus semuanya.

1. Selalu tambahkan class `datatable` pada tag `<table>`.
2. Selalu bungkus table di dalam `<div class="table-responsive">`.
3. Gunakan loop `foreach ($result as $row)` standar untuk me-render semua data. DataTables akan merender UI tabelnya di `spa.js`.
4. Hapus form pencarian `GET` dan link paginasi manual.

## 🔔 Redirect & Alert Pattern

**ALWAYS gunakan fungsi `redirectWithMessage()` di Controller/Action:**

```php
// Action handler: Akan otomatis dideteksi SPA engine dan me-render Bootstrap Toast di frontend
redirectWithMessage('../?hal=users_user-management', 'Data berhasil ditambahkan!', 'success');
redirectWithMessage('../?hal=users_user-management', 'Terjadi kesalahan.', 'error');
```

**Penting:** Tidak perlu lagi memanggil `showAlert()` di view PHP. SPA JS akan mengurus memunculkan Toast hijau atau merah secara mandiri dari response AJAX ini.

---

## 🔧 Generator Usage

**ALWAYS gunakan generate.php untuk buat module baru:**
```bash
php generate.php module_feature
```

**Output:**
- `pages/module/feature.php` → View template
- `actions/pages/module/feature.php` → Action handler template
- Relative path otomatis calculated

**Setelah generate:**
1. Edit view file → tambah form, table, modal
2. Edit action file → implement CRUD logic
3. Add menu ke `sidebar.php`

---

## 📋 Checklist Saat Develop Feature Baru

### ✅ Planning
- [ ] Determine module name & feature name
- [ ] Run generator: `php generate.php module_feature`
- [ ] Create database table dengan UUID primary key
- [ ] Add menu item di `sidebar.php`

### ✅ View File (pages/)
- [ ] No manual search box, rely on DataTables.
- [ ] Use `class="datatable"` for tables.
- [ ] Create modal add dengan action ke `actions/?hal=...`
- [ ] Modal form MUST have `class="ajax-form"` for SPA integration.
- [ ] Add delete link dengan class `delete-btn` ke `actions/?hal=...&delete=id`
- [ ] No manual pagination code in PHP.

### ✅ Action File (actions/pages/)
- [ ] Calculate correct relative path depth
- [ ] ALWAYS use `redirectWithMessage()` instead of `echo <script>` or raw headers.
- [ ] Include all required functions (sanitasi, secure_query, upload_file, etc)
- [ ] Implement CREATE dengan generate_uuid()
- [ ] Implement UPDATE dengan file handling (keep old if no new)
- [ ] Implement DELETE dengan file cleanup
- [ ] ALWAYS use executeSecure() / querySecure()
- [ ] ALWAYS sanitize all input dengan sani()
- [ ] Set session message untuk feedback
- [ ] Redirect ke index.php dengan parameter hal

### ✅ Security Check
- [ ] All inputs di-sanitize dengan sani()
- [ ] All queries menggunakan prepared statements
- [ ] Password di-hash dengan password_hash()
- [ ] File upload menggunakan uploadFile()
- [ ] UUID untuk primary key
- [ ] No direct mysqli_query() usage
- [ ] No SQL string concatenation

### ✅ Testing
- [ ] Test add data (dengan & tanpa file upload)
- [ ] Test update data (dengan & tanpa file upload)
- [ ] Test delete data (file terhapus?)
- [ ] Test search functionality
- [ ] Test pagination (multiple pages)
- [ ] Test SQL injection (input dengan quotes)
- [ ] Test file upload validation (size, type)
- [ ] Test session message display

---

## ⚠️ Common Mistakes (AVOID)

### ❌ Path Errors
```php
// ❌ WRONG - Hardcoded path depth
include '../../config.php';  // Might be wrong depth

// ✅ CORRECT - Calculate based on actual folder structure
// For actions/pages/users/file.php → root is ../../../
include '../../../config.php';
```

### ❌ Direct Query
```php
// ❌ WRONG - SQL injection vulnerable
$id = $_GET['id'];
$query = "SELECT * FROM users WHERE id = '$id'";
$result = mysqli_query($con, $query);

// ✅ CORRECT - Prepared statement
$id = sani($_GET['id']);
$result = querySecure($con, "SELECT * FROM users WHERE id = ?", [$id], 's');
```

### ❌ No Sanitization
```php
// ❌ WRONG - XSS vulnerable
$name = $_POST['name'];

// ✅ CORRECT
$name = sani($_POST['name']);
```

### ❌ File Upload Bug
```php
// ❌ WRONG - Will cause "undefined" if no new upload
$photo = $_FILES['photo'];

// ✅ CORRECT - Keep old if no new upload
$photo = $photo_old; // Default
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Upload new file
}
```

### ❌ Password Handling
```php
// ❌ WRONG - Plain text password
$password = $_POST['password'];

// ✅ CORRECT - Hash password
$password = password_hash(sani($_POST['password']), PASSWORD_DEFAULT);
```

### ❌ Auto-increment ID
```php
// ❌ WRONG - Sequential ID exposure
$query = "INSERT INTO users (name) VALUES (?)"; // id auto-increment

// ✅ CORRECT - UUID
$id = generate_uuid();
$query = "INSERT INTO users (id, name) VALUES (?, ?)";
```

---

## 🎯 Code Style Guide

### Naming Conventions
- **Variables:** `$snake_case`
- **Functions:** `camelCase()`
- **Constants:** `UPPER_CASE`
- **Files:** `kebab-case.php`
- **Folders:** `lowercase`

### PHP Code Style
```php
// Include statements at top
include 'config.php';

// Session operations
session_start();

// Constants
define('MAX_SIZE', 2097152);

// Variables
$user_name = sani($_POST['name']);
$user_email = sani($_POST['email']);

// Control structures (space before parenthesis)
if ($condition) {
    // Code
}

// Functions
function generateUuid()
{
    // Code
}
```

---

## 📚 Function Reference Quick Guide

### sani($data)
- Sanitize input (string or array)
- Always use for $_POST, $_GET, $_REQUEST

### querySecure($con, $query, $params, $types)
- For SELECT queries
- Returns mysqli_result

### executeSecure($con, $query, $params, $types)
- For INSERT/UPDATE/DELETE
- Returns boolean or last insert id

### generate_uuid()
- Returns UUID v4 string (36 chars)
- Use for all primary keys

### uploadFile($file, $targetDir, $maxSize, $allowedTypes)
- Upload dengan auto-compression
- Returns array with success/message/file_path

### makePagination($con, $query, $params = [], $types = '', $jumlahLimit = 10)
- Database pagination dengan signature aktual: `makePagination($con, $query, $params = [], $types = '', $jumlahLimit = 10)`
- Gunakan `$params` dan `$types` untuk keamanan ekstra pada parameter pencarian (bind param otomatis limit/offset).
- Returns array with data, total_pages, etc

### showPagination($totalPages, $currentPage = 1, $maxLinks = 5)
- Display Bootstrap pagination UI
- Signature aktual: `showPagination($totalPages, $currentPage = 1, $maxLinks = 5)`

### redirectWithMessage($url, $message = '', $type = 'success')
- Set `$_SESSION['message']` + `$_SESSION['message_type']`
- Redirect menggunakan JavaScript ke URL tujuan

### showAlert($message = null, $type = 'success')
- Jika dipanggil tanpa parameter: ambil message dari session lalu auto-unset
- Return HTML alert (`alert-success` / `alert-danger`)

---

## 🚀 Development Workflow

1. **Plan Feature**
   - Define module & feature name
   - Design database table

2. **Generate Files**
   ```bash
   php generate.php module_feature
   ```

3. **Setup Database**
   ```sql
   CREATE TABLE table_name (
       id VARCHAR(36) PRIMARY KEY,
       -- fields
   );
   ```

4. **Add Menu**
   - Edit `sidebar.php`
   - Add menu item dengan parameter hal

5. **Develop View**
   - Edit `pages/module/feature.php`
   - Add search, table, modals

6. **Develop Action**
   - Edit `actions/pages/module/feature.php`
   - Implement CRUD dengan security patterns

7. **Test & Debug**
   - Test all CRUD operations
   - Check security (sanitization, prepared statements)
   - Test file upload & deletion

8. **Deploy**
   - Review checklist
   - Test in production environment

---

## 🔒 Security Reminder

**NEVER SKIP:**
1. ✅ sani() untuk semua input
2. ✅ Prepared statements untuk semua query
3. ✅ password_hash() untuk password
4. ✅ UUID untuk primary key
5. ✅ uploadFile() untuk file upload
6. ✅ File validation (size, type)
7. ✅ Error logging (jangan expose ke user)
8. ✅ Session message untuk feedback

**FRAMEWORK SUDAH PROVIDE:**
- Input sanitization function
- Prepared statement wrappers
- UUID generator
- File upload dengan validation
- Password hashing examples
- Pagination dengan security

**YOUR JOB:**
- ALWAYS gunakan function yang ada
- NEVER bypass security measures
- ALWAYS validate input
- ALWAYS handle errors gracefully

---

## 💡 Tips untuk AI/Copilot

1. **Saat diminta buat CRUD:**
   - Generate files dengan `generate.php` dulu
   - Follow template pattern yang ada
   - Jangan lupa relative path calculation

2. **Saat diminta fix bug:**
   - Check relative path depth
   - Verify sanitization usage
   - Check prepared statement types

3. **Saat diminta add feature:**
   - Maintain consistency dengan existing code
   - Use helper functions yang sudah ada
   - Follow security patterns

4. **Saat diminta optimize:**
   - Check query efficiency (indexes)
   - Verify pagination implementation
   - Check file upload compression

5. **Saat diminta refactor:**
   - Maintain backward compatibility
   - Keep security measures
   - Update documentation

---

**Framework ini dirancang untuk security & maintainability. SELALU ikuti patterns yang sudah ada!**
