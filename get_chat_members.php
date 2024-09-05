<?php
session_start();
require_once 'config.php';
requireLogin();

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$chatId = $_GET['chatId'];

$memberIds = $redis->sMembers("chat:$chatId:members");
$publicKeys = [];

foreach ($memberIds as $memberId) {
    $publicKeys[$memberId] = $redis->get("user:$memberId:public_key");
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($publicKeys);
