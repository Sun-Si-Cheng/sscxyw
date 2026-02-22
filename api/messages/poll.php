<?php
/**
 * 轮询新消息（未读总数 + 各会话未读数）
 */
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$totalUnread = getTotalUnreadMessagesCount($userId);
$conversations = getConversationsForUser($userId);
$unreadByConv = [];
foreach ($conversations as $c) {
    if ($c['unread'] > 0) {
        $unreadByConv[(int) $c['id']] = (int) $c['unread'];
    }
}
echo json_encode([
    'total_unread' => $totalUnread,
    'by_conversation' => $unreadByConv,
]);
