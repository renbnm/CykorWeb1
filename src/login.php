<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset="utf-8">
    <title>LOGIN</title>
</head>
<body>
    <form method="post" action="login_check.php" class="loginForm">
        <h2>Login</h2>
        <div class="idform">
            <input type="text" name="name" class="name" placeholder="username" required>
        </div>
        <div class="passform">
            <input type="password" name="pw" class="pw" placeholder="password" required>
        </div>
        <button type="submit" class="btn" onclick="button()">로그인</button>

        <button type="button" class="btn" onclick="location.href='register.php'">회원가입</button>
    </form>
</body>
</html>