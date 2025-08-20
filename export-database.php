<?php
/**
 * Query Desk - Database Export Script
 * Run this script to export your database for hosting
 */

require_once 'api/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    echo "🔄 Exporting Query Desk Database...\n\n";
    
    // Get all tables
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    echo "📊 Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    echo "\n";
    
    // Create SQL file
    $sqlFile = 'query_desk_database.sql';
    $handle = fopen($sqlFile, 'w');
    
    if (!$handle) {
        die("❌ Cannot create SQL file. Check permissions.\n");
    }
    
    // Write header
    fwrite($handle, "-- Query Desk Database Export\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- Database: query_desk\n\n");
    
    // Export each table
    foreach ($tables as $table) {
        echo "📤 Exporting table: $table\n";
        
        // Get table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createTable = $stmt->fetch(PDO::FETCH_NUM);
        fwrite($handle, "\n-- Table structure for table `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $createTable[1] . ";\n\n");
        
        // Get table data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            fwrite($handle, "-- Data for table `$table`\n");
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                // Escape values
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) return 'NULL';
                    return $pdo->quote($value);
                }, $values);
                
                fwrite($handle, "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n");
            }
            fwrite($handle, "\n");
            
            echo "   ✅ Exported " . count($rows) . " rows\n";
        } else {
            echo "   ℹ️  Table is empty\n";
        }
    }
    
    fclose($handle);
    
    echo "\n🎉 Database export completed successfully!\n";
    echo "📁 File saved as: $sqlFile\n";
    echo "📊 Total tables exported: " . count($tables) . "\n";
    echo "\n💡 Next steps:\n";
    echo "   1. Upload this SQL file to your hosting\n";
    echo "   2. Import it via phpMyAdmin\n";
    echo "   3. Update config.php with your hosting details\n";
    
} catch (Exception $e) {
    echo "❌ Export failed: " . $e->getMessage() . "\n";
}
?>
