<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$convId = (int) ($_GET['conversation_id'] ?? 0);
if ($convId <= 0) {
    echo json_encode(['error' => '无效的会话']);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
$stmt->execute([$convId, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => '无权限']);
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$list = getConversationMessages($convId, $_SESSION['user_id'], $page, 30);
$out = [];
foreach ($list as $m) {
    $out[] = [
        'id' => (int) $m['id'],
        'sender_id' => (int) $m['sender_id'],
        'content' => $m['content'],
        'content_type' => $m['content_type'],
        'created_at' => $m['created_at'],
        'username' => $m['username'],
        'nickname' => $m['nickname'],
        'avatar' => $m['avatar'],
    ];
}
echo json_encode(['messages' => $out]);
