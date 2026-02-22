<?php
/**
 * 关注/取关接口 POST target_user_id
 * 返回 JSON: { "followed": true|false }
 */
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$targetId = (int) ($_POST['target_user_id'] ?? $input['target_user_id'] ?? 0);
if ($targetId <= 0) {
    echo json_encode(['error' => '无效的用户']);
    exit;
}

$currentId = (int) $_SESSION['user_id'];
$result = followToggle($currentId, $targetId);
if ($result === null) {
    echo json_encode(['error' => '不能关注自己']);
    exit;
}

if ($result === true) {
    createNotification($targetId, 'new_follower', ['user_id' => $currentId]);
}

echo json_encode(['followed' => $result]);
