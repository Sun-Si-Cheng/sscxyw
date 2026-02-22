<?php
require_once __DIR__ . '/includes/functions.php';

// 检查是否登录
if (!isLoggedIn()) {
    redirect('login.php');
}

$commentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// 获取评论信息
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

// 检查评论是否存在且用户有权限删除
if (!$comment) {
    redirect('post.php?id=' . $postId);
}

$currentUser = getCurrentUser();
if ($currentUser['id'] != $comment['user_id'] && !isAdmin()) {
    redirect('post.php?id=' . $postId);
}

// 执行软删除
$stmt = $pdo->prepare("UPDATE comments SET status = 0 WHERE id = ?");
$stmt->execute([$commentId]);

// 重定向回帖子页面
redirect('post.php?id=' . $postId);
