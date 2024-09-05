<?php
session_start();
require_once 'config.php';
requireLogin();

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$data = json_decode(file_get_contents('php://input'), true);
$chatId = $data['chatId'];
$maxUses = $data['maxUses'];

$inviteCode = bin2hex(random_bytes(16));
$redis->hMSet("invite:$inviteCode", [
    'chat_id' => $chatId,
    'created_by' => $_SESSION['user_id'],
    'max_uses' => $maxUses,
    'uses' => 0
]);

echo json_encode(['success' => true, 'inviteCode' => $inviteCode]);
