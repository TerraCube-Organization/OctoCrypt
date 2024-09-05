<?php
session_start();
require_once 'config.php';
requireLogin();

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$chatId = $_GET['chatId'];
$lastId = $_GET['lastId'];

$messageIds = $redis->lRange("chat:$chatId:messages", 0, -1);
$messages = [];

foreach ($messageIds as $messageId) {
    if ($messageId > $lastId) {
        $messageData = $redis->hGetAll("chat:$chatId:message:$messageId");
        $sender = $redis->hGet("user:{$messageData['sender']}", 'username');
        
        $messages[] = [
            'message_id' => $messageData['id'],
            'username' => $sender,
            'timestamp' => date('Y-m-d H:i:s', $messageData['timestamp']),
            'encrypted_message' => $messageData['message'],
            'encrypted_key' => $messageData["key:{$_SESSION['user_id']}"]
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($messages);

