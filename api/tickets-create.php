<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// Accept multipart/form-data (for screenshots) or JSON
$isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
$data = $isMultipart ? $_POST : json_input();

$mobileOrUserId = trim($data['mobileOrUserId'] ?? '');
$issueType = trim($data['issueType'] ?? '');
$issueDescription = trim($data['issueDescription'] ?? '');
$createdBy = trim($data['createdBy'] ?? 'Staff');
$assignedTo = trim($data['assignedTo'] ?? '');
$assignedToName = trim($data['assignedToName'] ?? '');

if ($mobileOrUserId === '' || $issueType === '' || $issueDescription === '') {
    bad_request('mobileOrUserId, issueType and issueDescription are required');
}

// Handle multiple screenshots upload
$screenshotPaths = [];
$screenshotsCount = 0;

if ($isMultipart && isset($_FILES['screenshots'])) {
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    
    // Handle multiple screenshots
    $files = $_FILES['screenshots'];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $fileTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        
        // Check for upload errors
        if ($fileError === UPLOAD_ERR_OK && $fileName) {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                continue; // Skip invalid file types
            }
            
            // Validate file size (5MB limit)
            if ($fileSize > 5 * 1024 * 1024) {
                continue; // Skip files that are too large
            }
            
            // Generate unique filename
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $safeName = 'scr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? ('.' . $ext) : '');
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
            
            if (move_uploaded_file($fileTmpName, $targetPath)) {
                $screenshotPaths[] = [
                    'path' => 'uploads/' . $safeName,
                    'original_filename' => $fileName,
                    'file_size' => $fileSize
                ];
                $screenshotsCount++;
            }
        }
    }
}

$pdo = get_pdo();

// Generate ticket code like TKT-0001
$pdo->exec('SET @next_code := (SELECT IFNULL(MAX(CAST(SUBSTRING(ticket_code, 5) AS UNSIGNED)) + 1, 1) FROM tickets)');
$stmtCode = $pdo->query('SELECT LPAD(@next_code, 4, "0") AS seq');
$seqRow = $stmtCode->fetch();
$ticketCode = 'TKT-' . ($seqRow['seq'] ?? '0001');

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert ticket
    $stmt = $pdo->prepare('INSERT INTO tickets (
        ticket_code,
        mobile_or_user_id,
        issue_type,
        issue_description,
        status,
        assigned_to,
        assigned_to_name,
        screenshot_path,
        screenshots_count,
        created_by
    ) VALUES (?,?,?,?,"new",?,?,?,?,?)');
    
    // For backward compatibility, keep the first screenshot in screenshot_path
    $firstScreenshotPath = $screenshotsCount > 0 ? $screenshotPaths[0]['path'] : null;
    
    $stmt->execute([
        $ticketCode,
        $mobileOrUserId,
        $issueType,
        $issueDescription,
        $assignedTo === '' ? null : $assignedTo,
        $assignedToName === '' ? null : $assignedToName,
        $firstScreenshotPath,
        $screenshotsCount,
        $createdBy === '' ? 'Staff' : $createdBy,
    ]);
    
    $ticketId = (int)$pdo->lastInsertId();
    
    // Insert screenshots into ticket_screenshots table
    if ($screenshotsCount > 0) {
        $screenshotStmt = $pdo->prepare('INSERT INTO ticket_screenshots (
            ticket_id, screenshot_path, original_filename, file_size
        ) VALUES (?, ?, ?, ?)');
        
        foreach ($screenshotPaths as $screenshot) {
            $screenshotStmt->execute([
                $ticketId,
                $screenshot['path'],
                $screenshot['original_filename'],
                $screenshot['file_size']
            ]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
} catch (Throwable $e) {
    // Rollback on error
    $pdo->rollBack();
    
    // Fallback for older schema without assigned_to_name and screenshots_count
    try {
        $stmt = $pdo->prepare('INSERT INTO tickets (
            ticket_code,
            mobile_or_user_id,
            issue_type,
            issue_description,
            status,
            assigned_to,
            screenshot_path,
            created_by
        ) VALUES (?,?,?,?,"new",?,?,?)');
        $stmt->execute([
            $ticketCode,
            $mobileOrUserId,
            $issueType,
            $issueDescription,
            $assignedTo === '' ? null : $assignedTo,
            $firstScreenshotPath,
            $createdBy === '' ? 'Staff' : $createdBy,
        ]);
        
        $ticketId = (int)$pdo->lastInsertId();
        
        // Try to insert screenshots if table exists
        if ($screenshotsCount > 0) {
            try {
                $screenshotStmt = $pdo->prepare('INSERT INTO ticket_screenshots (
                    ticket_id, screenshot_path, original_filename, file_size
                ) VALUES (?, ?, ?, ?)');
                
                foreach ($screenshotPaths as $screenshot) {
                    $screenshotStmt->execute([
                        $ticketId,
                        $screenshot['path'],
                        $screenshot['original_filename'],
                        $screenshot['file_size']
                    ]);
                }
            } catch (Throwable $screenshotError) {
                // Screenshots table might not exist, continue without them
                error_log('Failed to insert screenshots: ' . $screenshotError->getMessage());
            }
        }
    } catch (Throwable $fallbackError) {
        bad_request('Failed to create ticket: ' . $fallbackError->getMessage(), 500);
    }
}

json_response([
    'success' => true,
    'ticketCode' => $ticketCode,
    'message' => 'Ticket created successfully',
    'ticket' => [
        'id' => $ticketId,
        'ticketCode' => $ticketCode,
        'mobileOrUserId' => $mobileOrUserId,
        'issueType' => $issueType,
        'issueDescription' => $issueDescription,
        'status' => 'new',
        'assignedTo' => $assignedTo ?: null,
        'assignedToName' => $assignedToName ?: null,
        'screenshots' => $screenshotPaths,
        'screenshotsCount' => $screenshotsCount,
        'createdBy' => $createdBy,
        'createdAt' => date('c'),
    ],
]);


