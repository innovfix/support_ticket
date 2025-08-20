<?php
require_once '_bootstrap.php';

// Handle CLI execution
if (php_sapi_name() === 'cli') {
    echo "Tickets Update API\n";
    echo "This API requires POST data with ticket information\n";
    echo "Usage: curl -X POST -F 'ticketCode=TKT-0001' -F 'mobileOrUserId=1234567890' -F 'issueType=Test Issue' -F 'issueDescription=Test Description' http://localhost/query-desk/api/tickets-update.php\n";
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
    
    // Get form data
    $ticketCode = $_POST['ticketCode'] ?? '';
    $mobileOrUserId = $_POST['mobileOrUserId'] ?? '';
    $issueType = $_POST['issueType'] ?? '';
    $issueDescription = $_POST['issueDescription'] ?? '';
    
    // Debug logging
    error_log("Update request - ticketCode: $ticketCode, mobileOrUserId: $mobileOrUserId, issueType: $issueType");
    
    // Validate required fields
    if (empty($ticketCode) || empty($mobileOrUserId) || empty($issueType) || empty($issueDescription)) {
        json_response(['success' => false, 'message' => 'All fields are required'], 400);
        exit;
    }
    
    // Update ticket in database
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET mobile_or_user_id = ?, issue_type = ?, issue_description = ?
        WHERE ticket_code = ?
    ");
    
    $result = $stmt->execute([
        $mobileOrUserId,
        $issueType,
        $issueDescription,
        $ticketCode
    ]);
    
    if ($result) {
        if ($stmt->rowCount() > 0) {
            json_response([
                'success' => true, 
                'message' => 'Ticket updated successfully',
                'ticketCode' => $ticketCode
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Ticket not found or no changes made'], 404);
        }
    } else {
        json_response(['success' => false, 'message' => 'Database update failed'], 500);
    }
    
} catch (PDOException $e) {
    error_log("Database error in tickets-update.php: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in tickets-update.php: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'An error occurred'], 500);
}
?>
