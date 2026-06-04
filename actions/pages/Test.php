<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $hehehe = sani($_POST['hehehe']);
        $query = "INSERT INTO test (id, hehehe) VALUES (?, ?)";
        $params = [$id, $hehehe];
        $types = 'ss';
        $insertResult = executeSecure($con, $query, $params, $types);

        if ($insertResult) {
            $_SESSION['message'] = 'Data berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat menambahkan data.';
            $_SESSION['message_type'] = 'error';
        }
        echo "
            <script>
                window.location.href = '../?hal=Test';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $hehehe = sani($_POST['hehehe']);
        $query = "UPDATE test SET hehehe = ? WHERE id = ?";
        $params = [$hehehe, $id];
        $types = 'ss';
        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            $_SESSION['message'] = 'Data berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat memperbarui data.';
            $_SESSION['message_type'] = 'error';
        }
        echo "
            <script>
                window.location.href = '../?hal=Test';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM test WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = '../?hal=Test';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
