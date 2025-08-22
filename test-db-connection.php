<?php
/**
 * Database Connection Test - Production Debugging
 * This file helps debug database connection issues on the live site
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Database Connection Test</h1>";
echo "<p>Testing database connection for production...</p>";

try {
    // Test 1: Check if config.php exists and can be loaded
    echo "<h2>Test 1: Configuration File</h2>";
    
    $configPaths = [
        __DIR__ . '/config.php',
        __DIR__ . '/../config.php',
        __DIR__ . '/../../config.php',
    ];
    
    $configFound = false;
    foreach ($configPaths as $path) {
        if (file_exists($path)) {
            echo "<p style='color: green;'>✓ Config found at: $path</p>";
            $configFound = true;
            break;
        } else {
            echo "<p style='color: red;'>✗ Config not found at: $path</p>";
        }
    }
    
    if (!$configFound) {
        throw new Exception("Configuration file not found in any expected location");
    }
    
    // Test 2: Load configuration
    echo "<h2>Test 2: Load Configuration</h2>";
    require_once $path;
    echo "<p style='color: green;'>✓ Configuration loaded successfully</p>";
    
    // Test 3: Check if database constants are defined
    echo "<h2>Test 3: Database Constants</h2>";
    $requiredConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
    foreach ($requiredConstants as $constant) {
        if (defined($constant)) {
            echo "<p style='color: green;'>✓ $constant: " . constant($constant) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ $constant: NOT DEFINED</p>";
        }
    }
    
    // Test 4: Check if get_hosting_pdo function exists
    echo "<h2>Test 4: Database Function</h2>";
    if (function_exists('get_hosting_pdo')) {
        echo "<p style='color: green;'>✓ get_hosting_pdo function exists</p>";
    } else {
        throw new Exception("get_hosting_pdo function not found");
    }
    
    // Test 5: Test database connection
    echo "<h2>Test 5: Database Connection</h2>";
    $pdo = get_hosting_pdo();
    
    if ($pdo instanceof PDO) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test 6: Test a simple query
        echo "<h2>Test 6: Database Query</h2>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✓ Query successful: " . $result['count'] . " tickets found</p>";
        
        // Test 7: Check table structure
        echo "<h2>Test 7: Table Structure</h2>";
        $stmt = $pdo->query("DESCRIBE tickets");
        $columns = $stmt->fetchAll();
        echo "<p style='color: green;'>✓ Tickets table has " . count($columns) . " columns:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
    } else {
        throw new Exception("Database connection failed - PDO object not returned");
    }
    
    echo "<h2 style='color: green;'>🎉 ALL TESTS PASSED! Database is working correctly.</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR: " . $e->getMessage() . "</h2>";
    echo "<p><strong>Error Details:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    
    // Show PHP info for debugging
    echo "<h2>PHP Information</h2>";
    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
    echo "<p><strong>PDO Available:</strong> " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>PDO MySQL Available:</strong> " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
    echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
}

echo "<hr>";
echo "<p><em>This file should be deleted after debugging is complete.</em></p>";
?>
