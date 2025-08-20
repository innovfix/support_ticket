<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

$data = json_input();
$email = strtolower(trim((string)($data['email'] ?? '')));
$password = (string)($data['password'] ?? '');
if ($email === '' || $password === '') {
    bad_request('email and password are required');
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT id, name, email, password_hash, is_active FROM staff_users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !(bool)$user['is_active'] || !password_verify($password, $user['password_hash'])) {
    bad_request('Invalid credentials', 401);
}

json_response(['ok' => true, 'user' => ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email']]]);



