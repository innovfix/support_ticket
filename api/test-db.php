<?php
/**
 * Database Connection Test
 * This file tests if the database connection is working
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

try {
    // Try to include the bootstrap file
    if (file_exists(__DIR__ . '/_bootstrap.php')) {
        require_once __DIR__ . '/_bootstrap.php';
        
        // Try to get database connection
        if (function_exists('get_pdo')) {
            $pdo = get_pdo();
            
            // Test a simple query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM staff_users");
            $result = $stmt->fetch();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection successful',
                'staff_count' => $result['count'],
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => phpversion()
            ]);
            
        } else {
            throw new Exception('get_pdo function not found');
        }
        
    } else {
        throw new Exception('_bootstrap.php file not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database test failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>





