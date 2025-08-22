<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Suppress warnings for CLI execution
if (php_sapi_name() !== 'cli') {
    // Enhanced CORS headers for Live Server compatibility
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    
    // Handle preflight OPTIONS request
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Log CORS requests for debugging
    error_log("CORS request from: " . ($_SERVER['HTTP_ORIGIN'] ?? 'unknown'));
}

function env(string $key, ?string $default = null): ?string {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // Load configuration
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
        $pdo = get_hosting_pdo();
        return $pdo;
    }

    // Fallback to environment variables
    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
    $db   = env('DB_NAME', 'hima_support');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    return $pdo;
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
        return;
    }
    
    http_response_code($status);
    header('Content-Type: application/json');
    
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
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
