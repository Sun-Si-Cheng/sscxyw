<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '无效请求方法']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$convId = (int) ($_POST['conversation_id'] ?? $input['conversation_id'] ?? 0);
$content = trim($_POST['content'] ?? $input['content'] ?? '');
if ($convId <= 0 || $content === '') {
    echo json_encode(['error' => '参数无效']);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
$stmt->execute([$convId, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => '无权限']);
    exit;
}

$messageId = sendMessage($convId, (int) $_SESSION['user_id'], $content, 'text');
$stmt = $pdo->prepare("SELECT m.id, m.sender_id, m.content, m.content_type, m.created_at, u.username, u.nickname, u.avatar FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.id = ?");
$stmt->execute([$messageId]);
$m = $stmt->fetch();
echo json_encode([
    'message' => [
        'id' => (int) $m['id'],
        'sender_id' => (int) $m['sender_id'],
        'content' => $m['content'],
        'content_type' => $m['content_type'],
        'created_at' => $m['created_at'],
        'username' => $m['username'],
        'nickname' => $m['nickname'],
        'avatar' => $m['avatar'],
    ],
]);
