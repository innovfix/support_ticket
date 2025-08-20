<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// Handle CLI execution
if (php_sapi_name() === 'cli') {
    echo "Issue Types Delete API\n";
    echo "This API requires POST data with 'id' field\n";
    echo "Usage: curl -X POST -H 'Content-Type: application/json' -d '{\"id\":1}' http://localhost/query-desk/api/issue-types-delete.php\n";
    exit(0);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        bad_request('Issue type ID is required');
    }
    
    $id = (int)$input['id'];
    
    if ($id <= 0) {
        bad_request('Valid issue type ID is required');
    }
    
    $pdo = get_pdo();
    
    // Check if issue type exists
    $stmt = $pdo->prepare('SELECT id, name FROM issue_types WHERE id = ?');
    $stmt->execute([$id]);
    
    $issueType = $stmt->fetch();
    if (!$issueType) {
        bad_request('Issue type not found');
    }
    
    // Check if issue type is being used in tickets
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE issue_type = ?');
    $stmt->execute([$issueType['name']]);
    $ticketCount = $stmt->fetchColumn();
    
    if ($ticketCount > 0) {
        bad_request('Cannot delete issue type that is being used by ' . $ticketCount . ' ticket(s). Please reassign tickets first.');
    }
    
    // Soft delete by setting is_active to 0
    $stmt = $pdo->prepare('UPDATE issue_types SET is_active = 0 WHERE id = ?');
    $stmt->execute([$id]);
    
    // Return updated list
    $stmt = $pdo->query('SELECT id, name FROM issue_types WHERE is_active = 1 ORDER BY name ASC');
    $types = $stmt->fetchAll();
    
    json_response([
        'ok' => true,
        'message' => 'Issue type deleted successfully',
        'types' => $types
    ]);
    
} catch (Throwable $e) {
    bad_request('Failed to delete issue type: ' . $e->getMessage(), 500);
}



