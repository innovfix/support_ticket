<?php
declare(strict_types=1);

// Ensure we always return JSON, even on errors
header('Content-Type: application/json');

try {
    require __DIR__ . '/_bootstrap.php';
    
    $pdo = get_pdo();
    
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null; // all by default
    $fromDate = isset($_GET['fromDate']) ? trim((string)$_GET['fromDate']) : null; // YYYY-MM-DD
    $toDate = isset($_GET['toDate']) ? trim((string)$_GET['toDate']) : null;   // YYYY-MM-DD
    $code = isset($_GET['code']) ? trim((string)$_GET['code']) : null;         // TKT-xxxx or id
    
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
        t.created_by AS createdBy,
        t.created_at AS createdAt,
        t.updated_at AS updatedAt
    FROM tickets t
    LEFT JOIN staff_users s ON s.email = t.assigned_to';
    
    $params = [];
    $where = [];
    if ($status && in_array($status, ['new','in-progress','resolved','closed'], true)) {
        $where[] = 't.status = ?';
        $params[] = $status;
    }
    // Date range filter on created_at
    if ($fromDate !== null && $fromDate !== '') {
        // validate basic format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $where[] = 'DATE(t.created_at) >= ?';
            $params[] = $fromDate;
        }
    }
    if ($toDate !== null && $toDate !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $where[] = 'DATE(t.created_at) <= ?';
            $params[] = $toDate;
        }
    }
    // Code filter: match by ticket_code or numeric id
    if ($code !== null && $code !== '') {
        if (preg_match('/^\d+$/', $code)) {
            $where[] = 't.id = ?';
            $params[] = (int)$code;
        } else {
            $where[] = 't.ticket_code = ?';
            $params[] = $code;
        }
    }
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY t.id DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    
    echo json_encode(['ok' => true, 'tickets' => $rows]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Tickets list API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Always return JSON error response
    http_response_code(500);
    echo json_encode([
        'ok' => false, 
        'error' => 'Failed to load tickets: ' . $e->getMessage(),
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>


