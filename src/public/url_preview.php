<?php
include __DIR__ . '/../app/security.php';
start_secure_session();
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

if (!csrf_is_valid()) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_POST['url']) || !isset($_POST['chat_id'])) {
    echo json_encode(['success' => false, 'message' => 'URL이 제공되지 않았습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int) $_SESSION['id'];
$chat_id = (int) $_POST['chat_id'];
$url = is_string($_POST['url']) ? trim($_POST['url']) : '';

$target = public_http_target($url);

if (!$target) {
    echo json_encode([
        'success' => false,
        'message' => '외부의 http 또는 https URL만 사용할 수 있습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT id FROM chat_members WHERE chat_id = ? AND user_id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $chat_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($result);

if (!$member) {
    echo json_encode([
        'success' => false,
        'message' => '채팅방에 URL을 보낼 권한이 없습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($curl, CURLOPT_TIMEOUT, 5);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
curl_setopt(
    $curl,
    CURLOPT_RESOLVE,
    [$target['host'] . ':' . $target['port'] . ':' . $target['ip']]
);

$html = '';
$too_large = false;

curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, $chunk) use (&$html, &$too_large) {
    if (strlen($html) + strlen($chunk) > 1024 * 1024) {
        $too_large = true;
        return 0;
    }

    $html .= $chunk;
    return strlen($chunk);
});

$curl_result = curl_exec($curl);
$status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

curl_close($curl);

if ($curl_result === false || $too_large || $status_code < 200 || $status_code >= 300) {
    echo json_encode([
        'success' => false,
        'message' => '웹페이지 정보를 불러오지 못했습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$content_type || stripos($content_type, 'text/html') !== 0) {
    echo json_encode([
        'success' => false,
        'message' => 'HTML 웹페이지만 미리보기를 만들 수 있습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dom = new DOMDocument();
@$dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

$title_tags = $dom->getElementsByTagName('title');

if ($title_tags->length > 0) {
    $title = trim($title_tags->item(0)->nodeValue);
} else {
    $title = $url;
}

if (strlen($title) > 255 && preg_match('/^.{1,255}/us', $title, $matches))
    $title = $matches[0];

$sql = "INSERT INTO chat_url_preview (message_id, chat_id, uploader_id, url, title)
        VALUES (NULL, ?, ?, ?, ?)";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'iiss', $chat_id, $user_id, $url, $title);
$result = mysqli_stmt_execute($stmt);

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
