<?php
/**
 * 搜索 API，返回 JSON。用于前端无限滚动等。
 * GET q=关键词&scope=post|user&page=1&per_page=20
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$scope = isset($_GET['scope']) && in_array($_GET['scope'], ['post', 'user']) ? $_GET['scope'] : 'post';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));

if ($q === '') {
    echo json_encode(['posts' => [], 'users' => [], 'total_posts' => 0, 'total_users' => 0]);
    exit;
}

if ($scope === 'post') {
    $r = searchPosts($q, $page, $perPage);
    echo json_encode(['posts' => $r['items'], 'total_posts' => $r['total']]);
} else {
    $r = searchUsers($q, $page, $perPage);
    echo json_encode(['users' => $r['items'], 'total_users' => $r['total']]);
}
