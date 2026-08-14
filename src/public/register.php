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
    $username = isset($_POST['username']) && is_string($_POST['username'])
        ? trim($_POST['username']) : '';

    if (empty($name)) {
        echo "<script>alert('아이디를 입력해 주세요.'); history.back();</script>";
        exit;
    } else if (empty($username)) {
        echo "<script>alert('사용자 이름을 입력해 주세요.'); history.back();</script>";
        exit;
    } else if (empty($pass)) {
        echo "<script>alert('비밀번호를 입력해 주세요.'); history.back();</script>";
        exit;
    }

    if (strlen($pass) < 8 || strlen($pass) > 72) {
        echo "<script>alert('비밀번호는 8자 이상 72자 이하로 입력하세요.'); history.back();</script>";
        exit;
    }

    $sql_query = "SELECT id FROM users WHERE name = ?";
    $stmt = mysqli_prepare($connect, $sql_query);
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_fetch_assoc($result)) {
        echo "<script>alert('이미 존재하는 아이디입니다.'); history.back();</script>";
        exit;
    }

    $sql_query = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($connect, $sql_query);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_fetch_assoc($result)) {
        echo "<script>alert('이미 존재하는 사용자 이름입니다.'); history.back();</script>";
        exit;
    }

    $password_hash = password_hash($pass, PASSWORD_DEFAULT);
    $sql_insert = "INSERT INTO users (name, username, password) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($connect, $sql_insert);
    mysqli_stmt_bind_param($stmt, 'sss', $name, $username, $password_hash);
    $insert_result = mysqli_stmt_execute($stmt);

    if ($insert_result) {
        echo "<script>alert('회원가입 성공!'); location.href='login.php';</script>";
    } else {
        echo "<script>alert('회원가입 실패.'); history.back();</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>회원가입</title>
</head>
<body>
    <h1>회원가입</h1>
    <form action="register.php" method="post">
        <?php echo csrf_field(); ?>
        <p><label>아이디: <input type="text" name="name" required></label></p>
        <p><label>사용자 이름: <input type="text" name="username" required></label></p>
        <p><label>비밀번호: <input type="password" name="pw" required></label></p>
        <button type="submit">가입하기</button>
    </form>
    <button type="button" onclick="location.href='login.php'">로그인으로 돌아가기</button>
</body>
</html>
