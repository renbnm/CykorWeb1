<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<script>alert('잘못된 접근입니다.'); location.href='index.php';</script>";
    exit;
}

$id = $_SESSION['id'];
$user_id = (int) $_POST['user_id'];

if ($id == $user_id) {
    echo "<script>alert('자기 자신과 채팅할 수 없습니다.'); history.back();</script>";
    exit;
}

$sql_user = "SELECT id, username FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($connect, $sql_user);
$user = mysqli_fetch_assoc($result_user);

if (!$user) {
    echo "<script>alert('사용자를 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

$sql = "SELECT chat.id FROM chat 
        JOIN chat_members cm1 ON chat.id = cm1.chat_id
        JOIN chat_members cm2 ON chat.id = cm2.chat_id
        WHERE cm1.user_id = '$id' AND cm2.user_id = '$user_id'";
$result_chat = mysqli_query($connect, $sql);
$chat = mysqli_fetch_assoc($result_chat);

if (!$chat) {
    mysqli_begin_transaction($connect);
    $sql_create = "INSERT INTO chat (name) VALUES (NULL)";
    $result_create = mysqli_query($connect, $sql_create);
    $chat_id = mysqli_insert_id($connect);

    $sql_add_members = "INSERT INTO chat_members (chat_id, user_id)
                        VALUES ('$chat_id', '$id'),
                               ('$chat_id', '$user_id')";
    $result_add_members = mysqli_query($connect, $sql_add_members);

    if ($result_create && $result_add_members) {
        mysqli_commit($connect);
    } else {
        mysqli_rollback($connect);
        echo "<script>alert('채팅방 생성에 실패했습니다.'); history.back();</script>";
        exit;
    } 
} else {
    $chat_id = $chat['id'];
}

$sql_messages = "SELECT * FROM chat_message
                 JOIN users ON chat_message.sender_id = users.id
                 WHERE chat_message.chat_id = '$chat_id'
                 ORDER BY chat_message.created_at ASC";

$result_messages = mysqli_query($connect, $sql_messages);

?>
