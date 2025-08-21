<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

try {
    $pdo = get_pdo();

    $pdo->exec('CREATE TABLE IF NOT EXISTS tickets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_code VARCHAR(20) NOT NULL UNIQUE,
        mobile_or_user_id VARCHAR(100) NOT NULL,
        issue_type VARCHAR(100) NOT NULL,
        issue_description TEXT NOT NULL,
        status ENUM("new","in-progress","resolved","closed") NOT NULL DEFAULT "new",
        assigned_to VARCHAR(100) DEFAULT NULL,
        screenshot_path VARCHAR(255) DEFAULT NULL,
        created_by VARCHAR(100) NOT NULL DEFAULT "Staff",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // Ensure optional columns exist (assigned_by)
    try {
        $pdo->exec('ALTER TABLE tickets ADD COLUMN assigned_by VARCHAR(150) NULL');
    } catch (Throwable $e) {
        // ignore if already exists
    }
    try {
        $pdo->exec('ALTER TABLE tickets ADD COLUMN assigned_to_name VARCHAR(150) NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE tickets ADD COLUMN assigned_by_name VARCHAR(150) NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE tickets ADD COLUMN status_description TEXT NULL');
    } catch (Throwable $e) {}

    // Issue types master
    $pdo->exec('CREATE TABLE IF NOT EXISTS issue_types (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // Seed some defaults if empty
    $count = (int)$pdo->query('SELECT COUNT(*) FROM issue_types')->fetchColumn();
    if ($count === 0) {
        $seed = [
            'Withdrawal paid status not amount came',
            'Coins not added',
            'Call amount not added',
            'App issue / crash',
            'Bank details issue',
            'KYC',
        ];
        $stmt = $pdo->prepare('INSERT IGNORE INTO issue_types (name) VALUES (?)');
        foreach ($seed as $n) { $stmt->execute([$n]); }
    }

    // Staff users
    $pdo->exec('CREATE TABLE IF NOT EXISTS staff_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    json_response(['ok' => true, 'message' => 'Migration completed']);
} catch (Throwable $e) {
    bad_request('Migration failed: ' . $e->getMessage(), 500);
}


