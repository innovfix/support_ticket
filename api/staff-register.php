<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$data = json_input();
$name = trim((string)($data['name'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');
if ($name === '' || $email === '' || $password === '') {
    bad_request('name, email, password are required');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo = get_pdo();

try {
    $stmt = $pdo->prepare('INSERT INTO staff_users (name, email, password_hash) VALUES (?,?,?)');
    $stmt->execute([$name, $email, $hash]);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    bad_request('Registration failed: ' . $e->getMessage(), 400);
}



