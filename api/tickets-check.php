<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    // Count total tickets
    $count = $pdo->query('SELECT COUNT(*) FROM tickets')->fetchColumn();
    
    // Get recent tickets
    $stmt = $pdo->query('SELECT id, ticket_code, mobile_or_user_id, issue_type, status, created_at FROM tickets ORDER BY created_at DESC LIMIT 20');
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
