<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Workerman\Worker;
$worker = new Worker('websocket://0.0.0.0:8282');
$worker->count = 1;

$connect = null;
$rooms = [];

$worker->onWorkerStart = function () use (&$connect) {
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
        $chat_id = isset($data['chat_id']) ? $data['chat_id'] : 0;
        $user_id = isset($data['user_id']) ? $data['user_id'] : 0;

        $sql = "SELECT id FROM chat_members WHERE chat_id = '$chat_id' AND user_id = '$user_id'";
        $result = mysqli_query($connect, $sql);

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

        $content = isset($data['content']) ? trim($data['content']) : '';

        if ($content == '' || strlen($content) > 2000) {
            $error = ['type' => 'error', 'message' => '메시지는 1자 이상 2000자 이하로 입력하세요.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }
        $chat_id = $connection->chat_id;
        $user_id = $connection->user_id;
        $sql = "SELECT id FROM chat_members WHERE chat_id = '$chat_id' AND user_id = '$user_id'";
        $result = mysqli_query($connect, $sql);

        if (!$result || mysqli_num_rows($result) == 0) {
            $error = ['type' => 'error', 'message' => '채팅방에 참여할 권한이 없습니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }

        $content_sql = mysqli_real_escape_string($connect, $content);
        $sql = "INSERT INTO chat_message (chat_id, sender_id, content) VALUES ('$chat_id', '$user_id', '$content_sql')";
        $result = mysqli_query($connect, $sql);

        if (!$result) {
            $error = ['type' => 'error', 'message' => '메시지 저장에 실패했습니다.'];
            $connection->send(json_encode($error, JSON_UNESCAPED_UNICODE));
            return;
        }

        $sql = "UPDATE chat SET updated_at = NOW() WHERE id = '$chat_id'";
        mysqli_query($connect, $sql);

        $sql = "SELECT username FROM users WHERE id = '$user_id'";
        $result = mysqli_query($connect, $sql);
        $user = mysqli_fetch_assoc($result);

        $message = [
            'type' => 'message',
            'chat_id' => $chat_id,
            'sender_id' => $user_id,
            'username' => $user['username'],
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
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
