<?php
require_once '_bootstrap.php';

// Handle CLI execution
if (php_sapi_name() === 'cli') {
    echo "Tickets Delete API\n";
    echo "This API requires POST data with 'ticketCode' field\n";
    echo "Usage: curl -X POST -F 'ticketCode=TKT-0001' http://localhost/query-desk/api/tickets-delete.php\n";
    exit(0);
}

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit(0);
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Only POST method allowed'], 405);
    exit;
}

try {
    // Get database connection
    $pdo = get_pdo();
    
    // Get ticket code
    $ticketCode = $_POST['ticketCode'] ?? '';
    
    // Validate ticket code
    if (empty($ticketCode)) {
        json_response(['success' => false, 'message' => 'Ticket code is required'], 400);
        exit;
    }
    
    // Check if ticket exists
    $stmt = $pdo->prepare("SELECT ticket_code FROM tickets WHERE ticket_code = ?");
    $stmt->execute([$ticketCode]);
    
    if (!$stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Ticket not found'], 404);
        exit;
    }
    
    // Delete ticket from database
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE ticket_code = ?");
    $result = $stmt->execute([$ticketCode]);
    
    if ($result && $stmt->rowCount() > 0) {
        json_response([
            'success' => true, 
            'message' => 'Ticket deleted successfully',
            'ticketCode' => $ticketCode
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to delete ticket'], 500);
    }
    
} catch (PDOException $e) {
    error_log("Database error in tickets-delete.php: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in tickets-delete.php: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'An error occurred'], 500);
}
?>
