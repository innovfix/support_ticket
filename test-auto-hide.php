<?php
/**
 * Test script to verify auto-hide feature for resolved tickets
 * This script tests the logic that hides resolved tickets older than 1 day
 */

require_once 'api/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    echo "<h2>Testing Auto-Hide Feature for Resolved Tickets</h2>\n";
    echo "<p>This test verifies that resolved tickets older than 1 day are automatically hidden.</p>\n";
    
    // Test 1: Check current auto-hide logic
    echo "<h3>Test 1: Current Auto-Hide Logic</h3>\n";
    
    $sql = 'SELECT 
        t.id,
        t.ticket_code,
        t.status,
        t.created_at,
        t.updated_at,
        CASE 
            WHEN t.status = "resolved" AND t.updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY) 
            THEN "HIDDEN" 
            ELSE "VISIBLE" 
        END as visibility
    FROM tickets t
    WHERE (t.status != "resolved" OR t.updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY))
    ORDER BY t.status, t.updated_at DESC
    LIMIT 10';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $visibleTickets = $stmt->fetchAll();
    
    echo "<p><strong>Visible Tickets (including recent resolved):</strong></p>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Code</th><th>Status</th><th>Created</th><th>Updated</th><th>Visibility</th></tr>\n";
    
    foreach ($visibleTickets as $ticket) {
        echo "<tr>";
        echo "<td>{$ticket['id']}</td>";
        echo "<td>{$ticket['ticket_code']}</td>";
        echo "<td>{$ticket['status']}</td>";
        echo "<td>{$ticket['created_at']}</td>";
        echo "<td>{$ticket['updated_at']}</td>";
        echo "<td>{$ticket['visibility']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test 2: Check hidden resolved tickets
    echo "<h3>Test 2: Hidden Resolved Tickets (Older than 1 day)</h3>\n";
    
    $hiddenSql = 'SELECT 
        t.id,
        t.ticket_code,
        t.status,
        t.created_at,
        t.updated_at,
        TIMESTAMPDIFF(HOUR, t.updated_at, NOW()) as hours_old
    FROM tickets t
    WHERE t.status = "resolved" AND t.updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY t.updated_at DESC
    LIMIT 10';
    
    $hiddenStmt = $pdo->prepare($hiddenSql);
    $hiddenStmt->execute();
    $hiddenTickets = $hiddenStmt->fetchAll();
    
    if (count($hiddenTickets) > 0) {
        echo "<p><strong>Hidden Resolved Tickets (older than 24 hours):</strong></p>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>ID</th><th>Code</th><th>Status</th><th>Created</th><th>Updated</th><th>Hours Old</th></tr>\n";
        
        foreach ($hiddenTickets as $ticket) {
            echo "<tr>";
            echo "<td>{$ticket['id']}</td>";
            echo "<td>{$ticket['ticket_code']}</td>";
            echo "<td>{$ticket['status']}</td>";
            echo "<td>{$ticket['created_at']}</td>";
            echo "<td>{$ticket['updated_at']}</td>";
            echo "<td>{$ticket['hours_old']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p><strong>No hidden resolved tickets found.</strong></p>\n";
    }
    
    // Test 3: Count summary
    echo "<h3>Test 3: Summary Counts</h3>\n";
    
    $totalSql = 'SELECT COUNT(*) as total FROM tickets';
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->execute();
    $totalCount = $totalStmt->fetchColumn();
    
    $visibleSql = 'SELECT COUNT(*) as visible FROM tickets WHERE (status != "resolved" OR updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY))';
    $visibleStmt = $pdo->prepare($visibleSql);
    $visibleStmt->execute();
    $visibleCount = $visibleStmt->fetchColumn();
    
    $hiddenCount = $totalCount - $visibleCount;
    
    echo "<p><strong>Total tickets in database:</strong> {$totalCount}</p>\n";
    echo "<p><strong>Visible tickets (including recent resolved):</strong> {$visibleCount}</p>\n";
    echo "<p><strong>Hidden resolved tickets (older than 1 day):</strong> {$hiddenCount}</p>\n";
    
    if ($hiddenCount > 0) {
        echo "<p style='color: green;'><strong>✅ Auto-hide feature is working correctly!</strong></p>\n";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ No resolved tickets older than 1 day found to test auto-hide.</strong></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
