<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    
    // Check if specific staff ID is requested
    if (isset($_GET['id'])) {
        $staffId = (int)$_GET['id'];
        $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM staff_users WHERE id = ?');
        $stmt->execute([$staffId]);
        $staff = $stmt->fetch();
        
        if ($staff) {
            json_response([$staff]);
        } else {
            json_response([]);
        }
        return;
    }
    
    // Get all staff members
    $stmt = $pdo->query('SELECT id, name, email, created_at FROM staff_users ORDER BY name ASC');
    $staff = $stmt->fetchAll();
    
    json_response($staff);
    
} catch (Throwable $e) {
    bad_request('Failed to load staff: ' . $e->getMessage(), 500);
}



