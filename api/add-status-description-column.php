<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    // Check if the column already exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM tickets LIKE 'status_description'");
    $stmt->execute();
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "Column 'status_description' already exists in tickets table.\n";
        exit(0);
    }
    
    // Add the status_description column
    $sql = "ALTER TABLE tickets ADD COLUMN status_description TEXT NULL AFTER status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo "Successfully added 'status_description' column to tickets table.\n";
    
    // Verify the column was added
    $stmt = $pdo->prepare("DESCRIBE tickets");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']} {$column['Default']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

