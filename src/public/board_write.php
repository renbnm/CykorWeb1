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

    $title = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) && is_string($_POST['content'])
        ? trim($_POST['content']) : '';
    $author = $_SESSION['username'];

    if (empty($title)) {
        echo "<script>alert('제목이 비어 있습니다.'); history.back();</script>";
        exit;
    } else if (empty($content)) {
        echo "<script>alert('내용이 비어 있습니다.'); history.back();</script>";
        exit;
    }

    $author_id = (int) $_SESSION['id'];
    $sql = "INSERT INTO posts (title, content, author_id, author_name) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, 'ssis', $title, $content, $author_id, $author);
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        echo "<script>alert('작성 완료되었습니다.'); location.href='index.php';</script>";
    } else {
        echo "<script>alert('작성 실패하였습니다.'); history.back();</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>글쓰기</title>
</head>
<body>
    <h1>글쓰기</h1>
    <form method="post" action="board_write.php">
        <?php echo csrf_field(); ?>
        <h2>글 제목</h2>
        <input type="text" name="title" placeholder="제목을 입력하세요"><br>
        <h2>내용을 입력해 주세요</h2>
        <textarea rows="10" cols="100" name="content" placeholder="내용을 입력하세요"></textarea><br>
        <button type="submit">작성</button>
        <button type="button" onclick="location.href='index.php'">취소</button>
    </form>
</body>
</html>
