<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

// Accept multipart/form-data (for screenshot) or JSON
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

// Optional: handle screenshot upload
$screenshotPath = null;
if ($isMultipart && isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $tmpName = $_FILES['screenshot']['tmp_name'];
    $origName = basename($_FILES['screenshot']['name']);
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $safeName = 'scr_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? ('.' . $ext) : '');
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        bad_request('Failed to store uploaded screenshot', 500);
    }
    // Public path relative to project root for serving via Apache
    $screenshotPath = 'uploads/' . $safeName;
}

$pdo = get_pdo();

// Generate ticket code like TKT-0001
$pdo->exec('SET @next_code := (SELECT IFNULL(MAX(CAST(SUBSTRING(ticket_code, 5) AS UNSIGNED)) + 1, 1) FROM tickets)');
$stmtCode = $pdo->query('SELECT LPAD(@next_code, 4, "0") AS seq');
$seqRow = $stmtCode->fetch();
$ticketCode = 'TKT-' . ($seqRow['seq'] ?? '0001');

try {
    // Newer schema with assigned_to_name column
    $stmt = $pdo->prepare('INSERT INTO tickets (
        ticket_code,
        mobile_or_user_id,
        issue_type,
        issue_description,
        status,
        assigned_to,
        assigned_to_name,
        screenshot_path,
        created_by
    ) VALUES (?,?,?,?,"new",?,?,?,?)');
    $stmt->execute([
        $ticketCode,
        $mobileOrUserId,
        $issueType,
        $issueDescription,
        $assignedTo === '' ? null : $assignedTo,
        $assignedToName === '' ? null : $assignedToName,
        $screenshotPath,
        $createdBy === '' ? 'Staff' : $createdBy,
    ]);
} catch (Throwable $e) {
    // Fallback for older schema without assigned_to_name
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
        $screenshotPath,
        $createdBy === '' ? 'Staff' : $createdBy,
    ]);
}

$id = (int)$pdo->lastInsertId();

json_response([
    'success' => true,
    'ticketCode' => $ticketCode,
    'message' => 'Ticket created successfully',
    'ticket' => [
        'id' => $id,
        'ticketCode' => $ticketCode,
        'mobileOrUserId' => $mobileOrUserId,
        'issueType' => $issueType,
        'issueDescription' => $issueDescription,
        'status' => 'new',
        'assignedTo' => $assignedTo ?: null,
        'assignedToName' => $assignedToName ?: null,
        'screenshot' => $screenshotPath,
        'createdBy' => $createdBy,
        'createdAt' => date('c'),
    ],
]);


