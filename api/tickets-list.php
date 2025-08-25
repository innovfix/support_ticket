<?php
declare(strict_types=1);

// Ensure we always return JSON, even on errors
header('Content-Type: application/json');

try {
    require __DIR__ . '/_bootstrap.php';
    
    $pdo = get_pdo();
    
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null; // all by default
    
    $sql = 'SELECT 
        t.id,
        t.ticket_code AS ticketCode,
        t.mobile_or_user_id AS mobileOrUserId,
        t.issue_type AS issueType,
        t.issue_description AS issueDescription,
        t.status,
        t.assigned_to AS assignedTo,
        t.assigned_by AS assignedBy,
        t.assigned_to_name AS assignedToName,
        t.assigned_by_name AS assignedByName,
        t.status_description AS statusDescription,
        COALESCE(t.assigned_to_name, s.name) AS assignedStaffName,
        t.screenshot_path AS screenshot,
        t.screenshots_count AS screenshotsCount,
        t.created_by AS createdBy,
        t.created_at AS createdAt,
        t.updated_at AS updatedAt
    FROM tickets t
    LEFT JOIN staff_users s ON s.email = t.assigned_to';
    
    $params = [];
    if ($status && in_array($status, ['new','in-progress','resolved','closed'], true)) {
        $sql .= ' WHERE status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY id DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    
    // Fetch screenshots for each ticket
    $tickets = [];
    foreach ($rows as $row) {
        $ticket = $row;
        
        // Get all screenshots for this ticket
        try {
            $screenshotStmt = $pdo->prepare('SELECT 
                screenshot_path, original_filename, file_size, uploaded_at 
                FROM ticket_screenshots 
                WHERE ticket_id = ? 
                ORDER BY uploaded_at ASC');
            $screenshotStmt->execute([$row['id']]);
            $screenshots = $screenshotStmt->fetchAll();
            
            if (count($screenshots) > 0) {
                $ticket['screenshots'] = $screenshots;
                $ticket['screenshotsCount'] = count($screenshots);
            } else {
                // Fallback to old single screenshot if no multiple screenshots found
                $ticket['screenshots'] = $row['screenshot'] ? [['screenshot_path' => $row['screenshot']]] : [];
                $ticket['screenshotsCount'] = $row['screenshot'] ? 1 : 0;
            }
        } catch (Throwable $e) {
            // If ticket_screenshots table doesn't exist, fallback to old single screenshot
            $ticket['screenshots'] = $row['screenshot'] ? [['screenshot_path' => $row['screenshot']]] : [];
            $ticket['screenshotsCount'] = $row['screenshot'] ? 1 : 0;
        }
        
        $tickets[] = $ticket;
    }
    
    json_response([
        'ok' => true,
        'tickets' => $tickets,
        'count' => count($tickets)
    ]);
    
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => 'Failed to fetch tickets: ' . $e->getMessage()
    ], 500);
}
?>


