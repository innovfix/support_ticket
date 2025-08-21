<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Suppress warnings for CLI execution
if (php_sapi_name() !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }
}

function env(string $key, ?string $default = null): ?string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // Try to use hosting configuration first
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
        try {
            $pdo = get_hosting_pdo();
            error_log("Successfully connected to hosting database");
            return $pdo;
        } catch (Exception $e) {
            error_log("Hosting database connection failed: " . $e->getMessage());
            // Fall back to environment variables
        }
    }
    
    // Try to use main project config if hosting config doesn't exist
    if (file_exists(__DIR__ . '/../../hima-support/config.php')) {
        require_once __DIR__ . '/../../hima-support/config.php';
        try {
            $pdo = get_hosting_pdo();
            error_log("Successfully connected to main project database");
            return $pdo;
        } catch (Exception $e) {
            error_log("Main project database connection failed: " . $e->getMessage());
            // Fall back to environment variables
        }
    }

    // Fallback to environment variables (for local development)
    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
      $db   = env('DB_NAME', 'u743445510_hima_support');
    $user = env('DB_USER', 'u743445510_hima_support');
    $pass = env('DB_PASS', 'HimaSupport@2025');

    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        error_log("Successfully connected to local database: $host:$port/$db");
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw $e;
    }
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response($data, int $status = 200): void {
    // Ensure no output has been sent before
    if (headers_sent()) {
        error_log("Headers already sent, cannot send JSON response");
        return;
    }
    
    http_response_code($status);
    header('Content-Type: application/json');
    
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("JSON encoding failed: " . json_last_error_msg());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error - JSON encoding failed']);
        exit;
    }
    
    echo $json;
    exit;
}

function bad_request(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
}



