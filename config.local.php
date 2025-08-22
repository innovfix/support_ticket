<?php
/**
 * Query Desk - Local Development Configuration
 * This configuration is for local XAMPP development
 */

// Database Configuration (Local XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'hima');  // Your local database name
define('DB_USER', 'root');  // Default XAMPP username
define('DB_PASS', '');      // Default XAMPP password (empty)
define('DB_PORT', '3306');

// Application Configuration (Local)
define('APP_URL', 'http://localhost/hima-support/');  // Your local URL
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Security Configuration (Local - HTTP for development)
define('ENABLE_HTTPS', false);  // Disable HTTPS for local development
define('SESSION_SECURE', false);

// Error Reporting (Enabled for development)
define('SHOW_ERRORS', true);

// Database Connection Function
function get_hosting_pdo() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', 
            DB_HOST, DB_PORT, DB_NAME);
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

// Helper Functions (simplified for local development)
function is_https() {
    return false; // Always false for local development
}

function force_https() {
    // Do nothing for local development
    return;
}

// Error reporting
if (SHOW_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
