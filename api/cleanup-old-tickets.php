<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// This endpoint can be called manually or via cron job to clean up old tickets
// It removes resolved and closed tickets that are older than 2 days

try {
    $pdo = get_pdo();
    
    // Get count of tickets that will be cleaned up
    $countStmt = $pdo->prepare('
        SELECT COUNT(*) FROM tickets 
        WHERE status IN ("resolved", "closed") 
        AND updated_at < DATE_SUB(NOW(), INTERVAL 2 DAY)
    ');
    $countStmt->execute();
    $ticketsToDelete = $countStmt->fetchColumn();
    
    if ($ticketsToDelete === 0) {
        json_response([
            'success' => true,
            'message' => 'No old tickets to clean up',
            'tickets_deleted' => 0
        ]);
        exit;
    }
    
    // Delete old resolved and closed tickets
    $deleteStmt = $pdo->prepare('
        DELETE FROM tickets 
        WHERE status IN ("resolved", "closed") 
        AND updated_at < DATE_SUB(NOW(), INTERVAL 2 DAY)
    ');
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    
    // Log the cleanup operation
    error_log("Ticket cleanup: Deleted $deletedCount old resolved/closed tickets older than 2 days");
    
    json_response([
        'success' => true,
        'message' => "Successfully cleaned up $deletedCount old tickets",
        'tickets_deleted' => $deletedCount,
        'tickets_found' => $ticketsToDelete
    ]);
    
} catch (Exception $e) {
    error_log("Ticket cleanup error: " . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Failed to clean up old tickets: ' . $e->getMessage()
    ], 500);
}
?>
