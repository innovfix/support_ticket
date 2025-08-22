<?php
require_once '_bootstrap.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    // Debug logging
    error_log("=== TICKET UPDATE REQUEST ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Get PDO connection
    $pdo = get_pdo();
    
    // Get form data
    $ticketId = $_POST['ticketId'] ?? null;
    $issueType = $_POST['issueType'] ?? null;
    $description = $_POST['description'] ?? null;
    $phoneNumber = $_POST['phoneNumber'] ?? null;
    
    error_log("Parsed data - ticketId: $ticketId, issueType: $issueType, description: $description, phoneNumber: $phoneNumber");
    
    // Validate required fields
    if (!$ticketId || !$issueType || !$description) {
        json_response(['error' => 'Missing required fields: ticketId, issueType, description'], 400);
    }
    
    // Check if ticket exists
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $existingTicket = $stmt->fetch();
    
    if (!$existingTicket) {
        json_response(['error' => 'Ticket not found'], 404);
    }
    
    // Handle screenshot upload
    $screenshotPath = $existingTicket['screenshot_path']; // Keep existing screenshot by default
    
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['screenshot'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        $fileType = $file['type'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($fileType, $allowedTypes)) {
            json_response(['error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'], 400);
        }
        
        // Validate file size (max 5MB)
        if ($fileSize > 5 * 1024 * 1024) {
            json_response(['error' => 'File size too large. Maximum size is 5MB.'], 400);
        }
        
        // Generate unique filename
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueFileName = 'screenshot_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $uniqueFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            $screenshotPath = 'uploads/' . $uniqueFileName;
            
            // Delete old screenshot if it exists and is different
            if ($existingTicket['screenshot_path'] && 
                $existingTicket['screenshot_path'] !== $screenshotPath && 
                file_exists('../' . $existingTicket['screenshot_path'])) {
                unlink('../' . $existingTicket['screenshot_path']);
            }
        } else {
            json_response(['error' => 'Failed to upload screenshot'], 500);
        }
    }
    
    // Update ticket in database
    $updateQuery = "
        UPDATE tickets 
        SET issue_type = ?, 
            issue_description = ?, 
            mobile_or_user_id = ?,
            screenshot_path = ?,
            updated_at = NOW()
        WHERE id = ?
    ";
    
    error_log("Update query: $updateQuery");
    error_log("Update parameters: " . print_r([$issueType, $description, $phoneNumber, $screenshotPath, $ticketId], true));
    
    $stmt = $pdo->prepare($updateQuery);
    
    $result = $stmt->execute([
        $issueType,
        $description,
        $phoneNumber,
        $screenshotPath,
        $ticketId
    ]);
    
    error_log("Database update result: " . ($result ? 'success' : 'failed'));
    if (!$result) {
        error_log("Database error info: " . print_r($stmt->errorInfo(), true));
    }
    
    if ($result) {
        // Get updated ticket data
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $updatedTicket = $stmt->fetch();
        
        json_response([
            'success' => true,
            'message' => 'Ticket updated successfully',
            'ticket' => $updatedTicket
        ]);
    } else {
        json_response(['error' => 'Failed to update ticket'], 500);
    }
    
} catch (PDOException $e) {
    error_log("Database error in tickets-update.php: " . $e->getMessage());
    json_response(['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in tickets-update.php: " . $e->getMessage());
    json_response(['error' => 'An error occurred while updating the ticket'], 500);
}
?>
