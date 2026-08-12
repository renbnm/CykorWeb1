<?php
include __DIR__ . '/../app/db_connect.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$id = $_GET['id'];
$sql = "SELECT * FROM posts WHERE id = '$id'";
$result = mysqli_query($connect, $sql);
$post = mysqli_fetch_assoc($result);

if (!$post) {
    echo "<script>alert('게시물이 존재하지 않습니다.'); location.href='index.php';</script>";
    exit;
}

$title = $post['title'];
$author_id = $post['author_id'];
$author_name = $post['author_name'];
$date = $post['created_at'];
$content = $post['content'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo "<title>{$title}</title>"; ?>
</head>
<body>
    <h1><?php echo $title; ?></h1>
    <?php
    if ($author_id == $_SESSION['id']) {
        echo "<form method='post' action='board_delete.php' style='display:inline;'>";
        echo "<input type='hidden' name='id' value='{$id}'>";
        echo "<button type='submit'>게시물 삭제</button></form>";
        echo "<button type='button' onclick=\"location.href='board_edit.php?id={$id}'\">게시물 수정</button>";
    }
    ?>
    <div class="meta">
        <p><strong>작성자:<a href="profile.php?id=<?php echo $author_id; ?>"><?php echo htmlspecialchars($author_name); ?></a></strong></p>
        <p><strong>작성시각: </strong><?php echo $date; ?></p>
    </div>
    <div><?php echo nl2br($content); ?></div>
    <div><button type="button" onclick="location.href='index.php'">게시판으로 돌아가기</button></div>
</body>
</html>
