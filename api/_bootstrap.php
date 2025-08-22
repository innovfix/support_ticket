<?php
declare(strict_types=1);

// Enable error reporting for debugging in production
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable for debugging production issues
ini_set('log_errors', 1);

// Suppress warnings for CLI execution
if (php_sapi_name() !== 'cli') {
    // Enhanced CORS headers for production
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

    try {
        // Try multiple paths for config.php
        $configPaths = [
            __DIR__ . '/../config.php',           // API folder parent
            __DIR__ . '/../../config.php',        // Two levels up
            __DIR__ . '/../../../config.php',     // Three levels up
            dirname(__DIR__) . '/config.php',     // Alternative approach
        ];
        
        $configLoaded = false;
        foreach ($configPaths as $configPath) {
            if (file_exists($configPath)) {
                require_once $configPath;
                $configLoaded = true;
                error_log("Config loaded from: " . $configPath);
                break;
            }
        }
        
        if (!$configLoaded) {
            error_log("ERROR: config.php not found in any of these paths: " . implode(', ', $configPaths));
            throw new Exception("Configuration file not found");
        }
        
        // Check if get_hosting_pdo function exists
        if (!function_exists('get_hosting_pdo')) {
            error_log("ERROR: get_hosting_pdo function not found in config.php");
            throw new Exception("Database connection function not found");
        }
        
        $pdo = get_hosting_pdo();
        if (!$pdo instanceof PDO) {
            error_log("ERROR: get_hosting_pdo did not return a valid PDO object");
            throw new Exception("Database connection failed");
        }
        
        error_log("Database connection successful");
        return $pdo;
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Fallback to environment variables if config fails
        try {
            $host = env('DB_HOST', 'localhost');
            $port = env('DB_PORT', '3306');
            $db   = env('DB_NAME', 'u743445510_hima_support');
            $user = env('DB_USER', 'u743445510_hima_support');
            $pass = env('DB_PASS', 'HimaSupport@2025');

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            error_log("Fallback database connection successful using environment variables");
            return $pdo;
            
        } catch (PDOException $pdoError) {
            error_log("Fallback database connection also failed: " . $pdoError->getMessage());
            throw new Exception("All database connection attempts failed: " . $e->getMessage() . " | PDO: " . $pdoError->getMessage());
        }
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
        error_log("JSON encoding failed for response data");
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error - JSON encoding failed']);
        exit;
    }
    
    echo $json;
    exit;
}

function bad_request(string $message, int $status = 400): void {
    error_log("Bad request: " . $message . " (Status: " . $status . ")");
    json_response(['error' => $message], $status);
}
