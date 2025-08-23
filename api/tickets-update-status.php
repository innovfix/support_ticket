<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// Debug logging
error_log("=== STATUS UPDATE REQUEST ===");
error_log("Request data: " . print_r($_POST, true));
error_log("JSON input: " . file_get_contents('php://input'));

$data = json_input();
error_log("Parsed data: " . print_r($data, true));

$ticketCode = trim((string)($data['ticketCode'] ?? ''));
$ticketId = isset($data['ticketId']) ? (int)($data['ticketId']) : null;
$newStatus = trim((string)($data['status'] ?? ''));
$description = trim((string)($data['description'] ?? ''));
$changedBy = trim((string)($data['changedBy'] ?? 'Manager'));
$assignedTo = isset($data['assignedTo']) ? trim((string)$data['assignedTo']) : null; // staff email
$assignedToName = isset($data['assignedToName']) ? trim((string)$data['assignedToName']) : null; // staff display name

// Check if we have either ticketCode or ticketId
if ((!$ticketCode || $ticketCode === '') && !$ticketId) {
    bad_request('Either ticketCode or ticketId is required');
}

if (!in_array($newStatus, ['new','in-progress','resolved','closed'], true)) {
    bad_request('Valid status is required');
}

$pdo = get_pdo();

error_log("Ticket code: $ticketCode, New status: $newStatus, Description: $description, Changed by: $changedBy");

// Build WHERE clause based on available identifier
$whereClause = '';
$whereParams = [];

if ($ticketId) {
    $whereClause = 'WHERE id = ?';
    $whereParams[] = $ticketId;
} elseif ($ticketCode) {
    $whereClause = 'WHERE ticket_code = ?';
    $whereParams[] = $ticketCode;
}

if ($assignedTo !== null && $assignedTo !== '') {
    // If no display name provided, use the identifier we got for assignee
    $displayTo = ($assignedToName !== null && $assignedToName !== '') ? $assignedToName : $assignedTo;
    $displayBy = $changedBy;
    $stmt = $pdo->prepare('UPDATE tickets 
        SET status = ?, 
            assigned_to = ?, 
            assigned_to_name = ?, 
            assigned_by = ?, 
            assigned_by_name = ?, 
            status_description = ?, 
            updated_at = NOW() 
        ' . $whereClause);
    $params = [$newStatus, $assignedTo, $displayTo, $changedBy, $displayBy, $description];
    $params = array_merge($params, $whereParams);
    error_log("Executing assignment update with params: " . print_r($params, true));
    $stmt->execute($params);
} else {
    $stmt = $pdo->prepare('UPDATE tickets SET status = ?, status_description = ?, updated_at = NOW() ' . $whereClause);
    $params = [$newStatus, $description];
    $params = array_merge($params, $whereParams);
    error_log("Executing simple update with params: " . print_r($params, true));
    $stmt->execute($params);
}

error_log("Update result - rows affected: " . $stmt->rowCount());

if ($stmt->rowCount() === 0) {
    if ($ticketId) {
        error_log("No rows affected - ticket not found for ID: $ticketId");
        bad_request('Ticket not found for ID: ' . $ticketId, 404);
    } else {
        error_log("No rows affected - ticket not found for code: $ticketCode");
        bad_request('Ticket not found for code: ' . $ticketCode, 404);
    }
}

// Optionally: a status history table would be better, but not added yet
error_log("Status update successful, sending response");
json_response(['success' => true, 'message' => 'Ticket status updated successfully']);


