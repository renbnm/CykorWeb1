<?php
include __DIR__ . '/security.php';

if (PHP_SAPI != 'cli') {
    http_response_code(404);
    exit;
}

include __DIR__ . '/db_connect.php';

$result = mysqli_query($connect, 'SELECT id, password FROM users');
$sql = 'UPDATE users SET password = ? WHERE id = ?';
$stmt = mysqli_prepare($connect, $sql);
$updated = 0;

while ($user = mysqli_fetch_assoc($result)) {
    $password_info = password_get_info($user['password']);

    if ($password_info['algoName'] != 'unknown')
        continue;

    $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
    $user_id = (int) $user['id'];
    mysqli_stmt_bind_param($stmt, 'si', $password_hash, $user_id);
    mysqli_stmt_execute($stmt);
    $updated++;
}

echo "Password migration complete: {$updated} account(s) updated.\n";
