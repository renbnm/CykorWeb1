<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo '로그인이 필요합니다.';
    exit;
}

$user_id = $_SESSION['id'];
$attachment_id = $_GET['id'];

$sql = "SELECT * FROM chat_attachment WHERE id = '$attachment_id'";
$result = mysqli_query($connect,    $sql);
$attachment = mysqli_fetch_assoc($result);

if (!$attachment) {
    echo '첨부파일이 존재하지 않습니다.';
    exit;
}

$chat_id = $attachment['chat_id'];

$sql = "SELECT * FROM chat_members
        WHERE chat_id = '$chat_id' AND user_id = '$user_id'";
$result = mysqli_query($connect, $sql);
$member = mysqli_fetch_assoc($result);

if (!$member) {
    echo '파일을 볼 권한이 없습니다.';
    exit;
}

$file_name = basename($attachment['stored_name']);
$file_path = __DIR__ . '/../storage/uploads/' . $file_name;

if (!file_exists($file_path)) {
    echo '서버에 파일이 존재하지 않습니다.';
    exit;
}

if ($attachment['type'] == 'image') {
    header('Content-Type: ' . $attachment['mime_type']);
    readfile($file_path);
    exit;
}

header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . $attachment['original_name'] . '"');
readfile($file_path);
exit;
