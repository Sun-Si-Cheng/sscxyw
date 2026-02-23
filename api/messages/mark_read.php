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

markConversationRead($convId, (int) $_SESSION['user_id']);
echo json_encode(['ok' => true]);
