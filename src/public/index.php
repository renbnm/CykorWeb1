<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
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
        <form action="logout.php" method="post" style="display:inline;">
            <?php echo csrf_field(); ?>
            <button type="submit">로그아웃</button>
        </form>
        <button type="button" onclick="location.href='profile.php?id=<?php echo $_SESSION['id']; ?>'">프로필</button>
        <button type="button" onclick="location.href='friends.php'">친구 목록</button>
        <h2>반갑습니다. <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?> 님</h2>
    </header>

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
                $num = (int) $posts['id'];
                $title = htmlspecialchars($posts['title'], ENT_QUOTES, 'UTF-8');
                $author = htmlspecialchars($posts['author_name'], ENT_QUOTES, 'UTF-8');
                $date = htmlspecialchars($posts['created_at'], ENT_QUOTES, 'UTF-8');
                echo "<tr><td>{$num}</td><td><a href='board_view.php?id={$num}'>{$title}</a></td><td>{$author}</td><td>{$date}</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
