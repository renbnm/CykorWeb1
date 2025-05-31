<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset="utf-8">
    <title>LOGIN</title>
</head>
<body>
    <form method="post" action="check_login.php" class="loginForm">
        <h2>Login</h2>
        <div class="idform">
            <input type="text" name="name" class="name" placeholder="username" required>
        </div>
        <div class="passform">
            <input type="password" name="pw" class="pw" placeholder="password" required>
        </div>
        <button type="submit" class="btn" onclick="button()">로그인</button>
        <a href="register.php" class="btn">회원가입</a>
    </form>
</body>
</html>