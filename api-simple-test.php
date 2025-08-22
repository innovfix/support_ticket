<?php
/**
 * Simple API Test - No Complex Dependencies
 * This file tests basic API functionality from the root directory
 */

// Set JSON header
header('Content-Type: application/json');

try {
    echo json_encode([
        'status' => 'success',
        'message' => 'Simple API test working',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'current_file' => __FILE__,
        'current_dir' => __DIR__,
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'API test failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
