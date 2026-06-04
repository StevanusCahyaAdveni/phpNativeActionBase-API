<?php
    function createLog($con, $user, $description) {
        // Get user IP address
        $UserIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        // Check for proxy or load balancer
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $UserIp = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can contain multiple IPs, get the first one
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $UserIp = trim($ipList[0]);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $query = "INSERT INTO logs (user, description, ip_address, timestamp) VALUES (?, ?, ?, ?)";
        executeSecure($con, $query, [$user, $description, $UserIp, $timestamp], 'ssss');
    }
?>