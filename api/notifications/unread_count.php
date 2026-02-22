<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$count = getUnreadNotificationCount((int) $_SESSION['user_id']);
echo json_encode(['count' => $count]);
