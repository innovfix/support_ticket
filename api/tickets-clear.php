<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();
    // Remove all tickets
    $pdo->exec('TRUNCATE TABLE tickets');
    json_response(['ok' => true]);
} catch (Throwable $e) {
    bad_request('Failed to clear tickets: ' . $e->getMessage(), 500);
}



