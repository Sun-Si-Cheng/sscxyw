<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 20);
$perPage = min(max(1, $perPage), 50);

$list = getNotifications((int) $_SESSION['user_id'], $page, $perPage);
$total = getNotificationsCount((int) $_SESSION['user_id']);

$out = [];
foreach ($list as $n) {
    $out[] = [
        'id' => (int) $n['id'],
        'type' => $n['type'],
        'data' => $n['data'] ? json_decode($n['data'], true) : null,
        'is_read' => (bool) $n['is_read'],
        'created_at' => $n['created_at'],
    ];
}
echo json_encode(['notifications' => $out, 'total' => $total]);
