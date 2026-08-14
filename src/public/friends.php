<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

$user_id = (int) $_SESSION['id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_is_valid()) {
        echo "<script>alert('잘못된 요청입니다.'); location.href='friends.php';</script>";
        exit;
    }

    $friendship_id = isset($_POST['friendship_id']) ? (int) $_POST['friendship_id'] : 0;
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'accept') {
        $sql_accept = "UPDATE friendships SET status = 'accepted', responded_at = NOW()
                       WHERE id = ? AND receiver_id = ? AND status = 'pending'";
        $stmt = mysqli_prepare($connect, $sql_accept);
        mysqli_stmt_bind_param($stmt, 'ii', $friendship_id, $user_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) != 1) {
            echo "<script>alert('처리할 수 없는 친구 요청입니다.'); location.href='friends.php';</script>";
            exit;
        }
        echo "<script>alert('친구 요청을 수락했습니다.'); location.href='friends.php';</script>";
    } else if ($action === 'reject') {
        $sql_reject = "DELETE FROM friendships
                       WHERE id = ? AND receiver_id = ? AND status = 'pending'";
        $stmt = mysqli_prepare($connect, $sql_reject);
        mysqli_stmt_bind_param($stmt, 'ii', $friendship_id, $user_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) != 1) {
            echo "<script>alert('처리할 수 없는 친구 요청입니다.'); location.href='friends.php';</script>";
            exit;
        }
        echo "<script>alert('친구 요청을 거절했습니다.'); location.href='friends.php';</script>";
    }else if ($action === 'delete') {
        $sql_unfriend = "DELETE FROM friendships
                         WHERE id = ? AND status = 'accepted'
                         AND (sender_id = ? OR receiver_id = ?)";
        $stmt = mysqli_prepare($connect, $sql_unfriend);
        mysqli_stmt_bind_param($stmt, 'iii', $friendship_id, $user_id, $user_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) != 1) {
            echo "<script>alert('삭제할 수 없는 친구 관계입니다.'); location.href='friends.php';</script>";
            exit;
        }
        echo "<script>alert('친구를 삭제했습니다.'); location.href='friends.php';</script>";
    }
    exit;
}

$sql_friends = "SELECT friendships.id AS f_id, users.id, users.username, friendships.responded_at FROM friendships 
                JOIN users ON friendships.receiver_id = users.id WHERE friendships.sender_id = ?
                AND friendships.status = 'accepted'
                UNION ALL
                SELECT friendships.id AS f_id, users.id, users.username, friendships.responded_at FROM friendships
                JOIN users ON friendships.sender_id = users.id WHERE friendships.receiver_id = ?
                AND friendships.status = 'accepted'
                ORDER BY username ASC";
        
$stmt = mysqli_prepare($connect, $sql_friends);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result_friends = mysqli_stmt_get_result($stmt);

$sql_requests = "SELECT friendships.id, users.id AS sender_id, users.username, friendships.created_at FROM friendships
                 JOIN users ON friendships.sender_id = users.id WHERE friendships.receiver_id = ?
                 AND friendships.status = 'pending'
                 ORDER BY friendships.created_at DESC";

$stmt = mysqli_prepare($connect, $sql_requests);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result_requests = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>친구 목록</title>
</head>
<body>
    <button type="button" onclick="location.href='index.php'">홈으로</button>
    <h1><?php echo htmlspecialchars($username); ?>님의 친구 목록</h1>

    <h2>현재 친구</h2>
    <?php if (mysqli_num_rows($result_friends) === 0): ?>
        <p>아직 친구가 없습니다.</p>
    <?php else: ?>
        <table border="1">
            <thead>
                <tr>
                    <th>사용자 이름</th>
                    <th>채팅</th>
                    <th>친구가 된 시각</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($friend = mysqli_fetch_assoc($result_friends)): ?>
                    <tr>
                        <td>
                            <a href="profile.php?id=<?php echo htmlspecialchars($friend['id']); ?>">
                                <?php echo htmlspecialchars($friend['username']); ?>
                            </a>
                        </td>
                        <td>
                            <form action="chat.php" method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="user_id" value="<?php echo $friend['id']; ?>">
                                <button type="submit">채팅</button>
                            </form>                        
                        </td>
                        <td><?php echo htmlspecialchars($friend['responded_at']); ?></td>
                        <td>
                            <form action="friends.php" method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friendship_id" value="<?php echo htmlspecialchars($friend['f_id']); ?>">
                                <button type="submit" name="action" value="delete">친구 삭제</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>받은 친구 요청</h2>
    <?php if (mysqli_num_rows($result_requests) === 0): ?>
        <p>받은 친구 요청이 없습니다.</p>
    <?php else: ?>
        <table border="1">
            <thead>
                <tr>
                    <th>보낸 사람</th>
                    <th>요청 시각</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($request = mysqli_fetch_assoc($result_requests)): ?>
                    <tr>
                        <td>
                            <a href="profile.php?id=<?php echo htmlspecialchars($request['sender_id']); ?>">
                                <?php echo htmlspecialchars($request['username']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                        <td>
                            <form action="friends.php" method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friendship_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <button type="submit" name="action" value="accept">수락</button>
                            </form>
                            <form action="friends.php" method="post" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="friendship_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <button type="submit" name="action" value="reject">거절</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
