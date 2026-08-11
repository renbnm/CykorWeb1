<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $pass = $_POST['pw'];

    if (empty($name)) {
        echo "<script>alert('아이디를 입력해 주세요.'); history.back();</script>";
        exit;
    } else if (empty($pass)) {
        echo "<script>alert('비밀번호를 입력해 주세요.'); history.back();</script>";
        exit;
    }

    $sql_query = "SELECT * FROM users WHERE name = '$name' AND password = '$pass'";
    $result = mysqli_query($connect, $sql_query);

    if ($user = mysqli_fetch_assoc($result)) {
        echo "<script>alert('로그인 성공'); location.href='index.php';</script>";
        $_SESSION['id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['username'] = $user['username'];
        exit;
    } else {
        echo "<script>alert('로그인 실패, 로그인 정보를 다시 확인해 주세요.'); history.back();</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>로그인</title>
</head>
<body>
    <h1>로그인</h1>
    <form method="post" action="login.php" class="loginForm">
        <p><label>아이디 <input type="text" name="name" required></label></p>
        <p><label>비밀번호 <input type="password" name="pw" required></label></p>
        <button type="submit">로그인</button>
    </form>
    <button type="button" onclick="location.href='register.php'">회원가입</button>
</body>
</html>
