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

if (!isset($_POST['chat_id']) || !isset($_FILES['attachment'])) {
    echo json_encode(['success' => false, 'message' => '채팅방 또는 파일 정보가 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int) $_SESSION['id'];
$chat_id = (int) $_POST['chat_id'];
$file = $_FILES['attachment'];

if (!is_array($file)
    || !isset($file['error'], $file['tmp_name'], $file['size'], $file['name'])
    || !is_int($file['error'])
    || !is_string($file['tmp_name'])
    || !is_int($file['size'])
    || !is_string($file['name'])) {
    echo json_encode(['success' => false, 'message' => '첨부파일 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($chat_id <= 0) {
    echo json_encode(['success' => false, 'message' => '잘못된 채팅방입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT id FROM chat_members WHERE chat_id = ? AND user_id = ?";
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $chat_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$member = mysqli_fetch_assoc($result);

if (!$member) {
    echo json_encode(['success' => false, 'message' => '채팅방에 파일을 올릴 권한이 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($file['error'] != UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '파일 업로드에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_uploaded_file($file['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => '올바른 업로드 파일이 아닙니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($file['size'] <= 0 || $file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '파일은 10MB 이하만 올릴 수 있습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime_type == 'image/jpeg') {
    $type = 'image';
    $extension = 'jpg';
} else if ($mime_type == 'image/png') {
    $type = 'image';
    $extension = 'png';
} else if ($mime_type == 'image/gif') {
    $type = 'image';
    $extension = 'gif';
} else if ($mime_type == 'image/webp') {
    $type = 'image';
    $extension = 'webp';
} else if ($mime_type == 'application/pdf') {
    $type = 'file';
    $extension = 'pdf';
} else if ($mime_type == 'text/plain') {
    $type = 'file';
    $extension = 'txt';
} else if ($mime_type == 'application/zip') {
    $type = 'file';
    $extension = 'zip';
} else {
    echo json_encode(['success' => false, 'message' => '허용되지 않는 파일 형식입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type == 'image') {
    $image_info = getimagesize($file['tmp_name']);

    if (!$image_info) {
        echo json_encode(['success' => false, 'message' => '올바른 이미지 파일이 아닙니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($image_info[0] > 10000
        || $image_info[1] > 10000
        || $image_info[0] * $image_info[1] > 40000000) {
        echo json_encode(['success' => false, 'message' => '이미지 해상도가 너무 큽니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$original_name = basename($file['name']);

if ($original_name == '' || strlen($original_name) > 255) {
    echo json_encode(['success' => false, 'message' => '파일 이름이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stored_name = bin2hex(random_bytes(16)) . '.' . $extension;
$upload_dir = __DIR__ . '/../storage/uploads';
$upload_path = $upload_dir . '/' . $stored_name;

if (!is_dir($upload_dir) && !mkdir($upload_dir, 0700, true)) {
    echo json_encode(['success' => false, 'message' => '업로드 폴더를 만들 수 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "INSERT INTO chat_attachment
        (message_id, chat_id, uploader_id, stored_name, original_name, mime_type, file_size, type)
        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";
$file_size = (int) $file['size'];
$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param(
    $stmt,
    'iisssis',
    $chat_id,
    $user_id,
    $stored_name,
    $original_name,
    $mime_type,
    $file_size,
    $type
);
$result = mysqli_stmt_execute($stmt);

if (!$result) {
    unlink($upload_path);
    echo json_encode(['success' => false, 'message' => '첨부파일 정보 저장에 실패했습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$attachment_id = mysqli_insert_id($connect);

echo json_encode([
    'success' => true,
    'attachment_id' => $attachment_id,
    'type' => $type,
    'original_name' => $original_name
], JSON_UNESCAPED_UNICODE);
