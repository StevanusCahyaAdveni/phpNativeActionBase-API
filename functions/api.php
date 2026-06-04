<?php

/**
 * REST API Helper Functions
 */

/**
 * Return a standardized JSON response for SUCCESS
 * 
 * @param string $message Response message
 * @param mixed $data Data payload to return
 * @param int $httpCode HTTP Status Code
 */
function apiResponseSuccess($message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Return a standardized JSON response for ERROR
 * 
 * @param string $code Error code (e.g., 'UNAUTHORIZED', 'VALIDATION_ERROR')
 * @param string $message Error message
 * @param mixed $details Detailed error information (e.g., validation messages)
 * @param int $httpCode HTTP Status Code
 */
function apiResponseError($code, $message, $details = null, $httpCode = 400) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
            'details' => $details
        ]
    ]);
    exit;
}

/**
 * Read API input (Supports JSON and Form-Data)
 * 
 * @return array Parsed input as associative array
 */
function getApiInput() {
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    // Jika format JSON
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
    
    // Jika format form-data atau urlencoded (yang digunakan saat upload file)
    return $_POST;
}

/**
 * (Deprecated) Read raw JSON input from request body. Use getApiInput() instead.
 */
function getJsonInput() {
    return getApiInput();
}

/**
 * Extract Bearer Token from Authorization Header
 * 
 * @return string|null The token or null if not present
 */
function getBearerToken() {
    $headers = null;
    
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
