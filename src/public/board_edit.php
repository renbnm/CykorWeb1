<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_is_valid()) {
        echo "<script>alert('잘못된 요청입니다.'); history.back();</script>";
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
} else {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
}

$sql = "SELECT * FROM posts WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
    $title = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) && is_string($_POST['content'])
        ? trim($_POST['content']) : '';

    if (empty($title)) {
        echo "<script>alert('제목이 비어 있습니다.'); history.back();</script>";
        exit;
    } else if (empty($content)) {
        echo "<script>alert('내용이 비어 있습니다.'); history.back();</script>";
        exit;
    }

    $author_id = (int) $_SESSION['id'];
    $sql_update = "UPDATE posts SET title = ?, content = ? WHERE id = ? AND author_id = ?";
    $stmt = mysqli_prepare($connect, $sql_update);
    mysqli_stmt_bind_param($stmt, 'ssii', $title, $content, $id, $author_id);
    $result_update = mysqli_stmt_execute($stmt);

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
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <p>제목: <input type="text" name="title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"></p>
        <p>내용:<br><textarea name="content" rows="10" cols="100"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea></p>
        <button type="submit">수정</button>
        <button type="button" onclick="location.href='board_view.php?id=<?php echo $id; ?>'">취소</button>
    </form>
</body>
</html>
