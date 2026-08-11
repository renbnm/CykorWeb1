<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$name = $_SESSION['name'];
$username = $_SESSION['username'];
$sql = 'SELECT * FROM posts ORDER BY id DESC';
$result = mysqli_query($connect, $sql);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>게시판</title>
</head>
<body>
    <header>
        <button type="button" onclick="location.href='logout.php'">로그아웃</button>
        <h2><?php echo "반갑습니다. $username 님" ?></h2>
    </header>

    <h1>게시판</h1>
    <button type="button" onclick="location.href='board_write.php'">글쓰기</button>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>제목</th>
                <th>작성자</th>
                <th>작성일</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($posts = mysqli_fetch_assoc($result)) {
                $num = $posts['id'];
                $title = $posts['title'];
                $author = $posts['author_name'];
                $date = $posts['created_at'];
                echo "<tr><td>{$num}</td><td><a href='board_view.php?id={$num}'>{$title}</a></td><td>{$author}</td><td>{$date}</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
