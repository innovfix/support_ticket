<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    echo "Database connection successful!\n";
    
    // Check if issue_types table exists
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    if (in_array('issue_types', $tables)) {
        echo "Issue types table exists!\n";
        
        // Check issue types count
        $count = $pdo->query("SELECT COUNT(*) FROM issue_types")->fetchColumn();
        echo "Issue types count: $count\n";
        
        if ($count > 0) {
            $types = $pdo->query("SELECT * FROM issue_types")->fetchAll(PDO::FETCH_ASSOC);
            echo "Issue types:\n";
            foreach ($types as $type) {
                echo "- ID: {$type['id']}, Name: {$type['name']}, Active: {$type['is_active']}\n";
            }
        } else {
            echo "No issue types found. Running migration...\n";
            // Run migration
            require __DIR__ . '/migrate.php';
        }
    } else {
        echo "Issue types table does not exist. Running migration...\n";
        // Run migration
        require __DIR__ . '/migrate.php';
    }
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
