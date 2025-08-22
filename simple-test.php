<?php
/**
 * Simple Hosting Environment Test
 * This file works from any directory and shows basic server information
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Hosting Environment Test</h1>";
echo "<p>Testing basic server functionality...</p>";

echo "<h2>1. Basic Server Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current File:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";

echo "<h2>2. Directory Structure Test</h2>";
$currentDir = __DIR__;
echo "<p><strong>Current Directory:</strong> $currentDir</p>";

// Check if we can find config.php
$configPaths = [
    $currentDir . '/config.php',
    $currentDir . '/../config.php',
    $currentDir . '/../../config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/query-desk/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/hima-support/config.php'
];

echo "<h3>Config.php Search Results:</h3>";
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        echo "<p style='color: green;'>✓ Found: $path</p>";
    } else {
        echo "<p style='color: red;'>✗ Not found: $path</p>";
    }
}

echo "<h2>3. PHP Extensions Test</h2>";
echo "<p><strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>PDO MySQL Available:</strong> " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>MySQL Available:</strong> " . (extension_loaded('mysqli') ? 'Yes' : 'No') . "</p>";

echo "<h2>4. Database Connection Test</h2>";
try {
    // Try direct connection without config file
    $host = 'localhost';
    $dbname = 'u743445510_hima_support';
    $username = 'u743445510_hima_support';
    $password = 'HimaSupport@2025';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Database query successful: " . $result['count'] . " tickets found</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>5. File Permissions Test</h2>";
$testFile = __FILE__;
echo "<p><strong>Current file readable:</strong> " . (is_readable($testFile) ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Current file writable:</strong> " . (is_writable($testFile) ? 'Yes' : 'No') . "</p>";

echo "<h2>6. Environment Variables</h2>";
echo "<p><strong>DB_HOST:</strong> " . (getenv('DB_HOST') ?: 'Not set') . "</p>";
echo "<p><strong>DB_NAME:</strong> " . (getenv('DB_NAME') ?: 'Not set') . "</p>";
echo "<p><strong>DB_USER:</strong> " . (getenv('DB_USER') ?: 'Not set') . "</p>";

echo "<hr>";
echo "<p><em>This file helps debug hosting environment issues. Delete after testing.</em></p>";
?>
