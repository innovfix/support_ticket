<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// Handle CLI execution


try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id']) || !isset($input['name'])) {
        bad_request('Issue type ID and name are required');
    }
    
    $id = (int)$input['id'];
    $name = trim($input['name']);
    
    if ($name === '') {
        bad_request('Issue type name cannot be empty');
    }
    
    $pdo = get_pdo();
    
    // Check if issue type exists
    $stmt = $pdo->prepare('SELECT id FROM issue_types WHERE id = ?');
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        bad_request('Issue type not found');
    }
    
    // Check if name already exists for different ID
    $stmt = $pdo->prepare('SELECT id FROM issue_types WHERE name = ? AND id != ?');
    $stmt->execute([$name, $id]);
    
    if ($stmt->fetch()) {
        bad_request('Issue type with this name already exists');
    }
    
    // Update issue type
    $stmt = $pdo->prepare('UPDATE issue_types SET name = ? WHERE id = ?');
    $stmt->execute([$name, $id]);
    
    // Return updated list
    $stmt = $pdo->query('SELECT id, name FROM issue_types WHERE is_active = 1 ORDER BY name ASC');
    $types = $stmt->fetchAll();
    
    json_response([
        'ok' => true,
        'message' => 'Issue type updated successfully',
        'types' => $types
    ]);
    
} catch (Throwable $e) {
    bad_request('Failed to update issue type: ' . $e->getMessage(), 500);
}



