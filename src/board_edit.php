<?php
include "db_connect.php";
session_start();
if (!isset($_SESSION['id'])){
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$num = $_GET['id'];
$sql = "SELECT * FROM posts WHERE id ={$num}";
$result = mysqli_query($connect, $sql);
$post = mysqli_fetch_assoc($result);

if (!$post) {
    echo "<script>alert('게시물이 존재하지 않습니다.'); location.href='index.php';</script>";
    exit;
}

if ($post['author'] != $_SESSION['id']){
    echo "<script>alert('권한이 없습니다.'); history.back();</script>";
    exit;
}
$title = $post['title'];
$content = $post['content'];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시글 수정</title>
</head>
<body>
    <h1>게시글 수정</h1>
    <form method="post" action="board_update.php">
        <input type="hidden" name="id" value="<?php echo $num;?>"/>
        <p>제목: <input type="text" name="title" value="<?php echo $title; ?>"></p>
        <p>내용:<br><textarea name="content" rows="10" cols="100"><?php echo $content; ?></textarea></p>
        <input type="submit" value="수정하기">
        <button type="button" onclick="location.href='board_view.php?id=<?php echo $num; ?>'">취소</button>
    </form>
</body>
</html>
