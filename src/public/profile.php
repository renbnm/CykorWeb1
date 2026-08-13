<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}
$user_id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'friend_request') {
    $s_id = $_SESSION['id'];
    $r_id = $_POST['receiver_id'];

    if ($s_id == $r_id) {
        echo "<script>alert('자기 자신에게 친구 요청을 보낼 수 없습니다.'); history.back();</script>";
        exit;
    }

    $sql = "SELECT * FROM friendships
            WHERE (sender_id = '$s_id' AND receiver_id = '$r_id')
            OR (sender_id = '$r_id' AND receiver_id = '$s_id')";    
    $result = mysqli_query($connect, $sql);
    $friendship = mysqli_fetch_assoc($result);

    if ($friendship) {
        if ($friendship['status'] == 'accepted')
            echo "<script>alert('이미 친구입니다.'); history.back();</script>";
        else if ($friendship['status'] == 'pending')
            echo "<script>alert('이미 친구 요청을 보냈습니다.'); history.back();</script>";
        exit;
    }

    $sql = "INSERT INTO friendships (sender_id, receiver_id) VALUES ('$s_id', '$r_id')";
    $result = mysqli_query($connect, $sql);

    if ($result)
        echo "<script>alert('친구 요청을 보냈습니다.'); location.href='profile.php?id={$r_id}';</script>";
    else
        echo "<script>alert('친구 요청에 실패했습니다.'); history.back();</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'update_bio') {

    if ($user_id != $_SESSION['id']) {
        echo "<script>alert('권한이 없습니다.'); history.back();</script>";
        exit;
    }

    $bio = mysqli_real_escape_string($connect, trim($_POST['bio']));
    $sql = "UPDATE users SET bio = '$bio' WHERE id = '{$_SESSION['id']}'";
    mysqli_query($connect, $sql);

    echo "<script>alert('자기소개를 수정했습니다.'); location.href='profile.php?id={$user_id}';</script>";
    exit;
}

$sql_user = "SELECT id, username, bio FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($connect, $sql_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    echo "<script>alert('사용자가 존재하지 않습니다.'); location.href='index.php';</script>";
    exit;
}

$sql_post = "SELECT * FROM posts WHERE author_id = '$user_id' ORDER BY id DESC";
$result_post = mysqli_query($connect, $sql_post);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?php echo $user['username']; ?>의 프로필</title>
</head>
<body>
<button type="button" onclick="location.href='index.php'">홈으로</button>
<h1><?php echo $user['username']; ?>의 프로필</h1>
<p id="bio-text">
    자기소개: <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
</p>

<?php if ($user_id == $_SESSION['id']): ?>
    <button type="button" id="edit-bio-button">자기소개 수정</button>

    <form id="bio-form" method="post"
          action="profile.php?id=<?php echo $user_id; ?>" hidden>

        <input type="hidden" name="action" value="update_bio">

        <p>
            <textarea name="bio" rows="5" cols="50"><?php
                echo htmlspecialchars($user['bio']);
            ?></textarea>
        </p>

        <button type="submit">저장</button>
        <button type="button" id="cancel-bio-button">취소</button>
    </form>

    <script>
        const editButton = document.getElementById('edit-bio-button');
        const bioForm = document.getElementById('bio-form');
        const cancelButton = document.getElementById('cancel-bio-button');

        editButton.addEventListener('click', () => {
            bioForm.hidden = false;
            editButton.hidden = true;
        });

        cancelButton.addEventListener('click', () => {
            bioForm.hidden = true;
            editButton.hidden = false;
        });
    </script>
    <button type="button" onclick="location.href='friends.php'">친구 목록</button>
<?php endif; ?>    

<?php if ($user_id != $_SESSION['id']): ?>
    <form method="post" action="profile.php?id=<?php echo $user_id; ?>">
        <input type="hidden" name="action" value="friend_request">
        <input type="hidden" name="receiver_id" value="<?php echo $user_id; ?>">
        <button type="submit">친구 요청 보내기</button>
        <form action="chat.php" method="post">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <button type="submit">채팅</button>
        </form>                        
    </form>
<?php endif; ?>

<h2>게시글</h2>
<ul>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>제목</th>
            <th>작성일</th>
        </tr>
    </thead>
    <tbody>
        <?php
        while ($posts = mysqli_fetch_assoc($result_post)) {
            $num = $posts['id'];
            $title = $posts['title'];
            $date = $posts['created_at'];
            echo "<tr><td>{$num}</td><td><a href='board_view.php?id={$num}'>{$title}</a></td><td>{$date}</td></tr>";
        }
        ?>
    </tbody>
</table>    </ul>
</body>
</html>