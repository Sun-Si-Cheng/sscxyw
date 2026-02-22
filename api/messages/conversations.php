<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['error' => '请先登录']);
    exit;
}

$list = getConversationsForUser((int) $_SESSION['user_id']);
$out = [];
foreach ($list as $row) {
    $out[] = [
        'id' => (int) $row['id'],
        'updated_at' => $row['updated_at'],
        'last_content' => $row['last_content'],
        'last_at' => $row['last_at'],
        'last_sender_id' => (int) $row['last_sender_id'],
        'unread' => (int) $row['unread'],
        'other' => $row['other'] ? [
            'id' => (int) $row['other']['id'],
            'username' => $row['other']['username'],
            'nickname' => $row['other']['nickname'],
            'avatar' => $row['other']['avatar'],
        ] : null,
    ];
}
echo json_encode(['conversations' => $out]);
