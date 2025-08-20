<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$data = json_input();

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
            updated_at = NOW() 
        WHERE ticket_code = ?');
    $stmt->execute([$newStatus, $assignedTo, $displayTo, $changedBy, $displayBy, $ticketCode]);
} else {
    $stmt = $pdo->prepare('UPDATE tickets SET status = ?, updated_at = NOW() WHERE ticket_code = ?');
    $stmt->execute([$newStatus, $ticketCode]);
}

if ($stmt->rowCount() === 0) {
    bad_request('Ticket not found for code: ' . $ticketCode, 404);
}

// Optionally: a status history table would be better, but not added yet

json_response(['ok' => true]);


