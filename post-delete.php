<?php
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$post = getPost($postId);

// 检查帖子是否存在且用户有权限删除
if (!$post) {
    redirect('index.php');
}

$currentUser = getCurrentUser();
if ($currentUser['id'] != $post['user_id'] && !isAdmin()) {
    redirect('post.php?id=' . $postId);
}

// 执行软删除
$pdo = getDBConnection();
$stmt = $pdo->prepare("UPDATE posts SET status = 0 WHERE id = ?");
$stmt->execute([$postId]);

// 重定向到板块页面
redirect('category.php?id=' . $post['category_id']);
