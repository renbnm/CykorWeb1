<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<script>alert('잘못된 요청입니다.'); location.href='index.php';</script>";
    exit;
}

if (!csrf_is_valid()) {
    echo "<script>alert('잘못된 요청입니다.'); location.href='index.php';</script>";
    exit;
}

$num = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$sql = "SELECT * FROM posts WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'i', $num);
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

$author_id = (int) $_SESSION['id'];
$sql_delete = "DELETE FROM posts WHERE id = ? AND author_id = ?";
$stmt = mysqli_prepare($connect, $sql_delete);
mysqli_stmt_bind_param($stmt, 'ii', $num, $author_id);
$result_delete = mysqli_stmt_execute($stmt);

if ($result_delete) {
    echo "<script>alert('삭제가 완료되었습니다.'); location.href='index.php';</script>";
} else {
    echo "<script>alert('삭제에 실패했습니다.'); history.back();</script>";
}
exit;
