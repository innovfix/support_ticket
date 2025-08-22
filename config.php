<?php
/**
 * Query Desk - Hosting Configuration
 * This configuration is for production hosting
 * For local development, update with your XAMPP database details
 */

// Database Configuration (Production Hosting)
define('DB_HOST', 'localhost');          // Usually 'localhost' for shared hosting
define('DB_NAME', 'u743445510_hima_support'); // Your hosting database name
define('DB_USER', 'u743445510_hima_support');   // Your hosting database username
define('DB_PASS', 'HimaSupport@2025');   // Your hosting database password
define('DB_PORT', '3306');                // Default MySQL port

// Application Configuration (Production)
define('APP_URL', 'https://ticket.himaapp.in/query-desk/');  // Your website URL
define('UPLOAD_PATH', __DIR__ . '/uploads/');             // Upload directory path

// Security Configuration (Production - HTTPS enabled)
define('ENABLE_HTTPS', true);             // Enable HTTPS redirect in production
define('SESSION_SECURE', true);           // Session cookies secure over HTTPS

// Error Reporting (Disable for production)
define('SHOW_ERRORS', false);

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

// Helper Functions
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           $_SERVER['SERVER_PORT'] == 443;
}

function force_https() {
    if (ENABLE_HTTPS && !is_https()) {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url", true, 301);
        exit();
    }
}

// Force HTTPS if enabled
if (ENABLE_HTTPS) {
    force_https();
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
