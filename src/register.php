<?php
session_start();
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = trim($_POST['name']);
    $pass = trim($_POST['pw']);

    if (empty($name)){
        echo "<script>alert('아이디를 입력해주세요'); history.back();</script>";
        exit;
    } else if (empty($pass)){
        echo "<script>alert('비밀번호를 입력해주세요'); history.back();</script>";
        exit;
    }

    $sql_query = "SELECT * FROM users WHERE name = '$name' AND password = '$pass'";
    $result = mysqli_query($connect, $sql_query);

    if (mysqli_fetch_assoc($result)) {
        echo "<script>alert('이미 존재하는 아이디입니다.'); history.back();</script>";
        exit;
    }
    
    $sql_insert = "INSERT INTO users (name, password) VALUES ('$name', '$pass')";
    $insert_result = mysqli_query($connect, $sql_insert);
    
    if ($insert_result) {
        echo "<script>alert('회원가입 성공!'); location.href='login.php';</script>";
    } else {
        echo "<script>alert('회원가입 실패.'); history.back();</script>";
    }
    exit;

}
?>
<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
</head>
<body>
    <h2>회원가입</h2>
    <form action="register.php" method="post">
        <label>사용자 이름: <input type="text" name="name" required></label><br>
        <label>패스워드: <input type="password" name="pw" required></label><br>
        <button type=submit>가입하기</button>
    </form>
</body>
</html>