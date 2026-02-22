<?php
/**
 * 获取某会话中 id > after_id 的消息（用于轮询新消息）
 */
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$convId = (int) ($_GET['conversation_id'] ?? 0);
$afterId = (int) ($_GET['after_id'] ?? 0);

if ($convId <= 0) {
    echo json_encode(['messages' => []]);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
$stmt->execute([$convId, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['messages' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.id, m.sender_id, m.content, m.content_type, m.created_at, u.username, u.nickname, u.avatar
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = ? AND m.id > ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$convId, $afterId]);
$list = $stmt->fetchAll();
echo json_encode(['messages' => $list]);
