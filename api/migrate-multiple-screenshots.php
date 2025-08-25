<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    echo "Starting migration for multiple screenshots support...\n";
    
    // Create new table for ticket screenshots
    $pdo->exec('CREATE TABLE IF NOT EXISTS ticket_screenshots (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT UNSIGNED NOT NULL,
        screenshot_path VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_size INT UNSIGNED NOT NULL,
        uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        INDEX idx_ticket_id (ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    
    echo "Created ticket_screenshots table\n";
    
    // Add new column to tickets table for backward compatibility
    try {
        $pdo->exec('ALTER TABLE tickets ADD COLUMN screenshots_count INT UNSIGNED DEFAULT 0');
        echo "Added screenshots_count column to tickets table\n";
    } catch (Throwable $e) {
        echo "screenshots_count column already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Migrate existing screenshots to new table
    $stmt = $pdo->query('SELECT id, screenshot_path FROM tickets WHERE screenshot_path IS NOT NULL AND screenshot_path != ""');
    $existingScreenshots = $stmt->fetchAll();
    
    if (count($existingScreenshots) > 0) {
        echo "Migrating " . count($existingScreenshots) . " existing screenshots...\n";
        
        $insertStmt = $pdo->prepare('INSERT INTO ticket_screenshots (ticket_id, screenshot_path, original_filename, file_size) VALUES (?, ?, ?, ?)');
        
        foreach ($existingScreenshots as $screenshot) {
            $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $screenshot['screenshot_path'];
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
            $originalFilename = basename($screenshot['screenshot_path']);
            
            $insertStmt->execute([
                $screenshot['id'],
                $screenshot['screenshot_path'],
                $originalFilename,
                $fileSize
            ]);
        }
        
        echo "Migration completed successfully!\n";
    } else {
        echo "No existing screenshots to migrate\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
