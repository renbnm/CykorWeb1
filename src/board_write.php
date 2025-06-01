<?php
session_start();
if (!isset($_SESSION['id'])){
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글 쓰기</title>
</head>
<body>
    <form method="POST" action="board_insert.php">
        <h2>글 제목</h2>
        <input type="text" name="title" placeholder="제목을 입력하세요."><br>
        <h2>내용을 입력해 주세요.</h2>
        <textarea  rows="10" cols="100" name="content" placeholder="내용을 입력하세요.(최대 1000자까지 입력 가능)"></textarea><br>
        <button type="button" class="btn" onclick="location.href='index.php'">취소</button>
        <input type="submit" value="작성">
    </form>
</body>
</html>