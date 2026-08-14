<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
include __DIR__ . '/../app/db_connect.php';

if (!isset($_SESSION['id'])) {
    echo '로그인이 필요합니다.';
    exit;
}

$user_id = (int) $_SESSION['id'];
$attachment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$sql = "SELECT * FROM chat_attachment WHERE id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'i', $attachment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attachment = mysqli_fetch_assoc($result);

if (!$attachment) {
    echo '첨부파일이 존재하지 않습니다.';
    exit;
}

$chat_id = $attachment['chat_id'];

$sql = "SELECT id FROM chat_members
        WHERE chat_id = ? AND user_id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $chat_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
    header('Content-Length: ' . filesize($file_path));
    header('X-Content-Type-Options: nosniff');
    readfile($file_path);
    exit;
}

$original_name = str_replace(["\r", "\n"], '', $attachment['original_name']);
header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($original_name));
header('Content-Length: ' . filesize($file_path));
header('X-Content-Type-Options: nosniff');
readfile($file_path);
exit;
