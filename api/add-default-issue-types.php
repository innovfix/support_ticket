<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    echo "Database connection successful!\n";
    
    // Check if issue_types table exists
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    if (!in_array('issue_types', $tables)) {
        echo "Creating issue_types table...\n";
        $pdo->exec('CREATE TABLE IF NOT EXISTS issue_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        echo "Issue types table created!\n";
    }
    
    // Check current count
    $count = $pdo->query("SELECT COUNT(*) FROM issue_types")->fetchColumn();
    echo "Current issue types count: $count\n";
    
    if ($count === 0) {
        echo "Adding default issue types...\n";
        
        $defaultTypes = [
            'Withdrawal paid status not amount came',
            'Coins not added',
            'Call amount not added',
            'App issue / crash',
            'Bank details issue',
            'KYC'
        ];
        
        $stmt = $pdo->prepare('INSERT IGNORE INTO issue_types (name) VALUES (?)');
        foreach ($defaultTypes as $typeName) {
            $stmt->execute([$typeName]);
            echo "Added: $typeName\n";
        }
        
        echo "Default issue types added successfully!\n";
    } else {
        echo "Issue types already exist:\n";
        $types = $pdo->query("SELECT * FROM issue_types")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($types as $type) {
            echo "- ID: {$type['id']}, Name: {$type['name']}, Active: {$type['is_active']}\n";
        }
    }
    
    echo "\nTest the API endpoint: http://localhost/ticketing-manager/api/issue-types-list.php\n";
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
