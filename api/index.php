<?php
/**
 * Main API Router
 * Entry point for all REST API endpoints.
 */

// Handle preflight CORS requests if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include configurations and functions
include '../config.php';
include '../functions/sanitasi.php';
include '../functions/secure_query.php';
include '../functions/generate_uuid.php';
include '../functions/jwt.php';
include '../functions/api.php';
include '../functions/pagination.php';

// Parse hal parameter
$hal = isset($_GET['hal']) ? sani($_GET['hal']) : '';
if (empty($hal)) {
    apiResponseError('BAD_REQUEST', 'Parameter hal not specified', null, 400);
}

// Convert underscore to slash for folder structure (same as UI)
$endpointPath = 'endpoints/' . str_replace('_', '/', $hal) . '.php';

// -------------------------------------------------------------
// JWT Middleware Authentication
// -------------------------------------------------------------
// Public endpoints that don't require JWT token
$publicEndpoints = [
    'auth',       // Login endpoint
    'register'    // Register endpoint (if needed)
];

if (!in_array($hal, $publicEndpoints)) {
    $token = getBearerToken();
    if (!$token) {
        apiResponseError('UNAUTHORIZED', 'Authorization Token not found', null, 401);
    }
    
    $payload = verify_jwt($token);
    if (!$payload) {
        apiResponseError('UNAUTHORIZED', 'Token has expired or is invalid.', null, 401);
    }
    
    // Pass user payload to the endpoint via global variable
    $GLOBALS['api_user'] = $payload;
}

// -------------------------------------------------------------
// Endpoint Routing
// -------------------------------------------------------------
if (file_exists($endpointPath)) {
    include $endpointPath;
} else {
    apiResponseError('NOT_FOUND', 'Endpoint not found: ' . $hal, null, 404);
}
?>
