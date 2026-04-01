<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addUser'])) {
        include '../functions/upload_file.php';
        $id = generate_uuid();
        $fullname = sani($_POST['fullname']);
        $username = sani($_POST['username']);
        $email = sani($_POST['email']);
        $password = sani($_POST['password']);
        $photo_profile = null;

        if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] === UPLOAD_ERR_OK && !empty($_FILES['photo_profile']['name'])) {
            $result = uploadFile($_FILES['photo_profile'], '../assets/images/photo_profile/', 5 * 1024 * 1024);
            if ($result['success']) {
                $photo_profile = str_replace('../', '', $result['file_path']);
            } else {
                redirectWithMessage('../?hal=users_user-management', 'Upload gagal: ' . $result['message'], 'error');
            }
        }

        $query = "INSERT INTO users (id, fullname, username, email, password, photo_profile) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$id, $fullname, $username, $email, password_hash($password, PASSWORD_DEFAULT), $photo_profile];
        $types = 'ssssss';
        $insertResult = executeSecure($con, $query, $params, $types);

        if ($insertResult) {
            createLog($con, $_SESSION['admin']['email'], 'Successful user addition ' . $fullname);
            redirectWithMessage('../?hal=users_user-management', 'Data berhasil ditambahkan!', 'success');
        }

        redirectWithMessage('../?hal=users_user-management', 'Gagal menambahkan data user.', 'error');
    }

    if (isset($_POST['updateUser'])) {
        include '../functions/upload_file.php';
        $id = sani($_POST['id']);
        $fullname = sani($_POST['fullname']);
        $username = sani($_POST['username']);
        $email = sani($_POST['email']);
        $password = sani($_POST['password']);
        $password_old = sani($_POST['password_old']);

        $resultGetSingleUser = querySecure($con, "SELECT photo_profile FROM users WHERE id = ?", [$id], 's');
        $singleUser = mysqli_fetch_assoc($resultGetSingleUser);

        $photo_profile_old = $singleUser['photo_profile'];

        // Default: gunakan foto lama
        $photo_profile = $photo_profile_old;

        // Cek apakah ada file yang diupload
        if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] === UPLOAD_ERR_OK && !empty($_FILES['photo_profile']['name']) && $_FILES['photo_profile']['size'] > 0 && isset($_FILES['photo_profile']['name'])) {
            $result = uploadFile($_FILES['photo_profile'], '../assets/images/photo_profile/', 5 * 1024 * 1024);

            if ($result['success']) {
                // Hapus foto lama jika ada dan berbeda
                if (!empty($photo_profile_old) && file_exists('../' . $photo_profile_old)) {
                    unlink('../' . $photo_profile_old);
                }
                $photo_profile = str_replace('../', '', $result['file_path']);
            } else {
                $_SESSION['message'] = 'Upload gagal: ' . $result['message'];
                $_SESSION['message_type'] = 'error';
            }
        }
        $photo_profile == 'undefined' ? $photo_profile = $photo_profile_old : '';

        // Handle password
        if (!empty($password) && $password != '') {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $password_hashed = $password_old;
        }

        $query = "UPDATE users SET fullname = ?, username = ?, email = ?, password = ?, photo_profile = ? WHERE id = ?";
        $params = [$fullname, $username, $email, $password_hashed, $photo_profile, $id];
        $types = 'ssssss';
        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            createLog($con, $_SESSION['admin']['email'], 'Successful user update ' . $fullname);
            redirectWithMessage('../?hal=users_user-management', 'Data berhasil diperbarui!', 'success');
        }

        redirectWithMessage('../?hal=users_user-management', 'Gagal memperbarui data user.', 'error');
    }
    exit;
} elseif (isset($_GET['deleteUser'])) {
    $id = sani($_GET['deleteUser']);

    // Dapatkan data user untuk menghapus foto profile
    $resultGetUser = querySecure($con, "SELECT fullname, photo_profile FROM users WHERE id = ?", [$id], 's');
    $user = mysqli_fetch_assoc($resultGetUser);
    $photo_profile = $user['photo_profile'];

    // Hapus data user
    $deleteResult = executeSecure($con, "DELETE FROM users WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        createLog($con, $_SESSION['admin']['email'], 'Successful user deletion ' . $user['fullname']);
        if (!empty($photo_profile) && file_exists('../' . $photo_profile)) {
            unlink('../' . $photo_profile);
        }
        redirectWithMessage('../?hal=users_user-management', 'User berhasil dihapus!', 'success');
    }

    redirectWithMessage('../?hal=users_user-management', 'Gagal menghapus user.', 'error');
    exit;
} else {
    // If accessed directly, redirect to homepage
    redirectWithMessage('../../index.php', 'Akses tidak valid.', 'error');
}
