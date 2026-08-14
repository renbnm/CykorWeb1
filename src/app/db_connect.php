<?php
$host = 'db';
$user = 'root';
$pass = 'root';
$db = 'user_info';

$connect = null;

try {
    $connect = mysqli_connect($host, $user, $pass, $db);
    mysqli_set_charset($connect, 'utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    http_response_code(500);
    echo 'Database connection failed.';
    exit;
}
?>
