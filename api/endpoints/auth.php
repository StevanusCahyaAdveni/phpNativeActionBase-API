<?php
/**
 * API Endpoint: auth
 * Route: api/index.php?hal=auth
 * 
 * Handles user authentication and issues JWT tokens.
 */

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = getJsonInput();
    
    // Validate inputs
    $email = sani($input['email'] ?? '');
    $password = sani($input['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        apiResponseError('VALIDATION_ERROR', 'The given data was invalid.', [
            'email' => empty($email) ? ['The email field is required.'] : [],
            'password' => empty($password) ? ['The password field is required.'] : []
        ], 400);
    }
    
    // Check database
    $result = querySecure($con, "SELECT id, fullname, username, email, password FROM users WHERE email = ?", [$email], 's');
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        // Valid credentials, generate token
        $payload = [
            'id' => $user['id'],
            'email' => $user['email'],
            'fullname' => $user['fullname']
        ];
        
        $token = generate_jwt($payload, 86400); // 24 hours expiry
        
        // Remove password from response
        unset($user['password']);
        
        apiResponseSuccess('Authentication successful', [
            'token' => $token,
            'user' => $user
        ]);
    } else {
        apiResponseError('UNAUTHORIZED', 'Invalid email or password', null, 401);
    }
} else {
    apiResponseError('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
}
?>
