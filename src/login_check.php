<?php
session_start();
include "db_connect.php";
$name = $_POST['name'];
$pass = $_POST['pw'];

if (empty($name)){
    echo "<script>alert('아이디를 입력해주십시오'); history.back();</script>";
    exit;
} else if(empty($pass)){
    echo "<script>alert('비밀번호를 입력해주십시오'); history.back();</script>";
    exit;
}

$sql_query = "SELECT * FROM users WHERE name = '$name' AND password = '$pass'";
$result = mysqli_query($connect, $sql_query);

if ($user = mysqli_fetch_assoc($result)){
    $_SESSION['id'] = $user['name'];
    echo "<script>alert('로그인 성공'); location.href='index.php';</script>";
    exit;
} else {
    echo "<script>alert('로그인 실패, 로그인 정보를 다시 확인해주세요.'); history.back();</script>";
}
?>