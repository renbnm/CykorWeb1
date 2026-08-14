<?php
include __DIR__ . '/../app/security.php';
start_secure_session();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !csrf_is_valid()) {
    echo "<script>alert('잘못된 요청입니다.'); location.href='index.php';</script>";
    exit;
}

session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => 'Lax'
    ]);
}

echo "<script>alert('로그아웃 되었습니다.'); location.href='login.php';</script>";
exit;
