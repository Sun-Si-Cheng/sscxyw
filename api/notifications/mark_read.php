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
$id = (int) ($_POST['id'] ?? $input['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($id > 0) {
    markNotificationRead($id, $userId);
} else {
    markAllNotificationsRead($userId);
}
echo json_encode(['ok' => true]);
