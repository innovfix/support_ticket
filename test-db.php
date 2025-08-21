<?php
// Simple database connection test
require_once 'api/_bootstrap.php';

try {
    echo "Testing database connection...\n";
    
    $pdo = get_pdo();
    echo "✅ Database connection successful!\n";
    
    // Test if we can query the issue_types table
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM issue_types');
    $result = $stmt->fetch();
    echo "✅ Issue types table accessible! Count: " . $result['count'] . "\n";
    
    // Test if we can insert (but don't actually insert)
    $stmt = $pdo->prepare('INSERT INTO issue_types (name, is_active) VALUES (?, 1)');
    echo "✅ Insert statement prepared successfully!\n";
    
    echo "\n🎯 Database is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
