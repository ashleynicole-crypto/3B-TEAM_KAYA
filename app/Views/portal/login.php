<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';

$data = requestData();
$username = textValue($data, 'username');
$password = is_string($data['password'] ?? null) ? $data['password'] : '';
if ($username === '' || $password === '') {
    respond(['error' => 'Enter your username and password.'], 422);
}

try {
    $pdo = database();
    $query = $pdo->prepare('SELECT customer_id, username, `password` AS password_hash FROM REGISTRATION WHERE username = :username LIMIT 1');
    $query->execute(['username' => $username]);
    $user = $query->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        respond(['error' => 'Invalid username or password. Register an account before signing in.'], 401);
    }

    startUserSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['customer_id'];
    $_SESSION['username'] = $user['username'];

    respond(['ok' => true, 'username' => $user['username']]);
} catch (PDOException $error) {
    databaseUnavailable($error);
}
