<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/security.php';
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
TcpConnection::$defaultMaxPackageSize = 65536;
$worker = new Worker('websocket://0.0.0.0:8282');
$worker->count = 1;

$connect = null;
$rooms = [];

$worker->onWebSocketConnect = function ($connection, $request) {
    $origin = $request->header('origin', '');

    if (!websocket_origin_is_allowed($origin))
        $connection->close();
};

$worker->onWorkerStart = function () use (&$connect) {
    chat_token_secret();
    $connect = mysqli_connect('db', 'root', 'root', 'user_info');

    if (!$connect) {
        echo "MySQL connection failed\n";
        exit;
    }

    mysqli_set_charset($connect, 'utf8mb4');
};

$worker->onMessage = function ($connection, $data) use (&$connect, &$rooms) {
    $data = json_decode($data, true);

    if (!is_array($data) || !isset($data['type'])) {
        $error = ['type' => 'error', 'message' => '잘못된 메시지 형식입니다.'];
        $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
        return;
    }

    $type = $data['type'];

    if ($type == 'join') {
        $token = isset($data['token']) ? $data['token'] : '';
        $claims = verify_chat_token($token);

        if (!$claims) {
            $error = ['type' => 'error', 'message' => '유효하지 않은 채팅 인증 정보입니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            $connection->close();
            return;
        }

        $chat_id = $claims['chat_id'];
        $user_id = $claims['user_id'];

        $sql = "SELECT id FROM chat_members WHERE chat_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $chat_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result || mysqli_num_rows($result) == 0) {
            $error = ['type' => 'error', 'message' => '채팅방에 참여할 권한이 없습니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            $connection->close();
            return;
        }

        $connection->chat_id = $chat_id;
        $connection->user_id = $user_id;
        $connection_id = spl_object_id($connection);
        $rooms[$chat_id][$connection_id] = $connection;
        return;
    }

    if ($type == 'send') {
        if (!isset($connection->chat_id) || !isset($connection->user_id)) {
            $error = ['type' => 'error', 'message' => '채팅방에 먼저 입장해야 합니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }

        $content = isset($data['content']) && is_string($data['content'])
            ? trim($data['content']) : '';
        $attachment_id = isset($data['attachment_id']) ? (int) $data['attachment_id'] : 0;
        $url_id = isset($data['url_id']) ? (int) $data['url_id'] : 0;

        if (($content == '' && $attachment_id == 0 && $url_id == 0) || strlen($content) > 2000) {
            $error = ['type' => 'error', 'message' => '메시지는 1자 이상 2000자 이하로 입력하세요.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }
        $chat_id = $connection->chat_id;
        $user_id = $connection->user_id;
        $sql = "SELECT id FROM chat_members WHERE chat_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $chat_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result || mysqli_num_rows($result) == 0) {
            $error = ['type' => 'error', 'message' => '채팅방에 참여할 권한이 없습니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }
        
        $attachment = null;
        $url_preview = null;

        if ($attachment_id != 0) {
            $sql = "SELECT * FROM chat_attachment
                    WHERE id = ?
                    AND chat_id = ?
                    AND uploader_id = ?
                    AND message_id IS NULL";
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param($stmt, 'iii', $attachment_id, $chat_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $attachment = mysqli_fetch_assoc($result);

            if (!$attachment) {
                $error = [
                    'type' => 'error',
                    'message' => '첨부파일 정보를 찾을 수 없습니다.'
                ];
                $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
                return;
            }
        }

        if ($url_id != 0) {
            $sql = "SELECT * FROM chat_url_preview
                    WHERE id = ?
                    AND chat_id = ?
                    AND uploader_id = ?
                    AND message_id IS NULL";
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param($stmt, 'iii', $url_id, $chat_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $url_preview = mysqli_fetch_assoc($result);

            if (!$url_preview) {
                $error = [
                    'type' => 'error',
                    'message' => 'URL 미리보기 정보를 찾을 수 없습니다.'
                ];
                $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
                return;
            }
        }

        mysqli_begin_transaction($connect);
        $sql = "INSERT INTO chat_message (chat_id, sender_id, content) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, 'iis', $chat_id, $user_id, $content);
        $result = mysqli_stmt_execute($stmt);

        if (!$result) {
            mysqli_rollback($connect);
            $error = ['type' => 'error', 'message' => '메시지 저장에 실패했습니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }

        $message_id = mysqli_insert_id($connect);
        if ($attachment_id != 0) {
            $sql = "UPDATE chat_attachment
                    SET message_id = ?
                    WHERE id = ? AND chat_id = ? AND uploader_id = ?
                    AND message_id IS NULL";
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'iiii',
                $message_id,
                $attachment_id,
                $chat_id,
                $user_id
            );
            $result = mysqli_stmt_execute($stmt);

            if (!$result || mysqli_stmt_affected_rows($stmt) != 1) {
                mysqli_rollback($connect);
                $error = [
                    'type' => 'error',
                    'message' => '첨부파일 연결에 실패했습니다.'
                ];
                $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
                return;
            }
        }

        if ($url_id != 0) {
            $sql = "UPDATE chat_url_preview
                    SET message_id = ?
                    WHERE id = ? AND chat_id = ? AND uploader_id = ?
                    AND message_id IS NULL";
            $stmt = mysqli_prepare($connect, $sql);
            mysqli_stmt_bind_param($stmt, 'iiii', $message_id, $url_id, $chat_id, $user_id);
            $result = mysqli_stmt_execute($stmt);

            if (!$result || mysqli_stmt_affected_rows($stmt) != 1) {
                mysqli_rollback($connect);
                $error = [
                    'type' => 'error',
                    'message' => 'URL 미리보기 연결에 실패했습니다.'
                ];
                $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
                return;
            }
        }

        $sql = "UPDATE chat SET updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $chat_id);
        mysqli_stmt_execute($stmt);

        $sql = "SELECT username FROM users WHERE id = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_commit($connect);

        $message = [
            'type' => 'message',
            'chat_id' => $chat_id,
            'sender_id' => $user_id,
            'username' => $user['username'],
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
            'attachment_id' => $attachment_id,
            'attachment_type' => $attachment ? $attachment['type'] : '',
            'original_name' => $attachment ? $attachment['original_name'] : '',
            'url_id' => $url_id,
            'url' => $url_preview ? $url_preview['url'] : '',
            'url_title' => $url_preview ? $url_preview['title'] : '',
        ];
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);

        foreach ($rooms[$chat_id] as $room_connection) {
            $room_connection->send($message);
        }
        return;
    }

    $error = ['type' => 'error', 'message' => '지원하지 않는 요청입니다.'];
    $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
};

$worker->onClose = function ($connection) use (&$rooms) {
    if (!isset($connection->chat_id))
        return;

    $chat_id = $connection->chat_id;
    $connection_id = spl_object_id($connection);
    unset($rooms[$chat_id][$connection_id]);

    if (count($rooms[$chat_id]) == 0)
        unset($rooms[$chat_id]);
};

Worker::runAll();
