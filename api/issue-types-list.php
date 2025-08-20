<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$pdo = get_pdo();
$stmt = $pdo->query('SELECT id, name FROM issue_types WHERE is_active = 1 ORDER BY name ASC');
$rows = $stmt->fetchAll();
json_response(['ok' => true, 'types' => $rows]);


