<?php
/**
 * Simple API Test - Debug Production Issues
 * This file tests basic API functionality without complex logic
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

echo json_encode([
    'status' => 'API test started',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'current_dir' => __DIR__,
    'config_paths' => [
        'relative' => __DIR__ . '/../config.php',
        'absolute' => realpath(__DIR__ . '/../config.php'),
        'exists' => file_exists(__DIR__ . '/../config.php')
    ]
]);
?>
