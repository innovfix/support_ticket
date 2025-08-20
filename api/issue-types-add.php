<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// Handle CLI execution
if (php_sapi_name() === 'cli') {
    echo "Issue Types Add API\n";
    echo "This API requires POST data with 'name' field\n";
    echo "Usage: curl -X POST -H 'Content-Type: application/json' -d '{\"name\":\"Test Issue Type\"}' http://localhost/query-desk/api/issue-types-add.php\n";
    exit(0);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['name']) || trim($input['name']) === '') {
        bad_request('Issue type name is required');
    }
    
    $name = trim($input['name']);
    
    $pdo = get_pdo();
    
    // Check if issue type already exists
    $stmt = $pdo->prepare('SELECT id FROM issue_types WHERE name = ?');
    $stmt->execute([$name]);
    
    if ($stmt->fetch()) {
        bad_request('Issue type with this name already exists');
    }
    
    // Insert new issue type
    $stmt = $pdo->prepare('INSERT INTO issue_types (name, is_active) VALUES (?, 1)');
    $stmt->execute([$name]);
    
    $id = (int)$pdo->lastInsertId();
    
    // Return updated list
    $stmt = $pdo->query('SELECT id, name FROM issue_types WHERE is_active = 1 ORDER BY name ASC');
    $types = $stmt->fetchAll();
    
    json_response([
        'ok' => true,
        'message' => 'Issue type added successfully',
        'types' => $types
    ]);
    
} catch (Throwable $e) {
    bad_request('Failed to add issue type: ' . $e->getMessage(), 500);
}


