<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();

$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1");
$stats['users'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1 AND DATE(created_at) = CURDATE()");
$stats['users_today'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 1");
$stats['posts'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 1");
$stats['comments'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM messages");
$stats['messages'] = $stmt->fetchColumn();

$pageTitle = '仪表盘';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">仪表盘</h1>

<div class="admin-card">
    <h3 style="margin-bottom: 1rem;">数据概览</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
        <div>
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo (int) $stats['users']; ?></div>
            <div style="color: #6b7280; font-size: 0.875rem;">用户总数</div>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo (int) $stats['users_today']; ?></div>
            <div style="color: #6b7280; font-size: 0.875rem;">今日新增用户</div>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo (int) $stats['posts']; ?></div>
            <div style="color: #6b7280; font-size: 0.875rem;">帖子总数</div>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo (int) $stats['comments']; ?></div>
            <div style="color: #6b7280; font-size: 0.875rem;">评论总数</div>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 600;"><?php echo (int) $stats['messages']; ?></div>
            <div style="color: #6b7280; font-size: 0.875rem;">站内消息</div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
