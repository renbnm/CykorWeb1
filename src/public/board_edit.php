<?php
include __DIR__ . '/../app/db_connect.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
} else {
    $id = $_GET['id'];
}

$sql = "SELECT * FROM posts WHERE id = '$id'";
$result = mysqli_query($connect, $sql);
$post = mysqli_fetch_assoc($result);

if (!$post) {
    echo "<script>alert('게시물이 존재하지 않습니다.'); location.href='index.php';</script>";
    exit;
}

if ($post['author_id'] != $_SESSION['id']) {
    echo "<script>alert('권한이 없습니다.'); history.back();</script>";
    exit;
}

$title = $post['title'];
$content = $post['content'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) {
        echo "<script>alert('제목이 비어 있습니다.'); history.back();</script>";
        exit;
    } else if (empty($content)) {
        echo "<script>alert('내용이 비어 있습니다.'); history.back();</script>";
        exit;
    }

    $sql_update = "UPDATE posts SET title = '$title', content = '$content' WHERE id = '$id'";
    $result_update = mysqli_query($connect, $sql_update);

    if ($result_update) {
        echo "<script>alert('수정이 완료되었습니다.'); location.href='board_view.php?id={$id}';</script>";
    } else {
        echo "<script>alert('수정에 실패했습니다.'); history.back();</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시글 수정</title>
</head>
<body>
    <h1>게시글 수정</h1>
    <form method="post" action="board_edit.php">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <p>제목: <input type="text" name="title" value="<?php echo $title; ?>"></p>
        <p>내용:<br><textarea name="content" rows="10" cols="100"><?php echo $content; ?></textarea></p>
        <button type="submit">수정</button>
        <button type="button" onclick="location.href='board_view.php?id=<?php echo $id; ?>'">취소</button>
    </form>
</body>
</html>
