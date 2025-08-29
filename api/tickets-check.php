<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    // Count total tickets (excluding auto-hidden resolved tickets)
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE (status != "resolved" OR updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY))');
    $countStmt->execute();
    $count = $countStmt->fetchColumn();
    
    // Get recent tickets (excluding auto-hidden resolved tickets)
    $stmt = $pdo->prepare('SELECT id, ticket_code, mobile_or_user_id, issue_type, status, created_at FROM tickets WHERE (status != "resolved" OR updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) ORDER BY created_at DESC LIMIT 20');
    $stmt->execute();
    $tickets = $stmt->fetchAll();
    
    json_response([
        'ok' => true,
        'total_tickets' => $count,
        'recent_tickets' => $tickets,
        'message' => $count > 0 ? "Found $count tickets in database" : "No tickets found in database"
    ]);
    
} catch (Throwable $e) {
    bad_request('Failed to check tickets: ' . $e->getMessage(), 500);
}
