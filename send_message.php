<?php
session_start();
require_once 'config.php';
requireLogin();

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$data = json_decode(file_get_contents('php://input'), true);
$chatId = $data['chatId'];
$encryptedMessage = $data['encryptedMessage'];
$encryptedKeys = $data['encryptedKeys'];

$messageId = $redis->incr("chat:$chatId:message_id");
$timestamp = time();

$messageData = [
    'id' => $messageId,
    'sender' => $_SESSION['user_id'],
    'timestamp' => $timestamp,
    'message' => $encryptedMessage,
];

foreach ($encryptedKeys as $userId => $encryptedKey) {
    $messageData["key:$userId"] = $encryptedKey;
}

$redis->hMSet("chat:$chatId:message:$messageId", $messageData);
$redis->rPush("chat:$chatId:messages", $messageId);

echo json_encode(['success' => true]);
