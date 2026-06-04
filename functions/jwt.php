<?php

/**
 * Custom JWT Implementation for REST API
 * Stateless, fast, and secure token mechanism.
 */

// Basic Base64 URL encode
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Basic Base64 URL decode
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generate a custom JWT Token
 * 
 * @param array $payload Data to embed in the token (e.g., user id, email)
 * @param int $expiry Expiry time in seconds (default 24 hours)
 * @return string JWT Token
 */
function generate_jwt($payload, $expiry = 86400) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // Add issued at (iat) and expiration (exp)
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiry;
    
    $payload_encoded = json_encode($payload);

    $base64UrlHeader = base64url_encode($header);
    $base64UrlPayload = base64url_encode($payload_encoded);

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64url_encode($signature);

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Verify and decode a JWT Token
 * 
 * @param string $token JWT Token
 * @return array|false Returns the payload array if valid, false if invalid or expired
 */
function verify_jwt($token) {
    $tokenParts = explode('.', $token);
    
    if (count($tokenParts) != 3) {
        return false;
    }

    $header = $tokenParts[0];
    $payload = $tokenParts[1];
    $signature_provided = $tokenParts[2];

    // Re-create signature
    $signature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true);
    $base64UrlSignature = base64url_encode($signature);

    // Verify signature
    if (!hash_equals($base64UrlSignature, $signature_provided)) {
        return false;
    }

    $payload_decoded = json_decode(base64url_decode($payload), true);

    // Verify expiration
    if (isset($payload_decoded['exp']) && $payload_decoded['exp'] < time()) {
        return false; // Token has expired
    }

    return $payload_decoded;
}
