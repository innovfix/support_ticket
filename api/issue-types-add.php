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
    
    // Debug logging
    error_log("Issue Types Add API called with input: " . json_encode($input));
    
    if (!$input || !isset($input['name']) || trim($input['name']) === '') {
        error_log("Validation failed: missing or empty name");
        bad_request('Issue type name is required');
    }
    
    $name = trim($input['name']);
    error_log("Processing issue type name: " . $name);
    
    $pdo = get_pdo();
    error_log("Database connection established");
    
    // Check if issue type already exists
    $stmt = $pdo->prepare('SELECT id FROM issue_types WHERE name = ?');
    $stmt->execute([$name]);
    
    if ($stmt->fetch()) {
        error_log("Issue type already exists: " . $name);
        bad_request('Issue type with this name already exists');
    }
    
    error_log("Inserting new issue type: " . $name);
    
    // Insert new issue type
    $stmt = $pdo->prepare('INSERT INTO issue_types (name, is_active) VALUES (?, 1)');
    $result = $stmt->execute([$name]);
    
    if (!$result) {
        error_log("Insert failed: " . json_encode($stmt->errorInfo()));
        bad_request('Database insert failed');
    }
    
    $id = (int)$pdo->lastInsertId();
    error_log("New issue type inserted with ID: " . $id);
    
    // Return updated list
    $stmt = $pdo->query('SELECT id, name FROM issue_types WHERE is_active = 1 ORDER BY name ASC');
    $types = $stmt->fetchAll();
    
    error_log("Returning updated types list: " . json_encode($types));
    
    json_response([
        'ok' => true,
        'message' => 'Issue type added successfully',
        'types' => $types
    ]);
    
} catch (Throwable $e) {
    error_log("Exception in issue-types-add.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    bad_request('Failed to add issue type: ' . $e->getMessage(), 500);
}


