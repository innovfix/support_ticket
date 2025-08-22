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
$newStatus = trim((string)($data['status'] ?? ''));
$description = trim((string)($data['description'] ?? ''));
$changedBy = trim((string)($data['changedBy'] ?? 'Manager'));
$assignedTo = isset($data['assignedTo']) ? trim((string)$data['assignedTo']) : null; // staff email
$assignedToName = isset($data['assignedToName']) ? trim((string)$data['assignedToName']) : null; // staff display name

if ($ticketCode === '' || !in_array($newStatus, ['new','in-progress','resolved','closed'], true)) {
    bad_request('ticketCode and valid status are required');
}

$pdo = get_pdo();

error_log("Ticket code: $ticketCode, New status: $newStatus, Description: $description, Changed by: $changedBy");

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
        WHERE ticket_code = ?');
    error_log("Executing assignment update with params: " . print_r([$newStatus, $assignedTo, $displayTo, $changedBy, $displayBy, $description, $ticketCode], true));
    $stmt->execute([$newStatus, $assignedTo, $displayTo, $changedBy, $displayBy, $description, $ticketCode]);
} else {
    $stmt = $pdo->prepare('UPDATE tickets SET status = ?, status_description = ?, updated_at = NOW() WHERE ticket_code = ?');
    error_log("Executing simple update with params: " . print_r([$newStatus, $description, $ticketCode], true));
    $stmt->execute([$newStatus, $description, $ticketCode]);
}

error_log("Update result - rows affected: " . $stmt->rowCount());

if ($stmt->rowCount() === 0) {
    error_log("No rows affected - ticket not found for code: $ticketCode");
    bad_request('Ticket not found for code: ' . $ticketCode, 404);
}

// Optionally: a status history table would be better, but not added yet
error_log("Status update successful, sending response");
json_response(['ok' => true]);


