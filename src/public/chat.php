<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<script>alert('잘못된 접근입니다.'); location.href='index.php';</script>";
    exit;
}
elseif (isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $id = $_SESSION['id'];
    
}

?>

