<?php
include "db_connect.php";
session_start();
if (!isset($_SESSION['id'])){
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$num = $_GET["id"];

$sql = "SELECT * FROM posts WHERE id = '$num'";
$result = mysqli_query($connect, $sql);
$post = mysqli_fetch_assoc($result);
if (!$post){
    echo "<script>alert('게시물이 존재하지 않습니다.'); location.href='index.php';</script>";
}
$title = $post['title'];
$author = $post['author'];
$date = $post['created_at'];
$content = $post['content'];

?>

<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo"<title>{$title}</title>";?>
</head>
<body>
    <h1><?php echo $title;?></h1>
    <?php
        if ($author == $_SESSION['id']) {
            echo "<button type=\"button\" onclick=\"location.href='board_delete.php?id={$num}'\">게시물 삭제</button>";
            echo "<button type=\"button\" onclick=\"location.href='board_edit.php?id={$num}'\">게시물 수정</button>";
        }
    ?>
     
    <div class="meta">
    <p><strong>작성자 : </strong><?php echo $author;?></p>
    <p><strong>작성시각 : </strong><?php echo $date;?></p>
    <div>
        <?php echo nl2br($content); ?>
    </div>
    <div>
        <button type="button" class="btn" onclick="location.href='index.php'">게시판 돌아가기</button>
    </div>
</body>
</html>