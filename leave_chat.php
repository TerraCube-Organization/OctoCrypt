<?php
session_start();
require_once 'config.php';
requireLogin();

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$data = json_decode(file_get_contents('php://input'), true);
$chatId = $data['chatId'];
$userId = $_SESSION['user_id'];

try {
    // Remove user from chat members in Redis
    $redis->sRem("chat:$chatId:members", $userId);

    // Remove chat from user's chat list
    $redis->sRem("user:$userId:chats", $chatId);

    // If the user was an admin, remove them from admin list
    $redis->sRem("chat:$chatId:admins", $userId);

    // Remove user's encrypted keys for this chat's messages
    $messageIds = $redis->lRange("chat:$chatId:messages", 0, -1);
    foreach ($messageIds as $messageId) {
        $redis->hDel("chat:$chatId:message:$messageId", "key:$userId");
    }

    // Check if the chat is now empty
    $remainingMembers = $redis->sCard("chat:$chatId:members");
    if ($remainingMembers == 0) {
        // Delete all chat data if it's empty
        $redis->del("chat:$chatId:members");
        $redis->del("chat:$chatId:admins");
        $redis->del("chat:$chatId:messages");
        foreach ($messageIds as $messageId) {
            $redis->del("chat:$chatId:message:$messageId");
        }
        $redis->del("chat:$chatId:name");
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error in leave_chat.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ein Fehler ist aufgetreten beim Verlassen des Chats.']);
}
