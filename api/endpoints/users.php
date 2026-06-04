<?php
/**
 * API Endpoint: users
 * Route: api/index.php?hal=users
 * 
 * Handles CRUD operations for users.
 * Requires JWT Authentication (checked in api/index.php)
 */

include_once '../functions/upload_file.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Cek apakah minta 1 data (pakai ?id=...) atau semua
        if (isset($_GET['id'])) {
            $id = sani($_GET['id']);
            $res = querySecure($con, "SELECT id, fullname, username, email, photo_profile, created_at FROM users WHERE id = ?", [$id], 's');
            $data = mysqli_fetch_assoc($res);
            
            if ($data) {
                apiResponseSuccess('Data found', $data);
            } else {
                apiResponseError('NOT_FOUND', 'Data not found', null, 404);
            }
        } else {
            // Pencarian & Pagination
            $search = sani($_GET['search'] ?? '');
            $whereClause = "";
            $params = [];
            $types = "";
            
            if (!empty($search)) {
                $whereClause = " AND (fullname LIKE ? OR username LIKE ? OR email LIKE ?)";
                $searchWildcard = '%' . $search . '%';
                $params = [$searchWildcard, $searchWildcard, $searchWildcard];
                $types = "sss";
            }
            
            // Limit per halaman
            $limit = isset($_GET['limit']) ? (int) sani($_GET['limit']) : 10;
            if ($limit < 1 || $limit > 100) $limit = 10;
            
            $query = "SELECT id, fullname, username, email, photo_profile, created_at FROM users WHERE 1 = 1 " . $whereClause . " ORDER BY created_at DESC";
            
            // Menggunakan fungsi pagination existing
            $pagination = makePagination($con, $query, $params, $types, $limit);
            
            apiResponseSuccess('List users', $pagination);
        }
        break;

    case 'POST':
        // Insert DB
        $input = sani(getApiInput());
        
        $fullname = $input['fullname'] ?? '';
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
            $errors = [];
            if(empty($fullname)) $errors['fullname'] = ['The fullname field is required.'];
            if(empty($username)) $errors['username'] = ['The username field is required.'];
            if(empty($email)) $errors['email'] = ['The email field is required.'];
            if(empty($password)) $errors['password'] = ['The password field is required.'];
            
            apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', $errors, 400);
        }
        
        // Cek unik
        $check = querySecure($con, "SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username], 'ss');
        if (mysqli_num_rows($check) > 0) {
            apiResponseError('CONFLICT', 'Email or Username already exists', null, 409);
        }
        
        // Handle Photo Upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['photo'], '../assets/images/photos/', 5 * 1024 * 1024);
            if ($uploadResult['success']) {
                $photoPath = str_replace('../', '', $uploadResult['file_path']);
            } else {
                apiResponseError('UPLOAD_ERROR', $uploadResult['message'], null, 400);
            }
        }
        
        $id = generate_uuid();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $result = executeSecure($con, 
            "INSERT INTO users (id, fullname, username, email, password, photo_profile, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$id, $fullname, $username, $email, $hashedPassword, $photoPath],
            'ssssss'
        );
        
        if ($result) {
            apiResponseSuccess('User created successfully', ['id' => $id], 201);
        } else {
            apiResponseError('INTERNAL_ERROR', 'Failed to create user', null, 500);
        }
        break;

    case 'PUT':
        // Update DB
        $input = sani(getApiInput());
        
        // Dalam metode PUT, file $_FILES sering tidak terbaca secara natif di PHP
        // Solusi jika ada upload file di update, client sebaiknya pakai POST (dengan method spoofing)
        // Namun kita tetap akomodasi jika input masuk
        
        $id = $input['id'] ?? '';
        $fullname = $input['fullname'] ?? '';
        $username = $input['username'] ?? '';
        $email = $input['email'] ?? '';
        
        if (empty($id) || empty($fullname) || empty($username) || empty($email)) {
            $errors = [];
            if(empty($id)) $errors['id'] = ['The id field is required.'];
            if(empty($fullname)) $errors['fullname'] = ['The fullname field is required.'];
            if(empty($username)) $errors['username'] = ['The username field is required.'];
            if(empty($email)) $errors['email'] = ['The email field is required.'];
            
            apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', $errors, 400);
        }
        
        // Check if user exists
        $check = querySecure($con, "SELECT id FROM users WHERE id = ?", [$id], 's');
        if (mysqli_num_rows($check) === 0) {
            apiResponseError('NOT_FOUND', 'User not found', null, 404);
        }
        
        $result = executeSecure($con, 
            "UPDATE users SET fullname = ?, username = ?, email = ? WHERE id = ?",
            [$fullname, $username, $email, $id],
            'ssss'
        );
        
        if ($result) {
            apiResponseSuccess('User updated successfully');
        } else {
            apiResponseError('INTERNAL_ERROR', 'Failed to update user', null, 500);
        }
        break;
        
    case 'DELETE':
        $input = sani(getApiInput());
        $id = $input['id'] ?? ($_GET['id'] ?? '');
        
        if (empty($id)) {
            apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', ['id' => ['The id field is required.']], 400);
        }
        
        // Mencegah delete akun sendiri
        if (isset($GLOBALS['api_user']['id']) && $GLOBALS['api_user']['id'] === $id) {
            apiResponseError('FORBIDDEN', 'You cannot delete your own account', null, 403);
        }
        
        $result = executeSecure($con, "DELETE FROM users WHERE id = ?", [$id], 's');
        
        if ($result) {
            apiResponseSuccess('User deleted successfully');
        } else {
            apiResponseError('NOT_FOUND', 'Failed to delete user or user not found', null, 404);
        }
        break;

    default:
        apiResponseError('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
        break;
}
?>
