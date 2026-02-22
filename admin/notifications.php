<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_system'])) {
    $text = trim($_POST['text'] ?? '');
    $target = trim($_POST['target'] ?? 'all');
    if ($text !== '') {
        if ($target === 'all') {
            $stmt = $pdo->query("SELECT id FROM users WHERE status = 1");
            while ($row = $stmt->fetch()) {
                $pdo->prepare("INSERT INTO notifications (user_id, type, data) VALUES (?, 'system', ?)")->execute([$row['id'], json_encode(['text' => $text])]);
            }
        }
        header('Location: notifications.php');
        exit;
    }
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON u.id = n.user_id ORDER BY n.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$list = $stmt->fetchAll();
$total = (int) $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
$totalPages = ceil($total / $perPage);

$pageTitle = '通知管理';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">通知管理</h1>

<div class="admin-card">
    <h3 style="margin-bottom: 0.75rem;">发送系统通知</h3>
    <form method="POST">
        <input type="hidden" name="send_system" value="1">
        <input type="hidden" name="target" value="all">
        <div class="form-group">
            <textarea name="text" rows="3" placeholder="通知内容" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">发送给全部用户</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-bottom: 0.75rem;">最近通知</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>类型</th>
                <th>用户</th>
                <th>数据</th>
                <th>已读</th>
                <th>时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($list as $n): ?>
            <tr>
                <td><?php echo $n['id']; ?></td>
                <td><?php echo htmlspecialchars($n['type']); ?></td>
                <td><?php echo htmlspecialchars($n['username'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars(mb_substr($n['data'] ?? '', 0, 50)); ?></td>
                <td><?php echo $n['is_read'] ? '是' : '否'; ?></td>
                <td><?php echo $n['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrap" style="margin-top: 1rem;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
