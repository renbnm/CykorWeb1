<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
include __DIR__ . '/../app/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_is_valid()) {
        echo "<script>alert('잘못된 요청입니다.'); history.back();</script>";
        exit;
    }

    $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $pass = isset($_POST['pw']) && is_string($_POST['pw']) ? $_POST['pw'] : '';

    if (empty($name)) {
        echo "<script>alert('아이디를 입력해 주세요.'); history.back();</script>";
        exit;
    } else if (empty($pass)) {
        echo "<script>alert('비밀번호를 입력해 주세요.'); history.back();</script>";
        exit;
    }

    $sql_query = "SELECT id, name, username, password FROM users WHERE name = ?";
    $stmt = mysqli_prepare($connect, $sql_query);
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    $password_valid = false;

    if ($user) {
        $password_info = password_get_info($user['password']);

        if ($password_info['algoName'] != 'unknown') {
            $password_valid = password_verify($pass, $user['password']);
        } else if (hash_equals($user['password'], $pass)) {
            $password_valid = true;
            $new_hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql_update = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($connect, $sql_update);
            mysqli_stmt_bind_param($update_stmt, 'si', $new_hash, $user['id']);
            mysqli_stmt_execute($update_stmt);
        }
    }

    if ($password_valid) {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
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
        <?php echo csrf_field(); ?>
        <p><label>아이디 <input type="text" name="name" required></label></p>
        <p><label>비밀번호 <input type="password" name="pw" required></label></p>
        <button type="submit">로그인</button>
    </form>
    <button type="button" onclick="location.href='register.php'">회원가입</button>
</body>
</html>
