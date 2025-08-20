<?php
/**
 * Query Desk - Example Hosting Configuration
 *
 * Copy this file to `config.php` and fill in your real credentials locally.
 * NEVER commit `config.php` to Git. It is ignored via .gitignore.
 */

// Database Configuration (replace with your hosting provider details)
define('DB_HOST', 'localhost');           // e.g. 'localhost'
define('DB_NAME', 'YOUR_DB_NAME');        // e.g. 'hima_support'
define('DB_USER', 'YOUR_DB_USER');        // e.g. 'db_user'
define('DB_PASS', 'YOUR_DB_PASSWORD');    // e.g. 'strong-password-here'
define('DB_PORT', '3306');                // Usually '3306' for MySQL

// Application Configuration
define('APP_URL', 'https://your-domain.example.com/query-desk/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Security Configuration
define('ENABLE_HTTPS', true);
define('SESSION_SECURE', true);

// Error Reporting (Set to false for production)
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
	if (!is_https() && !headers_sent()) {
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


