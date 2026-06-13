<?php
function redirectWithMessage($url, $message = '', $type = 'success')
{
    // Deteksi Request AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $type === 'success',
            'message' => $message,
            'type'    => $type,
            'redirect'=> $url
        ]);
        exit;
    }

    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    echo "<script>
                alert('{$message}');
                window.location.href = '{$url}';
              </script>";
    exit;
}

function showAlert($message = null, $type = 'success')
{
    if ($message === null) {
        if (!isset($_SESSION['message'])) {
            return '';
        }

        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'success';
        unset($_SESSION['message'], $_SESSION['message_type']);
    }

    $alertClass = ($type === 'error') ? 'alert-danger' : 'alert-success';
    return "<div class='alert {$alertClass}' role='alert'>{$message}</div>";
}
