<?php
session_start();
include __DIR__ . '/../app/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_POST['url']) || !isset($_POST['chat_id'])) {
    echo json_encode(['success' => false, 'message' => 'URL이 제공되지 않았습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = $_SESSION['id'];
$chat_id = $_POST['chat_id'];
$url = trim($_POST['url']);

$sql = "SELECT * FROM chat_members WHERE chat_id = '$chat_id' AND user_id = '$user_id'";
$result = mysqli_query($connect, $sql);
$member = mysqli_fetch_assoc($result);

if (!$member) {
    echo json_encode([
        'success' => false,
        'message' => '채팅방에 URL을 보낼 권한이 없습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($curl, CURLOPT_TIMEOUT, 5);

$html = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

curl_close($curl);

if ($html === false || $status_code < 200 || $status_code >= 400) {
    echo json_encode([
        'success' => false,
        'message' => '웹페이지 정보를 불러오지 못했습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dom = new DOMDocument();
@$dom->loadHTML($html);

$title_tags = $dom->getElementsByTagName('title');

if ($title_tags->length > 0) {
    $title = trim($title_tags->item(0)->nodeValue);
} else {
    $title = $url;
}

$url_sql = mysqli_real_escape_string($connect, $url);
$title_sql = mysqli_real_escape_string($connect, $title);

$sql = "INSERT INTO chat_url_preview (message_id, chat_id, uploader_id, url, title)
        VALUES (NULL, '$chat_id', '$user_id', '$url_sql', '$title_sql')";
$result = mysqli_query($connect, $sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'URL 미리보기 저장에 실패했습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$url_id = mysqli_insert_id($connect);

echo json_encode([
    'success' => true,
    'url_id' => $url_id
], JSON_UNESCAPED_UNICODE);